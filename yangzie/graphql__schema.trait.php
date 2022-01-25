<?php

namespace yangzie;

/**
 * graphql schema 内省处理
 */
trait Graphql__Schema{

    public function __schema($node){
        $schemeResult = [];
        $models = $this->find_All_Models();
        foreach ($node['sub'] as $schemaNode){
            $schemaName = strtoupper($schemaNode['name']);
            switch ($schemaName){
                case 'QUERYTYPE': $schemeResult[$schemaNode['name']] = $this->schema_Query_type($models, $schemaNode);break;
                case 'SUBSCRIPTIONTYPE': $schemeResult[$schemaNode['name']] = $this->schema_Subscription_Type($schemaNode);break;
                case 'MUTATIONTYPE': $schemeResult[$schemaNode['name']] = $this->schema_Mutation_Type($models, $schemaNode);break;
                case 'TYPES': $schemeResult[$schemaNode['name']] = $this->all_schema_Types($models, $schemaNode);break;
                case 'DIRECTIVES': $schemeResult[$schemaNode['name']] = $this->schema_Directives($schemaNode);break;
                case 'DESCRIPTION': $schemeResult[$schemaNode['name']] = YZE_APP_NAME." schema for GraphiQL";break;
            }
        }
        return $schemeResult;
    }

    /**
     * 返回系统有哪些订阅操作
     * @param $node
     * @return string
     */
    private function schema_Subscription_Type($node) {
        return null;
    }

    /**
     * 返回系统有哪些指令类型
     * @param $node
     * @return string
     */
    private function schema_Directives($node) {
        return null;
    }

    private function schema_Query_type($models, $node) {
        // 根据查询的内容返回
        $rst = [];
        foreach ($node['sub'] as $sub){
            $subName = strtoupper($sub['name']);
            switch ($subName){
                case 'NAME': $rst[$sub['name']] = 'YangzieQuery'; break;
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = 'Yangzie Query entry'; break;
                case 'FIELDS': {
                    $queryFileds = [];
                    foreach ($models as $table => $class){
                        $modelObject = new $class;
                        $result = [];
                        foreach ($sub['sub'] as $field_sub) {
                            $subName = strtoupper($field_sub['name']);
                            switch ($subName) {
                                case 'NAME': $result[$field_sub['name']] = $table; break;
                                case 'DESCRIPTION': $result[$field_sub['name']] = $modelObject->get_description(); break;
                                case 'TYPE': $result[$field_sub['name']] = $this->_introspection_field_type($field_sub, [ 'kind'=> 'OBJECT', 'ofType'=>null, 'name'=>$table ]); break;
                                // TODO 以下要完善
                                case 'ARGS': $result[$field_sub['name']] = []; break;
                                case 'ISDEPRECATED': $result[$field_sub['name']] = false; break;
                                case 'DEPRECATIONREASON': $result[$field_sub['name']] = ''; break;
                            }
                        }
                        $queryFileds[] = $result;
                    }
                    $rst[$sub['name']] = $queryFileds; break;
                }
                // TODO 以下要完善
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        return $rst;
    }

    private function _get_mutations($subNodes){
        $result = [];
        foreach ($this->basic_types() as $type => $info){
            $mutaionFileds = [];
            foreach ($subNodes as $filed_sub) {
                $subName = strtoupper($filed_sub['name']);
                switch ($subName) {
                    case 'ARGS': $mutaionFileds[$filed_sub['name']] = $this->_introspection_field_args($filed_sub, [[
                        "name"=> "value",
                        "description"=> null,
                        "type"=> [
                            "kind"=> "SCALAR",
                            "name"=> $type,
                            "ofType"=> null
                        ],
                        "defaultValue"=> null,
                        "isDeprecated"=> false,
                        "deprecationReason"=> null
                    ]]);break;
                    case 'NAME': $mutaionFileds[$filed_sub['name']] = 'set'.ucfirst(strtolower($type));break;
                    case 'DESCRIPTION': $mutaionFileds[$filed_sub['name']] = 'Set the '.$info['description'].' field';break;
                    case 'TYPE': $mutaionFileds[$filed_sub['name']] = $this->_introspection_field_type($filed_sub, [
                        'kind'=> 'SCALAR',
                        'ofType'=>null,
                        'name'=>$type
                    ]);break;
                    case 'ISDEPRECATED': $mutaionFileds[$filed_sub['name']] = false;break;
                    case 'DEPRECATIONREASON': $mutaionFileds[$filed_sub['name']] = "";break;
                }
            }
            $result[] = $mutaionFileds;
        }
        return $result;
    }

    private function schema_Mutation_Type($models, $node) {

        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT';break;
                case 'NAME': $rst[$sub['name']] = 'YangzieMutation';break;
                case 'DESCRIPTION': $rst[$sub['name']] = 'Yangzie Mutation entry';break;
                case 'FIELDS': // 这里就是有哪些更新操作
                {
                    $rst[$sub['name']] = $this->_get_mutations($sub['sub']);
                    break;
                }
                case 'INPUTFIELDS': $rst[$sub['name']] = null;break;
                case 'INTERFACES': $rst[$sub['name']] = [];break;
                case 'ENUMVALUES': $rst[$sub['name']] = null;break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null;
            }
        }
        return $rst;
    }

    /**
     * 基础数据类型
     */
    private function _schema_Basic_type(&$results, $node){
        $basicTypes = $this->basic_types();

        foreach ($basicTypes as $basicType=>$info){
            $rst = [];
            foreach ($node['sub'] as $sub) {
                $subName = strtoupper($sub['name']);
                switch ($subName) {
                    case 'KIND': $rst[$sub['name']] = 'SCALAR'; break;
                    case 'NAME': $rst[$sub['name']] = $basicType; break;
                    case 'DESCRIPTION': $rst[$sub['name']] = $info['description']; break;
                    case 'FIELDS': $rst[$sub['name']] = null; break;
                    case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                    case 'INTERFACES': $rst[$sub['name']] = null; break;
                    case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                    case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
                }
            }
            $results[] = $rst;
        }
    }

    /**
     * 返回系统有哪些查询类型（也就是Model）,默认情况下每个Modal的cloumn都将返回，表示都可以被graphql查询
     * @param $node
     * @return array
     */
    private function all_schema_Types($models, $node) {
        if (!$node || !@$node['sub']) return [];
        $results = [];
        $results[] = $this->schema_Query_type($models, $node);
        $results[] = $this->schema_Mutation_Type($models, $node);
        $this->_schema_Basic_type($results, $node);

        // 类型系统的基础类型
        $this->_introspection__schema($results, $node);
        $this->_introspection__type($results, $node);
        $this->_introspection__typekind($results, $node);
        $this->_introspection__field($results, $node);
        $this->_introspection__inputvalue($results, $node);
        $this->_introspection__enumvalue($results, $node);
        $this->_introspection__directive($results, $node);
        $this->_introspection__directiveLocation($results, $node);

        // 每一个model都是types的一级节点
        foreach ($models as $table => $model){
            $modelObject = new $model;
            // 根据scheme请求返回内容
            $modelResult = [];
            foreach ($node['sub'] as $sub){
                // 类型scheme查询基本是已知的
                $subName = strtoupper($sub['name']);
                switch ($subName){
                    case 'NAME': $modelResult[$sub['name']] = "{$table}"; break; // 表名作为name
                    case 'KIND': $modelResult[$sub['name']] = "OBJECT"; break; // Model都是OBJECT
                    case 'DESCRIPTION': $modelResult[$sub['name']] = $modelObject->get_description(); break;
                    case 'FIELDS': $modelResult[$sub['name']] = $this->get_Model_Fields($modelObject, $sub); break;
                    // TODO 以下要完善
                    case 'INPUTFIELDS': $modelResult[$sub['name']] = null; break;
                    case 'INTERFACES': $modelResult[$sub['name']] = []; break;
                    case 'ENUMVALUES': $modelResult[$sub['name']] = null; break;
                    case 'POSSIBLETYPES': $modelResult[$sub['name']] = null; break;
                }
            }
            $results[] = $modelResult;
        }
        return $results;
    }


    /**
     * 查找出系统中所有的Model
     *
     * @return ['table name'=>'Model Class Full Name']
     */
    private function find_All_Models(){
        $models = [];
        foreach (glob(YZE_APP_MODULES_INC.'*') as $module){
            $moduleName = basename($module);
            foreach (glob($module.'/models/*.class.php') as $model){
                $basename = explode("_", basename($model, '.class.php'));
                $basename = array_map(function ($item){
                    return ucfirst($item);
                }, $basename);
                $basename = 'app\\'.$moduleName.'\\'.join("_", $basename);
                require_once $model;
                $modelObject = new $basename();
                $models[$modelObject::TABLE] = $modelObject::CLASS_NAME;
            }
        }
        return $models;
    }

    /**
     * 根据scheme查询返回model需要返回的字段信息
     *
     * @param YZE_Model $model
     * @param $node 查询结构体
     * @return []
     */
    private function get_Model_Fields(YZE_Model $model, $node){
        if (!$model || !$node) return [];
        $args = @$node['args']; // 目前还用不上
        $columns = $model->get_columns();
        $result = [];
        foreach ($columns as $columnName => $columnConfig){
            $columnResult = [];
            foreach ($node['sub'] as $sub){
                $subName = strtoupper($sub['name']);
                switch ($subName){
                    case 'NAME': $columnResult[$sub['name']] = $columnName; break;
                    case 'DESCRIPTION': $columnResult[$sub['name']] = $model->get_column_mean($columnName); break;
                    case 'ARGS': $columnResult[$sub['name']] = $this->get_Model_Field_Args($columnConfig, $columnName, $sub); break;
                    case 'TYPE': $columnResult[$sub['name']] = $this->get_Model_Field_Type($columnConfig, $columnName, $sub); break;
                    case 'ISDEPRECATED': $columnResult[$sub['name']] = false; break;
                    case 'DEPRECATIONREASON': $columnResult[$sub['name']] = ''; break;
                }
            }
            $result[] = $columnResult;
        }
        return $result;
    }

    /**
     * 获取查询某个字段的查询条件
     * @param $columnName
     * @param $node
     */
    private function get_Model_Field_Args($columnConfig, $columnName, $node){
        return []; //TODO
    }

    /**
     * 获取字段的类型
     * @param $columnName
     * @param $node
     * @return array
     */
    private function get_Model_Field_Type($columnConfig, $columnName, $node){
        if (!$columnName || !$node) return [];
        $typeResult = [];
        foreach ($node['sub'] as $sub){
            // 类型scheme查询基本是已知的
            $subName = strtoupper($sub['name']);
            switch ($subName){
                case 'NAME': $typeResult[$sub['name']] = $columnConfig['type']; break;
                case 'KIND': $typeResult[$sub['name']] = 'SCALAR'; break;
                case 'OFTYPE': $typeResult[$sub['name']] = null; break;
            }
        }
        return $typeResult;
    }
}

?>
