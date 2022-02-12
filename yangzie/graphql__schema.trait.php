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
                $models[$modelObject::TABLE] = $modelObject::CLASS_NAME;
            }
        }
        return $models;
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
        $typeSearch = GraphqlIntrospection::find_search_node_by_name($fieldSearch->sub, 'type');
//        print_r($argSearch);
        $fieldNames = [];
        foreach ($models as $table => $class) {
            $typeIntro = new GraphqlIntrospection($typeSearch, ['kind' => 'OBJECT', 'ofType' => null, 'name' => $table]);
            $modelObject = new $class;
            $fieldNames[] = $table;
            $queryFileds[] = [
                'name' => $table,
                '__typename'=>'__Field',
                'description' => $modelObject->get_description(),
                'type' => $typeIntro->search(),
                'isDeprecated' => false,
                'deprecationReason' => null,
                'args' => $this->get_Model_Args($argSearch)
            ];
        }

        $typeIntro = new GraphqlIntrospection($typeSearch, ['kind' => 'OBJECT', 'ofType' => null, 'name' => 'count']);
        $queryFileds[] = [
            'name' => 'count',
            '__typename'=>'__Field',
            'description' => '分页数据',
            'type' => $typeIntro->search(),
            'isDeprecated' => false,
            'deprecationReason' => null,
            'args' => []
        ];

        $fieldNames[] = 'count';

        if (count($fieldNames) != count(array_unique($fieldNames))){
            throw new YZE_FatalException(sprintf(__('field 定义重复了请检查: %s')), join(',', $fieldNames));
        }

        $intro = new GraphqlIntrospection($node, [
            'name' => 'YangzieQuery',
            'kind' => 'OBJECT',
            '__typename'=>'__Type',
            'description' => 'Yangzie Query entry',
            'fields' => $queryFileds,
            'inputFields' => null,
            'interfaces' => [],
            'enumValues' => null,
            'possibleTypes' => null,
            'specifiedByUrl' => ''
        ]);
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
            $queryFileds[] = [
                'name' => 'set' . ucfirst(strtolower($type)),
                'description' => 'Set the ' . $info['description'] . ' field',
                '__typename'=>'__Field',
                'args' => [[
                    "name" => "value",
                    "description" => null,
                    "__typename"=>"__InputValue",
                    "type" => [
                        "__typename"=>"__Type",
                        "kind" => "SCALAR",
                        "name" => $type,
                        "ofType" => null
                    ],
                    "defaultValue" => null,
                    "isDeprecated" => false,
                    "deprecationReason" => null
                ]],
                'type' => [
                    'kind' => 'SCALAR',
                    "__typename"=>"__Type",
                    'ofType' => null,
                    'name' => $type
                ]
            ];
        }

        $intro = new GraphqlIntrospection($node, [
            'name' => 'YangzieMutation',
            'kind' => 'OBJECT',
            '__typename'=>'__Field',
            'description' => 'Yangzie Mutation entry',
            'fields' => $queryFileds,
            'inputFields' => null,
            'interfaces' => [],
            'enumValues' => null,
            'possibleTypes' => null,
            'specifiedByUrl' => ''
        ]);
        return $intro->search();
    }

    private function get_model_schema($models, GraphqlSearchNode $node){
        $results = [];
        // 每一个model都是types的一级节点
        foreach ($models as $table => $model) {
            $modelObject = new $model;
            // 根据scheme请求返回内容
            $fieldSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'fields');
            $fields = $this->get_Model_Fields($modelObject, $fieldSearch);
            $intro = new GraphqlIntrospection($node, [
                'name' => $table,
                'kind' => 'OBJECT',
                '__typename' => '__Type',
                'description' => $modelObject->get_description(),
                'fields' => $fields,
                'inputFields' => null,
                'interfaces' => [],
                'enumValues' => null,
                'possibleTypes' => null,
                'specifiedByUrl' => ''
            ]);
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
                $intro = new GraphqlIntrospection($node, [
                    'name' => $enumTypeName,
                    'kind' => 'ENUM',
                    'description' => $modelObject->get_column_mean($columnName),
                    'fields' => null,
                    '__typename' => '__Type',
                    'inputFields' => null,
                    'interfaces' => [],
                    'enumValues' => $enumValues,
                    'possibleTypes' => null,
                    'specifiedByUrl' => ''
                ]);
                $results[] = $intro->search();
            }
        }

        $results = array_merge($results, $this->get_model_schema($models, $node));

        // model的查询参数类型
        // 根据scheme请求返回内容
        $inputFieldSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'inputFields');
        $fieldSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'fields');
        $intro = new GraphqlIntrospection($node, [
            'name' => 'count',
            'kind' => 'OBJECT',
            '__typename' => '__Type',
            'description' => "查询分页数据",
            'fields' => $this->get_count_Fields($models, $fieldSearch),
            'inputFields' => null,
            'interfaces' => [],
            'enumValues' => null,
            'possibleTypes' => null,
            'specifiedByUrl' => ''
        ]);
        $results[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'Where',
            'kind' => 'INPUT_OBJECT',
            '__typename' => '__Type',
            'description' => "model的查询条件",
            'fields' => null,
            'inputFields' => $this->get_Model_Where_Fields($inputFieldSearch),
            'interfaces' => [],
            'enumValues' => null,
            'possibleTypes' => null,
            'specifiedByUrl' => ''
        ]);
        $results[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'DQL',
            'kind' => 'INPUT_OBJECT',
            '__typename' => '__Type',
            'description' => "分页，分组和排序",
            'fields' => null,
            'inputFields' => $this->get_Model_Dql_Fields($inputFieldSearch),
            'interfaces' => [],
            'enumValues' => null,
            'possibleTypes' => null,
            'specifiedByUrl' => ''
        ]);
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
            $intro = new GraphqlIntrospection($node, [
                'name' => $basicType,
                'kind' => 'SCALAR',
                '__typename'=>'__Type',
                'description' => $info['description'],
                'fields' => null,
                'inputFields' => null,
                'interfaces' => null,
                'enumValues' => null,
                'possibleTypes' => null,
                'specifiedByUrl' => ''
            ]);
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
            $intro = new GraphqlIntrospection($node, [
                'name' => $enum,
                'description' => '',
                '__typename' => '__EnumValue',
                'isDeprecated' => false,
                'deprecationReason' => null,
            ]);
            $result[] = $intro->search();
        }
        return $result;
    }

    /**
     * 根据scheme查询返回model需要返回的字段信息
     *
     * @param YZE_Model $model
     * @param GraphqlSearchNode $node 查询结构体
     * @return []
     */
    private function get_Model_Fields(YZE_Model $model, GraphqlSearchNode $node)
    {
        if (!$model || !$node->has_value()) return [];
        $columns = $model->get_columns();
        $result = [];
        $typeSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'type');

        foreach ($columns as $columnName => $columnConfig) {
            $intro = new GraphqlIntrospection($node, [
                'name' => $columnName,
                'description' => $model->get_column_mean($columnName),
                "__typename"=>"__Field",
                'args' => [],
                'type' => $this->get_Model_Field_Type($model, $columnConfig, $columnName, $typeSearch),
                'isDeprecated' => false,
                'deprecationReason' => null,
            ]);
            $result[] = $intro->search();
        }

        $result[] = $intro->search();
        // 如果有关联表，则关联表也作为field
        $unique_keys = $model->get_relation_columns();
        foreach ($unique_keys as $column => $relationInfo){
            $assoName = $relationInfo['graphql_field'];
            $modelClass = $relationInfo['target_class'];
            if (!class_exists($modelClass))continue;
            $intro = new GraphqlIntrospection($node, [
                'name' => $assoName,
                'description' => '',
                "__typename"=>"__Field",
                'args' => [],
                'type' => [
                    'name' => $modelClass::TABLE,
                    'kind' => 'OBJECT',
                    "__typename"=>"__Type",
                    'ofType' => null
                ],
                'isDeprecated' => false,
                'deprecationReason' => null,
            ]);
            $result[] = $intro->search();
        }

        return $result;
    }
    private function get_count_Fields($models, GraphqlSearchNode $node){
        $queryFileds = [];
        $typeSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'type');

        foreach ($models as $table => $class) {
            $typeIntro = new GraphqlIntrospection($typeSearch, ['kind' => 'SCALAR', 'ofType' => null, 'name' => 'Int']);
            $queryFileds[] = [
                'name' => $table,
                '__typename'=>'__Field',
                'description' => sprintf(__("%s count"), $table),
                'type' => $typeIntro->search(),
                'isDeprecated' => false,
                'deprecationReason' => null,
                'args' => []
            ];
        }

        return $queryFileds;
    }
    private function get_Model_Where_Fields(GraphqlSearchNode $node)
    {
        if (!$node->has_value()) return [];
        $result = [];
        $typeSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'type');
        $typeIntro = new GraphqlIntrospection($typeSearch, [
            "kind" => "NON_NULL",
            "__typename"=>"__Type",
            "name" => null,
            "ofType" => [
                "kind" => "SCALAR",
                "__typename"=>"__Type",
                "name" => "String",
                "ofType" => null
            ]
        ]);
        $nullIntro = new GraphqlIntrospection($typeSearch, [
            "kind" => "SCALAR",
            "__typename"=>"__Type",
            "name" => "String",
            "ofType" => null
        ]);
        $listTypeIntro = new GraphqlIntrospection($typeSearch, [
            "kind" => "LIST",
            "__typename"=>"__Type",
            "name" => null,
            "ofType" => [
                "kind" => "SCALAR",
                "__typename"=>"__Type",
                "name" => 'String',
                "ofType" => null
            ]
        ]);

        $intro = new GraphqlIntrospection($node, [
            'name' => 'column',
            'description' => __("查询字段名"),
            "__typename"=>"__InputValue",
            'type' => $typeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'op',
            'description' => __("比较条件"),
            "__typename"=>"__InputValue",
            'type' => $typeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'value',
            'description' => __("查询值"),
            "__typename"=>"__InputValue",
            'type' => $listTypeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'andor',
            'description' => __("And / Or 拼接下一个where"),
            "__typename"=>"__InputValue",
            'type' => $nullIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();
        return $result;
    }

    private function get_Model_Dql_Fields(GraphqlSearchNode $node)
    {
        if (!$node->has_value()) return [];
        $result = [];
        $typeSearch = GraphqlIntrospection::find_search_node_by_name($node->sub, 'type');
        $typeIntro = new GraphqlIntrospection($typeSearch, [
            "kind" => "NON_NULL",
            "__typename"=>"__Type",
            "name" => null,
            "ofType" => [
                "kind" => "SCALAR",
                "__typename"=>"__Type",
                "name" => "String",
                "ofType" => null
            ]
        ]);
        $numberTypeIntro = new GraphqlIntrospection($typeSearch, [
            "kind" => "SCALAR",
            "__typename"=>"__Type",
            "name" => 'Int',
            "ofType" => null
        ]);
        $intro = new GraphqlIntrospection($node, [
            'name' => 'having',
            'description' => __("Having"),
            "__typename"=>"__InputValue",
            'type' => $typeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();
        $intro = new GraphqlIntrospection($node, [
            'name' => 'orderBy',
            'description' => __("排序"),
            "__typename"=>"__InputValue",
            'type' => $typeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();
        $intro = new GraphqlIntrospection($node, [
            'name' => 'groupBy',
            'description' => __("分组"),
            "__typename"=>"__InputValue",
            'type' => $typeIntro->search(),
            'defaultValue' => null,
        ]);
        $result[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'page',
            'description' => __("当前页"),
            "__typename"=>"__InputValue",
            'type' => $numberTypeIntro->search(),
            'defaultValue' => "1",
        ]);
        $result[] = $intro->search();

        $intro = new GraphqlIntrospection($node, [
            'name' => 'count',
            'description' => __("每页大小"),
            "__typename"=>"__InputValue",
            'type' => $numberTypeIntro->search(),
            'defaultValue' => "10",
        ]);
        $result[] = $intro->search();
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
        $args = [
            [
                "name" => "wheres",
                "description" => "查询条件数组",
                "__typename"=>"__InputValue",
                "type" => [
                    "kind" => "LIST",
                    "__typename"=>"__Type",
                    "name" => null,
                    "ofType" => [
                        "kind" => "Object",
                        "__typename"=>"__Type",
                        "name" => 'Where',
                        "ofType" => null
                    ]
                ],
                "defaultValue" => null,
                "isDeprecated" => false,
                "deprecationReason" => null
            ],
            [
                "name" => "dql",
                "description" => "分支、分页、排序",
                "__typename"=>"__InputValue",
                "type" => [
                    "kind" => "OBJECT",
                    "__typename"=>"__Type",
                    "name" => "DQL",
                    "ofType" => null
                ],
                "defaultValue" => null,
                "isDeprecated" => false,
                "deprecationReason" => null
            ]
        ];
//        print_r($node);
        $intro = new GraphqlIntrospectionValues($node, $args);
        return $intro->search() ?: null;
    }

    /**
     * 获取字段的类型
     * @param $columnName
     * @param GraphqlSearchNode $node
     * @return array
     */
    private function get_Model_Field_Type(YZE_Model $model, $columnConfig, $columnName, GraphqlSearchNode $node)
    {
        if (!$columnName || !$node->has_value()) return null;
        $map = ['integer' => 'Int', 'date' => 'Date', 'string' => 'String', 'float' => 'Float'];
        $intro = new GraphqlIntrospection($node, [
            'name' => $columnConfig['type'] == 'enum' ? $model::TABLE . '_' . $columnName : $map[$columnConfig['type']],
            'kind' => $columnConfig['type'] == 'enum' ? 'ENUM' : 'SCALAR',
            "__typename"=>"__Type",
            'ofType' => null
        ]);
        return $intro->search() ?: null;
    }

    /**
     * 返回系统有哪些指令类型
     * @param $node
     * @return string
     */
    private function schema_Directives($node)
    {
        $directives = [
            [
                "name" => "include",
                "description" => "Directs the executor to include this field or fragment only when the `if` argument is true.",
                "locations" => [
                    "FIELD",
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ],
                "__typename"=>"__Type",
                "args" => [
                    [
                        "name" => "if",
                        "description" => "Included when true.",
                        "__typename"=>"__InputValue",
                        "type" => [
                            "kind" => "NON_NULL",
                            "__typename"=>"__Type",
                            "name" => null,
                            "ofType" => [
                                "kind" => "SCALAR",
                                "__typename"=>"__Type",
                                "name" => "Boolean",
                                "ofType" => null
                            ]
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ],
            [
                "name" => "skip",
                "description" => "Directs the executor to skip this field or fragment when the `if` argument is true.",
                "locations" => [
                    "FIELD",
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ],
                "__typename"=>"__TYPE",
                "args" => [
                    [
                        "name" => "if",
                        "description" => "Skipped when true.",
                        "type" => [
                            "__typename"=>"__Type",
                            "kind" => "NON_NULL",
                            "name" => null,
                            "__typename"=>"__Type",
                            "ofType" => [
                                "kind" => "SCALAR",
                                "name" => "Boolean",
                                "ofType" => null
                            ]
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ],
            [
                "name" => "defer",
                "description" => "Directs the executor to defer this fragment when the `if` argument is true or undefined.",
                "locations" => [
                    "FRAGMENT_SPREAD",
                    "INLINE_FRAGMENT"
                ],
                "__typename"=>"__Type",
                "args" => [
                    [
                        "name" => "if",
                        "description" => "Deferred when true or undefined.",
                        "type" => [
                            "__typename"=>"__Type",
                            "kind" => "SCALAR",
                            "name" => "Boolean",
                            "ofType" => null
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ],
                    [
                        "name" => "label",
                        "description" => "Unique name",
                        "type" => [
                            "__typename"=>"__Type",
                            "kind" => "SCALAR",
                            "name" => "String",
                            "ofType" => null
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ],
            [
                "name" => "stream",
                "description" => "Directs the executor to stream plural fields when the `if` argument is true or undefined.",
                "locations" => [
                    "FIELD"
                ],
                "__typename"=>"__Type",
                "args" => [
                    [
                        "name" => "if",
                        "description" => "Stream when true or undefined.",
                        "type" => [
                            "kind" => "SCALAR",
                            "__typename"=>"__Type",
                            "name" => "Boolean",
                            "ofType" => null
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ],
                    [
                        "name" => "label",
                        "description" => "Unique name",
                        "type" => [
                            "kind" => "SCALAR",
                            "name" => "String",
                            "__typename"=>"__Type",
                            "ofType" => null
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ],
                    [
                        "name" => "initialCount",
                        "description" => "Number of items to return immediately",
                        "type" => [
                            "kind" => "SCALAR",
                            "__typename"=>"__Type",
                            "name" => "Int",
                            "ofType" => null
                        ],
                        "defaultValue" => "0",
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ],
            [
                "name" => "deprecated",
                "description" => "Marks an element of a GraphQL schema as no longer supported.",
                "locations" => [
                    "FIELD_DEFINITION",
                    "ARGUMENT_DEFINITION",
                    "INPUT_FIELD_DEFINITION",
                    "ENUM_VALUE"
                ],
                "__typename"=>"__Type",
                "args" => [
                    [
                        "name" => "reason",
                        "description" => "Explains why this element was deprecated, usually also including a suggestion for how to access supported similar data. Formatted using the Markdown syntax, as specified by [CommonMark](https=>//commonmark.org/).",
                        "type" => [
                            "kind" => "SCALAR",
                            "name" => "String",
                            "__typename"=>"__Type",
                            "ofType" => null
                        ],
                        "defaultValue" => '"No longer supported"',
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ],
            [
                "name" => "specifiedBy",
                "description" => "Exposes a URL that specifies the behaviour of this scalar.",
                "locations" => [
                    "SCALAR"
                ],
                "__typename"=>"__Type",
                "args" => [
                    [
                        "name" => "url",
                        "description" => "The URL that specifies the behaviour of this scalar.",
                        "type" => [
                            "kind" => "NON_NULL",
                            "__typename"=>"__Type",
                            "name" => null,
                            "ofType" => [
                                "__typename"=>"__Type",
                                "kind" => "SCALAR",
                                "name" => "String",
                                "ofType" => null
                            ]
                        ],
                        "defaultValue" => null,
                        "isDeprecated" => false,
                        "deprecationReason" => null
                    ]
                ]
            ]
        ];
        $results = [];
        foreach ($directives as $directive){
            $intro = new GraphqlIntrospection($node, $directive);
            $results[] = $intro->search();
        }
        return $results;
    }
}

?>
