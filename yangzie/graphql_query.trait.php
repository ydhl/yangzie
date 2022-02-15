<?php

namespace yangzie;

/**
 * graphql 查询处理
 */
trait Graphql_Query{
    private function get_andor($op){
        switch (strtolower($op)) {
            case 'and':
                return 'and';
            case 'or':
                return 'or';
            default:
                throw new YZE_FatalException("not support operation: " . $op);
        }
    }
    private function filter_array_value($values){
        $_ = [];
        $dba = YZE_DBAImpl::getDBA();
        foreach ((array)$values as $v){
            $_[] = $dba->quote($v);
        }
        return join(",", $_);
    }
    private function get_op($op){
        switch ($op){
            case '=': return '=';
            case '>=': return '>=';
            case '<=': return '<=';
            case '>': return '>';
            case '<': return '<';
            case '!=':
            case '<>': return '!=';
            case 'like': return 'like';
            case 'not like': return 'not like';
            case 'between': return 'between';
            case 'find_in_set': return 'find_in_set';
            case 'in': return 'in';
            case 'not in': return 'not in';
            case 'is not null': return 'is not null';
            case 'is null': return 'is null';
            default: throw new YZE_FatalException("not support operation: ".$op);
        }
    }
    private $models = [];
    private function get_models() {
        if (!$this->models)
            $this->models = $this->find_All_Models();

        return $this->models;
    }

    /**
     * 查找出系统中所有的Model
     *
     * @return ['table name'=>'Model Class Full Name']
     */
    private function find_All_Models(): array
    {
        $models = [];
        foreach (glob(YZE_APP_MODULES_INC . '*') as $module) {
            $moduleName = basename($module);
            foreach (glob($module . '/models/*.class.php') as $model) {
                $basename = explode("_", basename($model, '.class.php'));
                $basename = array_map(function ($item) {
                    return ucfirst($item);
                }, $basename);
                $basename = 'app\\' . $moduleName . '\\' . join("_", $basename);
                require_once $model;
                $modelObject = new $basename();
                if (method_exists($modelObject,"is_enable_graphql") && $modelObject->is_enable_graphql()){
                    $models[$modelObject::TABLE] = $modelObject::CLASS_NAME;
                }
            }
        }
        return $models;
    }
    /**
     * 针对model的查询
     * @param $models
     * @param $class
     * @param GraphqlSearchNode $node
     * @param $id
     * @param $wheres
     * @param $dql
     * @param $total
     * @return array|array[]|mixed
     * @throws YZE_DBAException
     * @throws YZE_FatalException
     */
    public function model_query($class, GraphqlSearchNode $node, $id, $wheres, $dql=[], &$total=0){
        $models = $this->get_models();
        $table = $class::TABLE;
        $dba = YZE_DBAImpl::getDBA();

        $result = [];
        $searchColumns = [];
        $foreignKeyColumns = [];
        /**
         * 外键关联配置：[filed_name=>[column=>关联的字段, target_class=>"",target_column=>"", 'node'=>查询结构体,'ids'=>[关联的字段的具体值列表]]]
         */
        $searchAssocTables = [];
        /**
         * 外键关联配置：[filed_name=>[column=>关联的字段, target_class=>"",target_column=>""]]
         */
        $relationConfig = [];
        /**
         * 查询出来的关联表数据：[filed_name=>[key=>[field_name=>field_value]]]
         */
        $assocTableRecords = [];
        if (!class_exists($class)) throw new YZE_FatalException("field '{$node->name}' not exist");
        $modelObject = new $class();
        $columnConfig = $modelObject->get_columns();
        foreach($modelObject->get_relation_columns() as $column => $config){
            $config['column'] = $column;
            $relationConfig[$config['graphql_field']] = $config;
        }
        $custom_fields = [];
        if (method_exists($modelObject, "custom_graphql_fields")){
            foreach ($modelObject->custom_graphql_fields() as $field){
                $custom_fields[] = $field->name;
            }
        }

        foreach ($node->sub as $sub) {
            if ($sub->name == "__typename"){ // 内省关键字处理
                $result["__typename"] = "__Field";
            }elseif (!$sub->sub ){// 直接查询的字段
                if (!@$columnConfig[$sub->name]) throw new YZE_FatalException("field '{$sub->name}' not exist");
                $result[$sub->name] = null;
                $searchColumns[] = $sub->name;
            }else if (@$relationConfig[$sub->name]){ // 查询的关联表
                $result[$sub->name] = null;
                $searchAssocTables[$sub->name] = $relationConfig[$sub->name];
                $searchAssocTables[$sub->name]['node'] = $sub;
                $searchAssocTables[$sub->name]['ids'] = [];
                $foreignKeyColumns[] = $searchAssocTables[$sub->name]['column'];
            }else if (@$custom_fields[$sub->name]){ // 查询通过custom_graphql_field定义的字段
                if (method_exists($modelObject, "query_graphql_fields")){
                    return $modelObject->query_graphql_fields($sub);
                }
                return [];
            }else{
                throw new YZE_FatalException("field '{$sub->name}' not exist");
            }
        }

        // 查询字段
        $where = "";
        if ($id){
            $where .= ' '.$modelObject->get_key_name()."=".$id;
        }else if ($wheres){
            if (!is_array(reset($wheres))){
                $wheres = [$wheres];
            }

            foreach ($wheres as $index => $_where){
                if (!@$columnConfig[$_where['column']]){
                    throw new YZE_FatalException("field '".$_where['column']."' not exist");
                }
                $op = $this->get_op($_where['op']);
                $where .= ' '.$_where['column'].' '.$_where['op'];
                if ($op == "in" || $op =='not in' || $op =='find_in_set'){
                    $where .= "(".$this->filter_array_value($_where['value']).")";
                }else{
                    $where .= ' '.$dba->quote($_where['value']);
                }
                if ($index+1 != count($wheres)){
                    $where .= ' '.$this->get_andor($_where['andor']);
                }
            }
        }
        $pagination = '';
        $orderby = '';
        if ($dql){
            $page = intval(@$dql['page']);
            $page = $page <=0 ? 1 : $page;
            $count = intval(@$dql['count']);
            $count = $count <=0 ? 10 : $count;
            $page = ($page - 1 ) * $count;

            if (@$dql['orderBy']){
                $sorts = ['ASC'=>'ASC','DESC'=>'DESC',''=>'ASC'];
                if (!$columnConfig[@$dql['orderBy']]) throw new YZE_FatalException("orderBy field '{$dql['orderBy']}' not exist");
                $sort = @$sorts[strtoupper(@$dql['sort']?:"")];
                if (!$sort) throw new YZE_FatalException("sort type '{$dql['sort']}' not support");
                $orderby .= ' order by '.$dql['orderBy'].' '.$sort;
            }

            if (@$dql['groupBy']){
                if (!$columnConfig[$dql['groupBy']]) throw new YZE_FatalException("groupBy field '{$dql['groupBy']}' not exist");
                $orderby .= ' group by '.$dql['groupBy'];
            }

            $pagination = " limit {$page}, $count";
        }

        $totalRst = $dba->nativeQuery("select count(*) as t from `{$table}` ".($where ? "where {$where}" : "").$orderby);
        $totalRst->next();
        $total = intval($totalRst->f('t'));

        $rsts = $dba->nativeQuery("select ".join(',', array_merge($foreignKeyColumns,$searchColumns))
            ." from `{$table}` ".($where ? "where {$where}" : "").$orderby.$pagination);

        $rsts = $rsts->get_results();
        // 对查询的数据中的每行进行过滤，确保返回的顺序和请求的顺序一直
        $rsts = array_map(function ($item) use($result, &$searchAssocTables){
            // 关联表的外键id列表，后面关联表查询使用
            foreach ($searchAssocTables as &$value){
                $value['ids'][] = $item[$value['column']];
            }
            return array_merge($result, $item);
        }, $rsts);

        // 查询关联表的字段
        foreach ($searchAssocTables as $fieldName=>$assocInfo){
            $targetClass = $assocInfo['target_class'];
            $targetColumn = $assocInfo['target_column'];
            if (!class_exists($targetClass)) {
                continue;
            }
            $targetModel = new $targetClass();
            $key_name = $targetModel->get_key_name();
            $assocTableRecords[$fieldName] = [];

            // 判断是否有id查询，为了关联查询，需要把关联表的主键也查询出来
            $hasKey = GraphqlIntrospection::find_search_node_by_name($assocInfo['node']->sub, $key_name);
            if (!$hasKey->has_value()){
                $id = new GraphqlSearchNode();
                $id->name = $key_name;
                $assocInfo['node']->sub[] = $id;
            }

            foreach($this->model_query($targetClass, $assocInfo['node'], null,
                ['column'=>$targetColumn, 'op'=>'in', 'value'=>join(",",array_unique($assocInfo['ids']))]) as $item){
                $key = $item[$key_name];
                if (!$hasKey->has_value()){
                    unset($item[$key_name]);
                }
                $assocTableRecords[$fieldName][$key] = $item;
            }
        }
        //去掉那些为了关联查询而增加的额外查询字段，只返回用户查询的内容
        $rsts = array_map(function ($item) use($foreignKeyColumns, $searchColumns, $assocTableRecords, $searchAssocTables){
            // 把关联表对应的数据放到item中对应的位置去
            foreach($assocTableRecords as $fieldName => $data){
                $myColumn = $searchAssocTables[$fieldName]['column'];
                $item[$fieldName] = @$data[$item[$myColumn]] ?: null;
            }
            // 移出因为关联查询而临时添加的字段
            foreach ($foreignKeyColumns as $column){
                if (!in_array($column, $searchColumns)){
                    unset($item[$column]);
                }
            }
            return $item;
        }, $rsts);
        return $rsts;
    }
}

?>
