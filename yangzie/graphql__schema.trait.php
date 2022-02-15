<?php

namespace yangzie;

/**
 * graphql schema 内省处理
 */
trait Graphql__Schema
{

    /**
     * @param GraphqlSearchNode $node
     * @return array
     */
    public function __schema(GraphqlSearchNode $node)
    {
        $schemeResult = [];
        $models = $this->find_All_Models();
        foreach ($node->sub as $schemaNode) {
            $schemaName = strtoupper($schemaNode->name);
            switch ($schemaName) {
                case '__TYPENAME':
                    $schemeResult[$schemaNode->name] = "__Schema";
                    break;
                case 'QUERYTYPE':
                    $schemeResult[$schemaNode->name] = $this->schema_Query_type($models, $schemaNode);
                    break;
                case 'SUBSCRIPTIONTYPE':
                    $schemeResult[$schemaNode->name] = $this->schema_Subscription_Type($schemaNode);
                    break;
                case 'MUTATIONTYPE':
                    $schemeResult[$schemaNode->name] = $this->schema_Mutation_Type($models, $schemaNode);
                    break;
                case 'TYPES':
                    $schemeResult[$schemaNode->name] = $this->all_schema_Types($models, $schemaNode);
                    break;
                case 'DIRECTIVES':
                    $schemeResult[$schemaNode->name] = $this->schema_Directives($schemaNode);
                    break;
                case 'DESCRIPTION':
                    $schemeResult[$schemaNode->name] = YZE_APP_NAME . " schema for GraphiQL";
                    break;
            }
        }
        return $schemeResult;
    }

    /**
     * @param array $models
     * @param GraphqlSearchNode $node
     * @return array
     */
    private function schema_Query_type($models, GraphqlSearchNode $node)
    {
        $queryFileds = [];
        $fieldSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'fields');
        $argSearch = GraphqlIntrospection::find_search_node_by_name($fieldSearch->sub, 'args');
//        print_r($argSearch);
        $fieldNames = [];
        foreach ($models as $table => $class) {
            $modelObject = new $class;
            $fieldNames[] = $table;
            $field = new GraphqlField($table,
                new GraphqlType($table,null,  GraphqlType::KIND_OBJECT),
                $modelObject->get_description(),
                $this->get_Model_Args($argSearch));
            $queryFileds[] = $field;
        }


        $field = new GraphqlField("count", new GraphqlType('count',null,  GraphqlType::KIND_OBJECT), "分页数据");
        $queryFileds[] = $field;

        $fieldNames[] = 'count';

        if (count($fieldNames) != count(array_unique($fieldNames))){
            throw new YZE_FatalException(sprintf(__('field 定义重复了请检查: %s')), join(',', $fieldNames));
        }

        $type = new GraphqlType("YangzieQuery","Yangzie Query entry", GraphqlType::KIND_OBJECT, null, $queryFileds);
        $intro = new GraphqlIntrospection($node, $type->get_data());
        return $intro->search();
    }

    /**
     * 返回系统有哪些订阅操作
     * @param $node
     * @return string
     */
    private function schema_Subscription_Type($node)
    {
        return null;
    }

    private function schema_Mutation_Type($models, GraphqlSearchNode $node)
    {
        $queryFileds = [];
        foreach ($this->basic_types() as $type => $info) {
            $field = new GraphqlField(
                'set' . ucfirst(strtolower($type)),
                new GraphqlType($type, null, GraphqlType::KIND_SCALAR),
                'Set the ' . $info['description'] . ' field',
                [new GraphqlInputValue("value", new GraphqlType($type, null, GraphqlType::KIND_SCALAR))]
            );
            $queryFileds[] = $field;
        }

        $type = new GraphqlType("YangzieMutation", "Yangzie Mutation entry", GraphqlType::KIND_OBJECT, null, $queryFileds);
        $intro = new GraphqlIntrospection($node, $type->get_data());
        return $intro->search();
    }

    private function get_model_schema($models, GraphqlSearchNode $node){
        $results = [];
        // 每一个model都是types的一级节点
        foreach ($models as $table => $model) {
            $modelObject = new $model;
            // 根据scheme请求返回内容
            $fields = $this->get_Model_Fields($modelObject);
            $type = new GraphqlType($table, $modelObject->get_description(), GraphqlType::KIND_OBJECT, null, $fields);
            $intro = new GraphqlIntrospection($node, $type->get_data());
            $results[] = $intro->search();

        }
        return $results;
    }
    /**
     * 返回系统有哪些查询类型（也就是Model）,默认情况下每个Modal的cloumn都将返回，表示都可以被graphql查询
     * @param GraphqlSearchNode $node
     * @return array
     */
    private function all_schema_Types($models, GraphqlSearchNode $node)
    {
        if (!$node->has_value()) return [];
        $results = [];
        $results[] = $this->schema_Query_type($models, $node);
        $results[] = $this->schema_Mutation_Type($models, $node);
//        $results[] = $this->schema_Subscription_Type($node);
        $results = array_merge($results, $this->_schema_Basic_type($node));


        // 类型系统的基础类型
        $schema = new GraphqlIntrospection_Schema($node, []);
        $results[] = $schema->search();
        $type = new GraphqlIntrospection_Type($node, []);
        $results[] = $type->search();
        $typekind = new GraphqlIntrospection_Typekind($node, []);
        $results[] = $typekind->search();
        $field = new GraphqlIntrospection_Field($node, []);
        $results[] = $field->search();
        $inputvalue = new GraphqlIntrospection_Inputvalue($node, []);
        $results[] = $inputvalue->search();
        $enumValue = new GraphqlIntrospection_EnumValue($node, []);
        $results[] = $enumValue->search();
        $directive = new GraphqlIntrospection_Directive($node, []);
        $results[] = $directive->search();
        $directiveLocation = new GraphqlIntrospection_DirectiveLocation($node, []);
        $results[] = $directiveLocation->search();

        // model中的enum 类型
        foreach ($models as $table => $model) {
            $modelObject = new $model;
            $columns = $modelObject->get_columns();
            foreach ($columns as $columnName => $columnConfig) {
                if ($columnConfig['type'] != 'enum') {
                    continue;
                }
                $enumTypeName = $model::TABLE . '_' . $columnName;
                $fieldSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'fields');
                $enumValues = $this->get_Model_Enum($modelObject, $fieldSearch, $columnName);
                $type = new GraphqlType($enumTypeName, $modelObject->get_column_mean($columnName), GraphqlType::KIND_ENUM);
                $type->enumValues = $enumValues;
                $intro = new GraphqlIntrospection($node, $type->get_data());
                $results[] = $intro->search();
            }
        }

        $results = array_merge($results, $this->get_model_schema($models, $node));

        // model的查询参数类型
        // 根据scheme请求返回内容
        $type = new GraphqlType("count", "查询分页数据");
        $queryFileds = [];
        foreach ($models as $table => $class) {
            $field = new GraphqlField($table,
                new GraphqlType('Int',null, GraphqlType::KIND_SCALAR)
                , sprintf(__("%s count"), $table)
            );
            $queryFileds[] = $field;
        }
        $type->fields = $queryFileds;
        $intro = new GraphqlIntrospection($node, $type->get_data());
        $results[] = $intro->search();

        $type = new GraphqlType("Where", "model的查询条件", GraphqlType::KIND_INPUT_OBJECT);
        $type->inputFields = $this->get_Model_Where_Fields();
        $intro = new GraphqlIntrospection($node, $type->get_data());
        $results[] = $intro->search();

        $type = new GraphqlType("DQL", __("分页，分组和排序"), GraphqlType::KIND_INPUT_OBJECT);
        $type->inputFields = $this->get_Model_Dql_Fields();
        $intro = new GraphqlIntrospection($node, $type->get_data());
        $results[] = $intro->search();

        return $results;
    }

    /**
     * 基础数据类型
     */
    private function _schema_Basic_type(GraphqlSearchNode $node)
    {
        $basicTypes = $this->basic_types();
        $rst = [];

        foreach ($basicTypes as $basicType => $info) {
            $type = new GraphqlType($basicType, $info['description'], GraphqlType::KIND_SCALAR);
            $intro = new GraphqlIntrospection($node, $type->get_data());
            $rst[] = $intro->search();
        }
        return $rst;
    }

    private function get_Model_Enum(YZE_Model $model, GraphqlSearchNode $node, $columnName)
    {
        if (!$model || !$node->has_value()) return [];
        $result = [];
        $method = "get_{$columnName}";
        if (!method_exists($model, $method)) return [];
        foreach ($model->$method() as $enum) {
            $result[] = new GraphqlEnumValue($enum);
        }
        return $result;
    }

    /**
     * 根据scheme查询返回model需要返回的字段信息，包含自定义的field
     *
     * @param YZE_Model $model
     * @param GraphqlSearchNode $node 查询结构体
     * @return []
     */
    private function get_Model_Fields(YZE_Model $model)
    {
        $columns = $model->get_columns();
        $result = [];

        foreach ($columns as $columnName => $columnConfig) {
            $field = new GraphqlField($columnName,
                $this->get_Model_Field_Type($model, $columnConfig, $columnName),
                $model->get_column_mean($columnName)
            );
            $result[] = $field;
        }

        if (method_exists($model, "custom_graphql_fields")){
            foreach ($model->custom_graphql_fields() as $custom_field){
                $result[] = $custom_field;
            }
        }

        // 如果有关联表，则关联表也作为field
        $unique_keys = $model->get_relation_columns();
        foreach ($unique_keys as $column => $relationInfo){
            $assoName = $relationInfo['graphql_field'];
            $modelClass = $relationInfo['target_class'];
            if (!class_exists($modelClass))continue;
            $field = new GraphqlField($assoName, new GraphqlType($modelClass::TABLE, null, GraphqlType::KIND_OBJECT), $column." field"
            );
            $result[] = $field;
        }

        return $result;
    }
    private function get_Model_Where_Fields()
    {
        $result = [];
        $typeIntro = new GraphqlType(null,null,GraphqlType::KIND_NON_NULL, new GraphqlType('String',null,GraphqlType::KIND_SCALAR));
        $nullIntro = new GraphqlType("String",null, GraphqlType::KIND_SCALAR);
        $listTypeIntro = new GraphqlType(null,null,GraphqlType::KIND_LIST, new GraphqlType('String',null,GraphqlType::KIND_SCALAR));
        $result[] = new GraphqlInputValue("column", $typeIntro, __("查询字段名"));
        $result[] = new GraphqlInputValue("op", $typeIntro, __("比较条件"));
        $result[] = new GraphqlInputValue("value", $listTypeIntro, __("查询值"));
        $result[] = new GraphqlInputValue("andor", $nullIntro, __("And / Or 拼接下一个where"));
        return $result;
    }

    private function get_Model_Dql_Fields()
    {
        $result = [];
        $typeIntro = new GraphqlType("String", null, GraphqlType::KIND_SCALAR);
        $numberTypeIntro = new GraphqlType("Int", null, GraphqlType::KIND_SCALAR);
        $result[] = new GraphqlInputValue("orderBy", $typeIntro, __("排序"));
        $result[] = new GraphqlInputValue("sort", $typeIntro, __("ASC / DESC"));
        $result[] = new GraphqlInputValue("groupBy", $typeIntro, __("分组"));
        $result[] = new GraphqlInputValue("page", $numberTypeIntro, __("当前页"), "1");
        $result[] = new GraphqlInputValue("count", $numberTypeIntro, __("每页大小"), "10");
        return $result;
    }

    /**
     * 获取查询某个字段的查询条件
     * @param $columnName
     * @param GraphqlSearchNode $node
     */
    private function get_Model_Args(GraphqlSearchNode $node)
    {
        if (!$node->has_value()) return [];
        return [
            new GraphqlInputValue("id", new GraphqlType("ID",null, GraphqlType::KIND_SCALAR),__("主键查询, 当传入时忽略 wheres 参数")),
            new GraphqlInputValue("wheres", new GraphqlType(null,null, GraphqlType::KIND_LIST, new GraphqlType('Where',null,  GraphqlType::KIND_OBJECT)), __("查询条件数组")),
            new GraphqlInputValue("dql", new GraphqlType('DQL',null, GraphqlType::KIND_OBJECT), __("分支、分页、排序")),
        ];
    }

    /**
     * 获取字段的类型
     * @param $columnName
     * @param GraphqlSearchNode $node
     * @return GraphqlType
     */
    private function get_Model_Field_Type(YZE_Model $model, $columnConfig, $columnName)
    {
        $map = ['integer' => 'Int', 'date' => 'Date', 'string' => 'String', 'float' => 'Float'];
        return new GraphqlType(
            $columnConfig['type'] == 'enum' ? $model::TABLE . '_' . $columnName : $map[$columnConfig['type']],
            null,
            $columnConfig['type'] == 'enum' ? 'ENUM' : 'SCALAR');
    }

    /**
     * 返回系统有哪些指令类型
     * @param $node
     * @return string
     */
    private function schema_Directives($node)
    {
        $booleanType = new GraphqlType("Boolean", null, GraphqlType::KIND_SCALAR);
        $stringType = new GraphqlType("String", null, GraphqlType::KIND_SCALAR);
        $intType = new GraphqlType("Int", null, GraphqlType::KIND_SCALAR);
        $nonNullBooleanType = new GraphqlType(null, null, GraphqlType::KIND_NON_NULL,$booleanType);
        $nonNullStringType = new GraphqlType(null, null, GraphqlType::KIND_NON_NULL,$stringType);
        $directives = [
            new GraphqlDirective("include",
                __("Directs the executor to include this field or fragment only when the `if` argument is true."),
                [
                    new GraphqlInputValue("if", $nonNullBooleanType, __("Included when true."))
                ],
                [
                    "FIELD",
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ]
            ),
            new GraphqlDirective("skip",
                __("Directs the executor to skip this field or fragment when the `if` argument is true."),
                [
                    new GraphqlInputValue("if",$nonNullBooleanType, __("Skipped when true."))
                ],
                [
                    "FIELD",
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ]
            ),

            new GraphqlDirective("defer",
                __("Directs the executor to defer this fragment when the `if` argument is true or undefined."),
                [
                    new GraphqlInputValue("if",$booleanType, __("Deferred when true or undefined.")),
                    new GraphqlInputValue("label",$stringType, __("Unique name."))
                ],
                [
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ]
            ),
            new GraphqlDirective("stream",
                __("Directs the executor to stream plural fields when the `if` argument is true or undefined."),
                [
                    new GraphqlInputValue("if",$booleanType, __("Stream when true or undefined.")),
                    new GraphqlInputValue("label",$stringType, __("Unique name.")),
                    new GraphqlInputValue("initialCount",$intType, __("Number of items to return immediately."))
                ],
                [
                    "FIELD"
                ]
            ),

            new GraphqlDirective("deprecated",
                __("Marks an element of a GraphQL schema as no longer supported."),
                [
                    new GraphqlInputValue("reason",$stringType, __("Explains why this element was deprecated, usually also including a suggestion for how to access supported similar data. Formatted using the Markdown syntax, as specified by [CommonMark](https=>//commonmark.org/).",'"No longer supported"')),
                ],
                [
                    "FIELD_DEFINITION",
                    "ARGUMENT_DEFINITION",
                    "INPUT_FIELD_DEFINITION",
                    "ENUM_VALUE"
                ]
            ),
            new GraphqlDirective("specifiedBy",
                __("Exposes a URL that specifies the behaviour of this scalar."),
                [
                    new GraphqlInputValue("url",$nonNullStringType, __("The URL that specifies the behaviour of this scalar.")),
                ],
                [
                    "SCALAR"
                ]
            )
        ];
        $results = [];
        foreach ($directives as $directive){
            $intro = new GraphqlIntrospection($node, $directive->get_data());
            $results[] = $intro->search();
        }
        return $results;
    }
}

?>
