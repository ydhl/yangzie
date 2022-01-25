<?php

namespace yangzie;

/**
 * graphql type 内省处理
 */
trait Graphql__Type{

    /**
     * 通过内省查询支持的类型
     * @param $node
     * @return array
     */
    public function __type($node){
        $args = $node['args']; // 查询参数, 目前type查询只支持name参数
//        print_r($node);
        // 确保用户查询的结果中由查询参数名对应的信息，便于和参数进行比对
        // { __type(name:"test") { description } }
        // 查询的字段只有description，但查询的参数名是name（查询的值是test），那么需要在查询的字段中加上name
        $hasArgName = [];
        foreach ($args as $arg) {
            foreach ($node['sub'] as $item){
                if (strtolower($item['name'])==$arg['name']){
                    $hasArgName[$arg['name']] = true;
                    break;
                }
            }
        }

        $addedField = [];

        foreach ($args as $arg) {
            if ( ! @$hasArgName[$arg['name']]){
                $addedField[] = $arg['name'];
                $node['sub'][] = ['name' => $arg['name']];
            }
        }
        $match = function($item, $args) {
            foreach ($args as $arg) {
                if (!preg_match("/\"?".@$item[$arg['name']]."\"?/", @$arg['default'])) {
                    return false;
                }
            }
            return true;
        };

        $models = $this->find_All_Models();
        $allTypes = $this->all_schema_Types($models, $node);
//        print_r(json_decode(json_encode($allTypes), true));
        $searchedTypes = [];
        foreach ($allTypes as $item){
            if ($match($item, $args)){
                // 为了查询而补上的字段删除
                foreach ($addedField as $field){
                    unset($item[$field]);
                }
                $searchedTypes[] = $item;
            }
        }
        return $searchedTypes;
    }

}

?>
