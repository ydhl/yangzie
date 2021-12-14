<?php

namespace yangzie;

/**
 * graphql schema 内省处理
 */
trait Graphql_Schema{

    /**
     * 返回系统的查询入口，默认叫YangzieQuery
     * @param $node
     * @return string
     */
    private function schemaQueryType($node) {
        if (!$node || !@$node['sub']) return '';
        $result = [];
        foreach ($node['sub'] as $sub){
            $name = strtoupper($sub['name']);
            switch ($name){
                case 'NAME' : $result[$sub['name']] = "YangzieQuery";break;
            }
        }
        return $result;
    }

    /**
     * 返回系统的更改操作入口，默认叫YangzieMutation
     * @param $node
     * @return string
     */
    private function schemaMutationType($node) {
        if (!$node || !@$node['sub']) return '';
        $result = [];
        foreach ($node['sub'] as $sub){
            $name = strtoupper($sub['name']);
            switch ($name){
                case 'NAME' : $result[$sub['name']] = "YangzieMutation";break;
            }
        }
        return $result;
    }

    /**
     * 返回系统有哪些订阅操作
     * @param $node
     * @return string
     */
    private function schemaSubscriptionType($node) {
        return null;
    }

    /**
     * 返回系统有哪些指令类型
     * @param $node
     * @return string
     */
    private function schemaDirectives($node) {
        return null;
    }

    private function _schemaQuerytype($models, $node) {
        // 根据查询的内容返回
        $result = [];
        foreach ($node['sub'] as $sub){
            $subName = strtoupper($sub['name']);
            switch ($subName){
                case 'NAME': $result[$sub['name']] = 'YangzieQuery'; break;
                case 'KIND': $result[$sub['name']] = 'OBJECT'; break;
                case 'DESCRIPTION': $result[$sub['name']] = 'Yangzie Query entry'; break;
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
                                case 'TYPE': $result[$field_sub['name']] = [ 'kind'=> 'OBJECT', 'ofType'=>null, 'name'=>$table ]; break;
                                // TODO 以下要完善
                                case 'ARGS': $result[$field_sub['name']] = []; break;
                                case 'ISDEPRECATED': $result[$field_sub['name']] = false; break;
                                case 'DEPRECATIONREASON': $result[$field_sub['name']] = ''; break;
                            }
                        }
                        $queryFileds[] = $result;
                    }
                    $result[$sub['name']] = $queryFileds; break;
                }
                // TODO 以下要完善
                case 'INPUTFIELDS': $result[$sub['name']] = null; break;
                case 'INTERFACES': $result[$sub['name']] = []; break;
                case 'ENUMVALUES': $result[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $result[$sub['name']] = null; break;
            }
        }
        return $result;
    }

    private function _schemaMutaiontype($models, $node) {

        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT';break;
                case 'NAME': $rst[$sub['name']] = 'YangzieMutation';break;
                case 'DESCRIPTION': $rst[$sub['name']] = 'Yangzie Mutation entry';break;
                case 'FIELDS': // 这里就是有哪些更新操作
                {
                    $mutaionFileds = [];
                    foreach ($sub['sub'] as $filed_sub) {
                        $subName = strtoupper($filed_sub['name']);
                        switch ($subName) {
                            case 'ARGS': $mutaionFileds[$filed_sub['name']] = [
                                "name"=> "value",
                                "description"=> null,
                                "type"=> [
                                    "kind"=> "SCALAR",
                                    "name"=> "string",
                                    "ofType"=> null
                                ],
                                "defaultValue"=> null,
                                "isDeprecated"=> false,
                                "deprecationReason"=> null
                            ];break;
                            case 'NAME': $mutaionFileds[$filed_sub['name']] = 'setString';break;
                            case 'DESCRIPTION': $mutaionFileds[$filed_sub['name']] = 'Set the string field';break;
                            case 'TYPE': $mutaionFileds[$filed_sub['name']] = [
                                'kind'=> 'SCALAR',
                                'ofType'=>null,
                                'name'=>'string'
                            ];break;
                            case 'ISDEPRECATED': $mutaionFileds[$filed_sub['name']] = false;break;
                            case 'DEPRECATIONREASON': $mutaionFileds[$filed_sub['name']] = "";break;
                        }
                    }
                    $rst[$sub['name']] = $mutaionFileds;break;
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
    private function _schemaBasictype(&$results, $node){
        $basicTypes = [
            'float'=>'decimal,float,double.',
            'integer'=>'int,tinyint,smallint,mediumint,bigint',
            'date'=>'timestamp,date,datetime,time,year',
            'enum'=>'enum',
            'string'=>'string',
        ];

        foreach ($basicTypes as $basicType=>$desc){
            $rst = [];
            foreach ($node['sub'] as $sub) {
                $subName = strtoupper($sub['name']);
                switch ($subName) {
                    case 'KIND': $rst[$sub['name']] = 'SCALAR'; break;
                    case 'NAME': $rst[$sub['name']] = $basicType; break;
                    case 'DESCRIPTION': $rst[$sub['name']] = $desc; break;
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
    private function schemaTypes($node) {
        if (!$node || !@$node['sub']) return [];
        $models = $this->findAllModels();
        if (!$models) return [];
        $results = [];
        $results[] = $this->_schemaQuerytype($models, $node);
        $results[] = $this->_schemaMutaiontype($models, $node);
        $this->_schemaBasictype($results, $node);

        // 每一个model都是types的一级节点
        foreach ($models as $table => $model){
            $modelObject = new $model;
            // 根据scheme请求返回内容
            $modelResult = [];
            foreach ($node['sub'] as $sub){
                // 类型scheme查询基本是已知的
                $subName = strtoupper($sub['name']);
                switch ($subName){
                    case 'NAME': $modelResult[$sub['name']] = $table; break; // 表名作为name
                    case 'KIND': $modelResult[$sub['name']] = 'OBJECT'; break; // Model都是OBJECT
                    case 'DESCRIPTION': $modelResult[$sub['name']] = $modelObject->get_description(); break;
                    case 'FIELDS': $modelResult[$sub['name']] = $this->getModelFields($modelObject, $sub); break;
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
    private function findAllModels(){
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
    private function getModelFields(YZE_Model $model, $node){
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
                    case 'ARGS': $columnResult[$sub['name']] = $this->getModelFieldArgs($columnConfig, $columnName, $sub); break;
                    case 'TYPE': $columnResult[$sub['name']] = $this->getModelFieldType($columnConfig, $columnName, $sub); break;
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
    private function getModelFieldArgs($columnConfig, $columnName, $node){
        return []; //TODO
    }

    /**
     * 获取字段的类型
     * @param $columnName
     * @param $node
     * @return array
     */
    private function getModelFieldType($columnConfig, $columnName, $node){
        if (!$columnName || !$node) return [];
        $typeResult = [];
        foreach ($node['sub'] as $sub){
            // 类型scheme查询基本是已知的
            $subName = strtoupper($sub['name']);
            switch ($subName){
                case 'NAME': $typeResult[$sub['name']] = $columnName; break;
                case 'KIND': $typeResult[$sub['name']] = $columnConfig['type']; break;
                case 'OFTYPE': $typeResult[$sub['name']] = null; break;
            }
        }
        return $typeResult;
    }
}

?>
