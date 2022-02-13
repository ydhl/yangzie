<?php

namespace yangzie;
class GraphqlSearchArg{
    /**
     * @var string 参数名
     */
    public $name;
    /**
     * @var 默认参数值
     */
    public $defaultValue;
    /**
     * @var 参数值
     */
    public $value;
    /**
     * @var 传入的值是不是变量
     */
    public $valueIsVar;
}
class GraphqlSearchNode{
    /**
     * @var string 查询内容
     */
    public $name;

    /**
     * @var array<GraphqlSearchArg>
     */
    public $args;
    /**
     * @var 别名
     */
    public $alias;
    /**
     * @var array<GraphqlSearchNode>
     */
    public $sub;
    public function has_value(){
        return $this->name;
    }
}
class GraphqlIntrospection{
    protected $_searchNode;
    protected $_valueInfo;

    /**
     * 根据指定的name 在指定的searchNodes数据中 查询对应的searchNode
     * @param array<GraphqlSearchNode> | null $searchNode
     * @param string $name
     * @return GraphqlSearchNode
     */
    public static function find_search_node_by_name($searchNodes, string $name){
        if (!$searchNodes || !is_array($searchNodes)) return new GraphqlSearchNode();
        foreach ($searchNodes as $item){
            if ($item->name == $name) return $item;
        }
        return new GraphqlSearchNode();
    }
    /**
     * @param GraphqlSearchNode $searchNode 查询结构体
     * @param array $valueInfo 要根据查询结构体返回的内容数据, 格式为[NAME=>INFO]
     */
    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $this->_searchNode = $searchNode;
        $this->_valueInfo = $valueInfo;
    }

    /**
     * 根据searchNode（查询请求中解析出的查询部分的内容）查询$valueInfo并返回满足条件的内容
     * @return mixed
     */
    public function search(): array{
        if (!$this->_searchNode || !$this->_searchNode->has_value()) return [];
        $rst = [];
        foreach ((array)$this->_searchNode->sub as $sub) {
            $rst = array_merge($rst, $this->pick($sub, $this->_valueInfo));
        }
        return $rst;
    }

    /**
     * 根据传入的名字返回对应的内容
     * @param GraphqlSearchNode $searchNode 整个查询节点数组, 根据里面的name返回对应的内容, 返回的内容通过$valueInfo指定
     * @return array $valueInfo 格式为[NAME=>INFO]
     */
    public function pick(GraphqlSearchNode $searchNode, array $valueInfo): array{
        $rst = [];
        $queryName = $searchNode->name;
        $value = @$valueInfo[$queryName];

        if (is_array($value) && $value){ // 下面还有内容
            if (is_array(reset($value))){// 值是数组构成的数组
                $intro = new GraphqlIntrospectionValues($searchNode, $value);
            }else{
                $intro = new GraphqlIntrospection($searchNode, $value);
            }
            $rst[$queryName] = $intro->search();
        }else{
            $rst[$queryName] = $value;
        }
        return $rst;
    }
}
class GraphqlIntrospectionValues extends GraphqlIntrospection {
    public function search(): array
    {
        $rsts = [];
        foreach ($this->_valueInfo as $value){
            $rst = [];
            foreach ((array)@$this->_searchNode->sub as $sub) {
                $rst = array_merge($rst, $this->pick($sub, $value));
            }
            $rsts[] = $rst;
        }
        return $rsts;
    }
}

/**
 * 封装graphql内省请求中，返回__field的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Field extends GraphqlIntrospection {
    private  $_fields = [
        [
            'name'=>'name',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                '__typename'=>'__Type',
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    '__typename'=>'__Type',
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    "ofType"=> null
                ]
            ]
        ],
        [
            'name'=>'description',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            '__typename'=>'__Field',
            "type"=> [
                '__typename'=>'__Type',
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
            ]
        ],
        [
            'name'=>'args',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            '__typename'=>'__Field',
            "args"=> [
                [
                    "name"=> "includeDeprecated",
                    '__typename'=>'__InputValue',
                    "type"=> [
                        '__typename'=>'__Type',
                        "kind"=> "SCALAR",
                        "name"=> "Boolean",
                        "ofType"=> null
                    ],
                    "defaultValue"=> "false",
                    "isDeprecated"=> false,
                    "deprecationReason"=> null
                ]
            ],
            "type"=> [
                "kind"=> "NON_NULL",
                '__typename'=>'__Type',
                "name"=> null,
                "ofType"=> [
                    '__typename'=>'__Type',
                    "kind"=> "LIST",
                    "name"=> null,
                    "ofType"=> [
                        '__typename'=>'__Type',
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        "ofType"=> [
                            '__typename'=>'__Type',
                            "kind"=> "OBJECT",
                            "name"=> "__InputValue",
                            "ofType"=> null
                        ]
                    ]
                ]
            ]
        ],
        [
            'name'=>'type',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            '__typename'=>'__Field',
            "args"=>[],
            "type"=> [
                '__typename'=>'__Type',
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    '__typename'=>'__Type',
                    "kind"=> "OBJECT",
                    "name"=> "__Type",
                    "ofType"=> null
                ]
            ]
        ],
        [
            'name'=>'isDeprecated',
            "isDeprecated"=>false,
            '__typename'=>'__Field',
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                '__typename'=>'__Type',
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    '__typename'=>'__Type',
                    "kind"=> "SCALAR",
                    "name"=> "Boolean",
                    "ofType"=> null
                ]
            ]
        ],
        [
            'name'=>'deprecationReason',
            "isDeprecated"=>false,
            '__typename'=>'__Field',
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                '__typename'=>'__Type',
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
            ]
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields'), $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__Field',
            'description'=>"Object and Interface types are described by a list of Fields, each of which has a name, potentially a list of arguments, and a return type.",
            'fields'=>$field->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__schema的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Schema extends GraphqlIntrospection {
    private  $_fields = [
        ['name'=>'description','__typename'=>'__Field','description'=>'','args'=>null,'type'=>['kind'=>'SCALAR','name'=>'String','__typename'=>'__Type','ofType'=>null], "isDeprecated"=> false, "deprecationReason"=> null],
        ['name'=>'types','__typename'=>'__Field','args'=>null,'description'=>'A list of all types supported by this server.','type'=>[
            'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                        'kind'=>'OBJECT','name'=>'__Type','__typename'=>'__Type','ofType'=>null
                    ]
                ]
            ]
        ], "isDeprecated"=> false, "deprecationReason"=> null],
        ['name'=>'queryType','__typename'=>'__Field','args'=>null,'description'=>'The type that query operations will be rooted at.','type'=>['kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>['kind'=>'OBJECT','name'=>'__Type','__typename'=>'__Type','ofType'=>null]], "isDeprecated"=> false, "deprecationReason"=> null],
        ['name'=>'mutationType','__typename'=>'__Field','args'=>null,'description'=>'If this server supports mutation, the type that mutation operations will be rooted at.','type'=>['kind'=>'OBJECT','name'=>'__Type','__typename'=>'__Type','ofType'=>null], "isDeprecated"=> false, "deprecationReason"=> null],
        ['name'=>'subscriptionType','__typename'=>'__Field','args'=>null,'description'=>'If this server support subscription, the type that subscription operations will be rooted at.','type'=>['kind'=>'OBJECT','name'=>'__Type','__typename'=>'__Type','ofType'=>null], "isDeprecated"=> false, "deprecationReason"=> null],
        ['name'=>'directives','__typename'=>'__Field','args'=>null,'description'=>'A list of all directives supported by this server.','type'=>[
            'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                        'kind'=>'OBJECT','name'=>'__Directive','__typename'=>'__Type','ofType'=>null
                    ]
                ]
            ]
        ], "isDeprecated"=> false, "deprecationReason"=> null]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $fieldNode = GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields');
        $intro = new GraphqlIntrospectionValues($fieldNode, $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__Schema',
            '__typename'=>'__Schema',
            'description'=>"A GraphQL Schema defines the capabilities of a GraphQL server. It exposes all available types and directives on the server, as well as the entry points for query, mutation, and subscription operations.",
            'fields'=>$intro->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__type的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Type extends GraphqlIntrospection {
    private $_fields =  [
        ['name'=>'kind','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>['kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>['kind'=>'ENUM','name'=>'__TypeKind','__typename'=>'__Type','ofType'=>null]]],
        ['name'=>'name','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>['kind'=>'SCALAR','name'=>'String','__typename'=>'__Type','ofType'=>null]],
        ['name'=>'description','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>['kind'=>'SCALAR','name'=>'String','__typename'=>'__Type','ofType'=>null]],
        ['name'=>'specifiedByURL','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>['kind'=>'SCALAR','name'=>'String','__typename'=>'__Type','ofType'=>null]],
        ['name'=>'fields','__typename'=>'__Field','description'=>'','isDeprecated'=>false, 'deprecationReason'=>null,'args'=>[[
            "name"=> "includeDeprecated",
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "Boolean",
                '__typename'=>'__Field',
                "ofType"=> null
            ],
            "defaultValue"=> "false",
            '__typename'=>'__InputValue',
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ]],'type'=>['kind'=>'LIST','name'=>'','__typename'=>'__Type','ofType'=>['kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>['kind'=>'OBJECT','name'=>'__Field','__typename'=>'__Type','ofType'=>null]]]],
        ['name'=>'interfaces','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>[
            'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'OBJECT','__typename'=>'__Type','name'=>'__Type'
                ]
            ]
        ]],
        ['name'=>'possibleTypes','__typename'=>'__Field','description'=>'','args'=>[],'isDeprecated'=>false, 'deprecationReason'=>null,'type'=>[
            'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'OBJECT','__typename'=>'__Type','name'=>'__Type'
                ]
            ]
        ]],
        ['name'=>'enumValues','__typename'=>'__Field','description'=>'','isDeprecated'=>false, 'deprecationReason'=>null,'args'=>[[
            "name"=> "includeDeprecated",
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "Boolean",
                '__typename'=>'__Type',
                "ofType"=> null
            ],
            "defaultValue"=> "false",
            "isDeprecated"=> false,
            '__typename'=>'__InputValue',
            "deprecationReason"=> null
        ]],'type'=>[
            'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'OBJECT','__typename'=>'__Type','name'=>'__EnumValue'
                ]
            ]
        ]],
        ['name'=>'inputFields','__typename'=>'__Field','description'=>'','isDeprecated'=>false, 'deprecationReason'=>null,'args'=>[[
            "name"=> "includeDeprecated",
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "Boolean",
                '__typename'=>'__Type',
                "ofType"=> null
            ],
            "defaultValue"=> "false",
            "isDeprecated"=> false,
            '__typename'=>'__InputValue',
            "deprecationReason"=> null
        ]],'type'=>[
            'kind'=>'LIST','name'=>null,'__typename'=>'__Type','ofType'=>[
                'kind'=>'NON_NULL','name'=>null,'__typename'=>'__Type','ofType'=>[
                    'kind'=>'OBJECT','__typename'=>'__Type','name'=>'__InputValue'
                ]
            ]
        ]]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields'), $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__Type',
            '__typename'=>'__Type',
            'description'=>"The fundamental unit of any GraphQL Schema is the type. There are many kinds of types in GraphQL as represented by the `__TypeKind` enum.\n\nDepending on the kind of a type, certain fields describe information about that type. Scalar types provide no information beyond a name, description and optional `specifiedByURL`, while Enum types provide their values. Object and Interface types provide the fields they describe. Abstract types, Union and Interface, provide the Object types possible at runtime. List and NonNull types compose other types.",
            'fields'=>$field->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__directive的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Directive extends GraphqlIntrospection {
    private $_fields = [
        [
            "name" => 'name',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                '__typename'=>'__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    '__typename'=>'__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'description',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                '__typename'=>'__Type',
                "ofType"=> null
            ]
        ],
        [
            "name" => 'isRepeatable',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                '__typename'=>'__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "Boolean",
                    '__typename'=>'__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'locations',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                '__typename'=>'__Type',
                "ofType"=> [
                    "kind"=> "LIST",
                    "name"=> null,
                    '__typename'=>'__Type',
                    "ofType"=> [
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        '__typename'=>'__Type',
                        "ofType"=> [
                            "kind"=> "ENUM",
                            '__typename'=>'__Type',
                            "name"=> "__DirectiveLocation",
                            "ofType"=> null
                        ]
                    ]
                ]
            ]
        ],
        [
            "name" => 'args',
            '__typename'=>'__Field',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=> [
                [
                    "name"=> "includeDeprecated",
                    "description"=> null,
                    '__typename'=>'__InputValue',
                    "type"=> [
                        "kind"=> "SCALAR",
                        "name"=> "Boolean",
                        '__typename'=>'__Type',
                        "ofType"=> null
                    ],
                    "defaultValue"=> "false",
                    "isDeprecated"=> false,
                    "deprecationReason"=> null
                ]
            ],
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                '__typename'=>'__Type',
                "ofType"=> [
                    "kind"=> "LIST",
                    "name"=> null,
                    '__typename'=>'__Type',
                    "ofType"=> [
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        '__typename'=>'__Type',
                        "ofType"=> [
                            "kind"=> "OBJECT",
                            "name"=> "__InputValue",
                            '__typename'=>'__Type',
                            "ofType"=> null
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields'), $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__Directive',
            '__typename'=>'__Type',
            'description'=>"A Directive provides a way to describe alternate runtime execution and type validation behavior in a GraphQL document.\n\nIn some cases, you need to provide options to alter GraphQL's execution behavior in ways field arguments will not suffice, such as conditionally including or skipping a field. Directives provide this by describing additional information to the executor.",
            'fields'=>$field->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__directiveLocation的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_DirectiveLocation extends GraphqlIntrospection {
    private $_enumvalues = [
        [
            "name"=> "QUERY",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a query operation.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "MUTATION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a mutation operation.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "SUBSCRIPTION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a subscription operation.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "FIELD",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a field.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "FRAGMENT_DEFINITION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a fragment definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "FRAGMENT_SPREAD",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a fragment spread.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "INLINE_FRAGMENT",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an inline fragment.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "VARIABLE_DEFINITION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a variable definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "SCHEMA",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a schema definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "SCALAR",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a scalar definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "OBJECT",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an object type definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "FIELD_DEFINITION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a field definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "ARGUMENT_DEFINITION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an argument definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "INTERFACE",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an interface definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "UNION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to a union definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "ENUM",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an enum definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "ENUM_VALUE",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an enum value definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "INPUT_OBJECT",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an input object type definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ],
        [
            "name"=> "INPUT_FIELD_DEFINITION",
            "__typename" => '__EnumValue',
            "description"=> "Location adjacent to an input object field definition.",
            "isDeprecated"=> false,
            "deprecationReason"=> null
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'enumValues'), $this->_enumvalues);
        $valueInfo = [
            'kind'=>'ENUM',
            'name'=>'__DirectiveLocation',
            '__typename'=>'__Type',
            'description'=>"A Directive can be adjacent to many parts of the GraphQL language, a __DirectiveLocation describes one such possible adjacencies.",
            'fields'=>null,
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>$field->search(),
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__enumvalue的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_EnumValue extends GraphqlIntrospection {
    private $_fields = [
        [
            "name" => 'name',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "__typename" => '__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    "__typename" => '__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'description',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "__typename" => '__Type',
                "ofType"=> null
            ]
        ],
        [
            "name" => 'isDeprecated',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "__typename" => '__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "Boolean",
                    "__typename" => '__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'deprecationReason',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "__typename" => '__Type',
                "ofType"=> null
            ]
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields'), $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__EnumValue',
            "__typename" => '__Type',
            'description'=>"One possible value for a given Enum. Enum values are unique values, not a placeholder for a string or numeric value. However an Enum value is returned in a JSON response as a string.",
            'fields'=>$field->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__inputValue的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Inputvalue extends GraphqlIntrospection {
    private $_fields = [
        [
            "name" => 'name',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "__typename" => '__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    "__typename" => '__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'description',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "__typename" => '__Type',
                "ofType"=> null
            ]
        ],
        [
            "name" => 'type',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "OBJECT",
                    "name"=> "__Type",
                    "__typename" => '__Type',
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'defaultValue',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "args"=>[],
            "description"=> "A GraphQL-formatted string representing the default value for this input value.",
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "__typename" => '__Type',
                "ofType"=> null
            ]
        ],
        [
            "name" => 'isDeprecated',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "__typename" => '__Type',
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "__typename" => '__Type',
                    "name"=> "Boolean",
                    "ofType"=> null
                ]
            ]
        ],
        [
            "name" => 'deprecationReason',
            "isDeprecated"=>false,
            "deprecationReason"=> null,
            "description"=> null,
            "args"=>[],
            "__typename" => '__Field',
            "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "__typename" => '__Type',
                "ofType"=> null
            ]
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'fields'), $this->_fields);
        $valueInfo = [
            'kind'=>'OBJECT',
            'name'=>'__InputValue',
            "__typename" => '__Type',
            'description'=>"Arguments provided to Fields or Directives and the input fields of an InputObject are represented as Input Values which describe their type and optionally a default value.",
            'fields'=>$field->search(),
            'inputFields'=>null,
            'interfaces'=>[],
            'specifiedByUrl'=>null,
            'enumValues'=>null,
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}
/**
 * 封装graphql内省请求中，返回__typeKind的内容，包含kind, name,description,fields,inputfileds,interfaces,enumvalues,possibleTypes
 */
class GraphqlIntrospection_Typekind extends GraphqlIntrospection {
    private $_enumvalues = [
        [
            "name"=> "SCALAR",
            "description"=> "Indicates this type is a scalar.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "OBJECT",
            "description"=> "Indicates this type is an object. `fields` and `interfaces` are valid fields.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "INTERFACE",
            "description"=> "Indicates this type is an interface. `fields`, `interfaces`, and `possibleTypes` are valid fields.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "UNION",
            "description"=> "Indicates this type is a union. `possibleTypes` is a valid field.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "ENUM",
            "description"=> "Indicates this type is an enum. `enumValues` is a valid field.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "INPUT_OBJECT",
            "description"=> "Indicates this type is an input object. `inputFields` is a valid field.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "LIST",
            "description"=> "Indicates this type is a list. `ofType` is a valid field.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ],
        [
            "name"=> "NON_NULL",
            "description"=> "Indicates this type is a non-null. `ofType` is a valid field.",
            "isDeprecated"=> false,
            "__typename" => '__EnumValue',
            "deprecationReason"=> null
        ]
    ];

    public function __construct(GraphqlSearchNode $searchNode, array $valueInfo)
    {
        $field = new GraphqlIntrospectionValues(GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'enumValues'), $this->_enumvalues);
        $valueInfo = [
            'kind'=>'ENUM',
            'name'=>'__TypeKind',
            'description'=>"An enum describing what kind of type a given `__Type` is.",
            'fields'=>null,
            'inputFields'=>null,
            'interfaces'=>[],
            "__typename" => '__Type',
            'specifiedByUrl'=>null,
            'enumValues'=>$field->search(),
            'possibleTypes'=>null,
        ];
        parent::__construct($searchNode, $valueInfo);
    }
}

class GraphqlResult extends YZE_JSON_View{
    public static function error($controller, $message =null, $code =null, $data=null) {
        return new GraphqlResult($controller,  array (
            'errors' => [$message],
            "data" => $data
        ) );
    }
    public static function success($controller, $data = null) {
        return new GraphqlResult($controller,  array (
            "data" => $data
        ) );
    }
}
/**
 * Graphql处理控制器
 *
 * @category Framework
 * @package Yangzie
 * @author liizii, <libol007@gmail.com>
 * @license http://www.php.net/license/3_01.txt PHP License 3.01
 * @link yangzie.yidianhulian.com
 */
class Graphql_Controller extends YZE_Resource_Controller {
    use Graphql__Schema, Graphql__Type, Graphql__Typename;
    private $operationType = 'query';
    private $operationName;
    /**
     * 变量
     * @var
     */
    private $vars;
    /**
     * 变量的默认值
     * @var array
     */
    private $varDefault;

    private $fetchActRegx = "/:|\{|\}|\(.+\)|\w+|\.{1,3}|\\$|\#[^\\n]*/miu";
    private $allModelTypes;
    public function response_headers(){
        return [
            "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, token, Redirect",
            "Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH",
            "Access-Control-Allow-Origin: *"
            ];
    }
    public function post_index() {
        return $this->index();
    }
    public function index() {
        $this->layout = '';
        try{
            // 1. 线解析graphql成语法结构体
            list($query, $vars) = $this->fetch_Request();
            $this->vars = $vars;

            $nodes = $this->parse($query);
            $result = [];
            $count = [];
            // 2. 对每个结构进行数据查询
            foreach ($nodes as $node) {
                //2.1 内省特殊的查询：如__SCHEMA 向服务端询问有哪些可查询端内容 https://graphql.cn/learn/introspection/
                if (preg_match("/^__/", $node->name, $matches)){
                    $method = $node->name;
                    if(!method_exists($this, $method)){
                        throw new YZE_FatalException('can not query for '.$method.', method not found');
                    }
                    $result[$node->name] = $this->$method($node);
                    continue;
                }

                // 提取形参实参
                $wheres = null;
                $dql = null;
                $id = null;
                foreach ((array)$node->args as $arg){
                    if ($arg->name == 'wheres'){
                        $wheres = $arg->valueIsVar ? @$this->vars[$arg->value] : $arg->value;
                    }else if ($arg->name == 'dql'){
                        $dql = $arg->valueIsVar ? @$this->vars[$arg->value] : $arg->value;
                    }else if ($arg->name == 'id'){
                        $id = $arg->valueIsVar ? @$this->vars[$arg->value] : $arg->value;
                    }
                }
//                print_r($node->args);
                // 2.2 具体数据查询
                if ($node->name != "count"){
                    $total = 0;
                    $result[$node->name] = $this->model_query($node->name, $node, $id, $wheres, $dql, $total);
                    $count[$node->name] = $total;
                }else{
                    $result[$node->name] = [];
                }
            }
            if (isset($result['count'])){
                $result['count'] = $count;
            }
            // 3. 返回结构
            return GraphqlResult::success($this, $result);
        }catch (\Exception $e){
            return GraphqlResult::error($this, $e->getMessage());
        }
    }

    /**
     * 根据请求端方法（post/get）已经传参端方式，获取请求中端数据
     * @return array [0=>查询字符串, 1=>变量字符串, 2=>操作名称字符串]
     */
    private function fetch_Request() {
        $request = $this->request;

        if (strcmp(@$_SERVER['CONTENT_TYPE'], 'application/json') === 0 ){
            $content = json_decode(trim(file_get_contents("php://input")), true);
            return [@$content['query'],@$content['variables'],@$content['operationName']];
        }

        return[
            trim($request->get_from_request('query')),
            trim($request->get_from_request('variables')),
            trim($request->get_from_request('operationName'))
        ];
    }

    /**
     * 解析请求并对field做验证，如果有错误抛出异常。
     * query IntrospectionQuery { __schema { queryType { name } } }返回的结构体格式如下：
     * <pre>
     * [
     *  0=>[
     *   'name'=>'__schema',
     *   'sub'=>[
     *      ... 下面的结构体
     *   ]
     *  ]
     * ]
     * </pre>
     * 每个结构体的格式如下：[name=>名称, sub=>[下面的结构体], args=>[参数结构体]]；
     * 参数结构体的格式如下：[name=>参数名, default=>默认值]
     * @throws YZE_FatalException
     * @return array<GraphqlSearchNode>
     */
    private function parse($query){
        //用正则来分离query里面的结构
        preg_match_all($this->fetchActRegx, $query, $matches);
        //处理query 或者 mutation name

        $acts = $matches[0];
        if (!$acts){
            throw new YZE_FatalException('query is missing');
        }
        //{
        //query {
        //query operationName {
        //query operationName(arg) {
        //mutation {
        //mutation operationName {
        //mutation operationName(arg) {的情况
        if (!strcasecmp('query', $acts[0]) || !strcasecmp('mutation', $acts[0])){
            $this->operationType = $acts[0];
            if ($acts[1]!="{"){
                $this->operationName = $acts[1];
                if ($acts[2]!='{'){
                    $this->parse_var_default($acts[2]);
                    return $this->fetch_Node(array_slice($acts, 4));
                }
                return $this->fetch_Node(array_slice($acts, 3));
            }
            return $this->fetch_Node(array_slice($acts, 2));
        }
        // 直接{开头的情况
        return $this->fetch_Node(array_slice($acts, 1));
    }

    private function parse_var_default($args){
        // 处理默认值
    }

    /**
     * 提取指定的fragment
     * @param $acts
     * @return array<GraphqlSearchNode>
     */
    private function fetch_Fragment($acts, $fragmentName) {
        $fragmentIndex = -1;
        foreach ($acts as $index => $act) {
            if (!strcasecmp('fragment', $acts[$index]) && !strcasecmp($acts[$index+1], $fragmentName)){
                $fragmentIndex = $index;
                break;
            }
        }
        if ($fragmentIndex==-1) return [];

        while (true) {
            if (!strcasecmp('{', $acts[$fragmentIndex])){
                break;
            }
            $fragmentIndex++;
        }
        return $this->fetch_Node(array_slice($acts, $fragmentIndex + 1));
    }

    /**
     * 遍历提取的关键字，然后解析出节点，传入的数据中不需要头的{
     * @param $acts
     * @param int $fetchedLength
     * @return array<GraphqlSearchNode>
     */
    private function fetch_Node ($acts, &$fetchedLength=0) {
        $nodes = [];
        $currNode = new GraphqlSearchNode();
        $index = 0;
        if (!$acts) return $nodes;
        while (true){
            // 解析完了
            if ($index==count($acts)-1) {
                $fetchedLength = $index;
                if ($currNode->name) $nodes[] = $currNode;
                return $nodes;
            }
            $act = $acts[$index++];

            // 遇到}表示当前节点节点解析完了
            if ($act=="}") {
                $fetchedLength = $index;
                if ($currNode->name) $nodes[] = $currNode;
                return $nodes;
            }

            // 开始解析新节点
            if ($act == "{"){
                $subLength = 0;
                $currNode->sub = $this->fetch_Node(array_slice($acts, $index), $subLength);
                $index += $subLength;
                $nodes[] = $currNode;
                $currNode = new GraphqlSearchNode();
                continue;
            }
            //参数处理
            if (substr($act, 0, 1) == "("){
                $arg_acts = $this->parse_args($act);
                $currNode->args = $this->fetch_Args($arg_acts);
                continue;
            }
            // ：别名处理,:后面是别名，index往后移动一位
            if ($act == ":"){
                $currNode->alias = $acts[$index++];
                continue;
            }
            // fragment 处理，后面是fragment，index移动一位
            if ($act == "..."){
                $nodes = array_merge($nodes, $this->fetch_Fragment($acts, $acts[$index++]));
                $currNode = new GraphqlSearchNode();
                continue;
            }
            // 正常节点名称
            if ($currNode->name){
                $nodes[] = $currNode;
                $currNode = new GraphqlSearchNode();
            }
            $currNode->name = $act;
        }
        return $nodes;
    }

    /**
     * 逐个字符遍历，提取参数名和参数值和参数列表的元字符[]{}
     *
     * 测试字符串
     * '(id: "\"{(1000),", a:"(2\\\')", c:1)';
     *
     * '(wheres: [{column: "id:,{()}[]\",", op: "=", value: "\"】28中文\"}"}], id: 2, c: "c", a:$a)'
     *
     * (a:1,b:2)
     *
     * @param $argString
     * @return array
     */
    private function parse_args($argString){
        //
        $acts = [];
        $index = 0;
        $words = [];
        $isHandleValue = false;
        $isInQuote = null;
        while (true){
            if ($index >= mb_strlen($argString)) {
                // 剩余的内容
                if ($words)$acts[] = join('', $words);
                break;
            }
            $c = mb_substr($argString, $index++, 1);

            // case 0 在没有值内容时，遇到的元字符
            if (in_array($c, ['{', '}', '[', ']']) && !$isHandleValue){
                $acts[] = $c;
                continue;
            }

            // case 1 在没有处理值时遇到: 表示参数名结束，如果当前在处理值，那么:是值内容，比如name: "value:"
            if ($c == ":" && !$isHandleValue){//名
                $words[] = $c;
                $acts[] = join('', $words);
                $words = [];

                // 名后面就是值开始
                $isHandleValue = true;

                // 往下推直到值的第一个字符不是空格的字符
                while(true){
                    $c = mb_substr($argString, $index++, 1);
                    if($c!=" ") break;
                }
                if (in_array($c, ["{","["])){
                    $isHandleValue = false; // 下级参数
                    $acts[] = $c;
                }else{
                    if (in_array($c, ["'",'"'])){ // 引号值
                        $isInQuote = $c;
                    }
                    $words[] = $c;
                }
                continue;
            }

            // 检测是否值结束，值的结束以,})
            if ($isHandleValue && in_array($c, [',','}',')'])){
                $isFinishedValue = false;
                if (!$isInQuote){// 非字符串值是没有引号的，那么遇到这些字符就表示结束
                    $isFinishedValue = true;
                }else{// 字符串则判断前一个字符是"，前2个字符不是\则是结束,比如"", "\",",
                    // $prev_c = mb_substr($argString, $index-2, 1);
                    // $prev_2nd_c = mb_substr($argString, $index-3, 1);
                    $prev_c = end($words);
                    $prev_2nd_c = prev($words);
                    reset($words);

                    if ($prev_c && $prev_2nd_c && $prev_c == $isInQuote && $prev_2nd_c != '\\'){
                        $isFinishedValue = true;
                    }
                }
                if ($isFinishedValue){
                    $acts[] = join('', $words);
                    $isHandleValue = false;
                    $isInQuote = null;
                    $words = [];
                    if ($c == "}"){// 由于这时由于isInQuote的影响，没有进入case 0
                        $acts[] = "}";
                    }

                    continue;
                }
            }
            if (($c == "(" || $c == ")") && !$isInQuote){// 忽略(),
                continue;
            }
            if (($c == "," || $c == " ") && !$isHandleValue){// 忽略参数名分隔符，和对应的空格,
                continue;
            }

            $words[] = $c;
        }
        return $acts;
    }

    private function array_key_last($array){
        $keys = array_keys($array);
        return end($keys);
    }

    private function fetch_Args_array ($end, $acts, &$fetchedLength=0) {
        $args = [];
        $index = 0;
        while (true) {
            $act = $acts[$index++];
            // 解析完了
            if ($act == $end) {
                $fetchedLength = $index;
                break;
            }

            // 开始解析下级数组
            if ($act == "{" || $act == "[") {
                $subLength = 0;
                $jsonValue = $this->fetch_Args_array($act == "[" ? "]" : "}",array_slice($acts, $index), $subLength);
                $index += $subLength;
                $key_last = $this->array_key_last($args);
                if ($key_last){
                    $args[$this->array_key_last($args)] = $jsonValue;
                }else{
                    $args[] = $jsonValue;
                }
                continue;
            }
            if (substr($act,-1) == ":"){
                $args[rtrim($act, ":")] = null;
            }else{
                if (substr($act,0,1) == '$'){
                    $args[$this->array_key_last($args)] = $this->vars[substr($act,1)];
                }else{
                    $args[$this->array_key_last($args)] = $act;
                }
            }
        }
        return $args;
    }
    /**
     * 提取查询字符串中的参数部分
     * @param $argString
     * @return array
     */
    private function fetch_Args ($acts) {
        $args = [];
        $currArg = new GraphqlSearchArg();
        $index = 0;
        while (true) {
            // 解析完了
            if ($index >= count($acts)) {
                if ($currArg->name){
                    $args[] = $currArg;
                }
                break;
            }
            $act = $acts[$index++];

            // 开始解析下级数组
            if ($act == "[" || $act == "{") {
                $subLength = 0;
                $jsonValue = $this->fetch_Args_array($act == "[" ? "]" : "}",array_slice($acts, $index), $subLength);
                $index += $subLength;
                $currArg->value = $jsonValue;
                $args[] = $currArg;
                $currArg = new GraphqlSearchArg();
                continue;
            }
            if (!$currArg->name){
                $currArg->name = rtrim($act, ":");
            }else{
                if (substr($act,0,1)=="\$"){
                    $currArg->value = substr($act, 1);
                    $currArg->valueIsVar = true;
                }else{
                    $currArg->value = $act;
                }
                $args[] = $currArg;
                $currArg = new GraphqlSearchArg();
            }
        }
        return $args;
    }

    /**
     * 解析并返回查询结果，对field做验证，如果有错误抛出异常
     * @param GraphqlSearchNode $node [name=>'', sub=>[]]
     * @throws YZE_FatalException
     */
    private function model_query($table, GraphqlSearchNode $node, $id, $wheres, $dql=[], &$total=0) {
        $models = $this->find_All_Models();
        if (!$models) return null;
        $dba = YZE_DBAImpl::getDBA();
        if (!$node->sub){ // 没有查询具体的字段
            return null;
        }
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
        $class = @$models[$table];
        if (!class_exists($class)) throw new YZE_FatalException("field '{$node->name}' not exist");
        $modelObject = new $class();
        $columnConfig = $modelObject->get_columns();
        foreach($modelObject->get_relation_columns() as $column => $config){
            $config['column'] = $column;
            $relationConfig[$config['graphql_field']] = $config;
        }
        foreach ($node->sub as $sub) {
            if ($sub->name == "__typename"){ // 内省关键字处理
                $result["__typename"] = "__Field";
            }elseif (!$sub->sub ){// 查询的字段
                if (!@$columnConfig[$sub->name]) throw new YZE_FatalException("field '{$sub->name}' not exist");
                $result[$sub->name] = null;
                $searchColumns[] = $sub->name;
            }else{ // 查询的关联表
                if (!$relationConfig[$sub->name]) throw new YZE_FatalException("field '{$sub->name}' not exist");
                $result[$sub->name] = null;
                $searchAssocTables[$sub->name] = $relationConfig[$sub->name];
                $searchAssocTables[$sub->name]['node'] = $sub;
                $searchAssocTables[$sub->name]['ids'] = [];
                $foreignKeyColumns[] = $searchAssocTables[$sub->name]['column'];
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
        if ($dql){
            $page = intval(@$dql['page']);
            $page = $page <=0 ? 1 : $page;
            $count = intval(@$dql['count']);
            $count = $count <=0 ? 10 : $count;
            $page = ($page - 1 ) * $count;

            if (@$dql['orderBy']){
                $sort = ['ASC'=>'ASC','DESC'=>'DESC',''=>'ASC'];
                if (!$columnConfig[$dql['orderBy']]) throw new YZE_FatalException("orderBy field '{$dql['orderBy']}' not exist");
                $where .= ' order by '.$dql['orderBy'].' '.$sort[strtoupper(@$dql['sort']?:"")];
            }

            if (@$dql['groupBy']){
                if (!$columnConfig[$dql['groupBy']]) throw new YZE_FatalException("groupBy field '{$dql['groupBy']}' not exist");
                $where .= ' group by '.$dql['groupBy'];
            }

            $pagination = " limit {$page}, $count";
        }
        $totalRst = $dba->nativeQuery("select count(*) as t from `{$table}` ".($where ? "where {$where}" : "").$pagination);
        $totalRst->next();
        $total = intval($totalRst->f('t'));

        $rsts = $dba->nativeQuery("select ".join(',', array_merge($foreignKeyColumns,$searchColumns))
            ." from `{$table}` ".($where ? "where {$where}" : "").$pagination);
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

            foreach($this->model_query($targetClass::TABLE, $assocInfo['node'], null,
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

    private function basic_types() {
        return [
            'Int'=>['description'=>''],
            'Date'=>['description'=>'timestamp,date,datetime,time,year'],
            'String'=>['description'=>''],
            'Float'=>['description'=>''],
            'Boolean'=>['description'=>''],
            'ID'=>['description'=>'']
        ];
    }

    /**
     * 返回格式[tableName=>[__Field]]
     * @return mixed
     * @throws YZE_FatalException
     */
    private function get_all_model_types(){
        if ($this->allModelTypes) return $this->allModelTypes;
        $models = $this->find_All_Models();
        $searchNode = $this->parse("{
    __schema {
      types {
        ...FullType
      }
    }
}

fragment FullType on __Type {
    name
    fields(includeDeprecated: true) {
        name
        type{
            name
        }
    }
}
");
        $searchNode = reset($searchNode);
        $searchNode = GraphqlIntrospection::find_search_node_by_name($searchNode->sub, 'types');
        $types = $this->get_model_schema($models, $searchNode);
        foreach ($types as $type){
            $this->allModelTypes[$type['name']] = $type['fields'];
        }
        return $this->allModelTypes;
    }
}

?>
