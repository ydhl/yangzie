<?php

namespace yangzie;

/**
 * graphql schema 内省处理
 */
trait Graphql__Introspection{

    private function basic_types() {
        return [
            'float'=>['description'=>'decimal,float,double.'],
            'integer'=>['description'=>'int,tinyint,smallint,mediumint,bigint'],
            'date'=>['description'=>'timestamp,date,datetime,time,year'],
            'enum'=>['description'=>'enum'],
            'string'=>['description'=>'string'],
            'boolean'=>['description'=>'boolean value'],
        ];
    }

    private function _introspection_field($fildTypes, $node){
        $fields = [];
        foreach ($fildTypes as $name=>$typeInfo){
            $rst = [];
            foreach ($node['sub'] as $sub) {
                $subName = strtoupper($sub['name']);
                switch ($subName) {
                    case 'NAME': $rst[$sub['name']] = $name; break;
                    case 'DESCRIPTION': $rst[$sub['name']] = @$typeInfo['description']; break;
                    case 'ARGS':$rst[$sub['name']] = $this->_introspection_field_args($sub, @$typeInfo['args']); break;
                    case 'TYPE': $rst[$sub['name']] = $this->_introspection_field_type($sub, $typeInfo['type']); break;
                    case 'ISDEPRECATED': $rst[$sub['name']] = false; break;
                    case 'DEPRECATIONREASON': $rst[$sub['name']] = null; break;
                }
            }
            $fields[] = $rst;
        }
        return $fields;
    }
    private function _introspection_field_type($node, $typeInfo){
        if (!$typeInfo) return null;
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'NAME':$rst[$sub['name']] = $typeInfo['name'];break;
                case 'KIND': $rst[$sub['name']] = $typeInfo['kind']; break;
                case 'OFTYPE': $rst[$sub['name']] = $this->_introspection_field_type($sub, @$typeInfo['ofType']); break;
            }
        }
        return $rst;
    }

    private function _introspection_field_args($node, $argsInfo){
        if (!$argsInfo) return [];
        $args = [];
        foreach ($argsInfo as $argInfo){
            $rst = [];
            foreach ($node['sub'] as $sub) {
                $subName = strtoupper($sub['name']);
                switch ($subName) {
                    case 'NAME':$rst[$sub['name']] = $argInfo['name'];break;
                    case 'DESCRIPTION': $rst[$sub['name']] = @$argInfo['description']; break;
                    case 'DEFAULTVALUE': $rst[$sub['name']] = $argInfo['defaultValue']; break;
                    case 'ISDEPRECATED': $rst[$sub['name']] = $argInfo['isDeprecated']; break;
                    case 'DEPRECATIONREASON': $rst[$sub['name']] = $argInfo['deprecationReason']; break;
                    case 'TYPE': $rst[$sub['name']] = $this->_introspection_field_type($sub, $argInfo['type']); break;
                }
            }
            $args[] = $rst;
        }
        return $args;
    }
    /**
     * 返回内省的__Scheme类型，指定__scheme中有那些内容
     * @param $results
     * @param $node
     * @return void
     */
    private function _introspection__schema(&$results, $node){
        $fields = [
            'description'=>['description'=>'','type'=>['kind'=>'SCALAR','name'=>'string','ofType'=>null]],
            'types'=>['description'=>'A list of all types supported by this server.','type'=>[
                'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                    'kind'=>'LIST','name'=>null,'ofType'=>[
                        'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                            'kind'=>'OBJECT','name'=>'__Type','ofType'=>null
                        ]
                    ]
                ]
            ]],
            'queryType'=>['description'=>'The type that query operations will be rooted at.','type'=>['kind'=>'NON_NULL','name'=>null,'ofType'=>['kind'=>'OBJECT','name'=>'__Type','ofType'=>null]]],
            'mutationType'=>['description'=>'If this server supports mutation, the type that mutation operations will be rooted at.','type'=>['kind'=>'OBJECT','name'=>'__Type','ofType'=>null]],
            'subscriptionType'=>['description'=>'If this server support subscription, the type that subscription operations will be rooted at.','type'=>['kind'=>'OBJECT','name'=>'__Type','ofType'=>null]],
            'directives'=>['description'=>'A list of all directives supported by this server.','type'=>[
                'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                    'kind'=>'LIST','name'=>null,'ofType'=>[
                        'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                            'kind'=>'OBJECT','name'=>'__Directive','ofType'=>null
                        ]
                    ]
                ]
            ]]
        ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__Schema'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "A GraphQL Schema defines the capabilities of a GraphQL server. It exposes all available types and directives on the server, as well as the entry points for query, mutation, and subscription operations."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }

    /**
     * 返回内省的__type类型，指定__type中有那些内容
     * @param $results
     * @param $node
     * @return void
     */
    private function _introspection__type(&$results, $node){
        $fields =  [
            'kind'=>['description'=>'','type'=>['kind'=>'NON_NULL','name'=>null,'ofType'=>['kind'=>'ENUM','name'=>'__TypeKind','ofType'=>null]]],
            'name'=>['description'=>'','type'=>['kind'=>'SCALAR','name'=>'string','ofType'=>null]],
            'description'=>['description'=>'','type'=>['kind'=>'SCALAR','name'=>'string','ofType'=>null]],
            'specifiedByURL'=>['description'=>'','type'=>['kind'=>'SCALAR','name'=>'string','ofType'=>null]],
            'fields'=>['description'=>'','args'=>[[
                "name"=> "includeDeprecated",
                "type"=> [
                    "kind"=> "SCALAR",
                    "name"=> "boolean",
                    "ofType"=> null
                ],
                "defaultValue"=> "false",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ]],'type'=>['kind'=>'LIST','name'=>'','ofType'=>['kind'=>'NON_NULL','name'=>null,'ofType'=>['kind'=>'OBJECT','name'=>'__Field','ofType'=>null]]]],
            'interfaces'=>['description'=>'','type'=>[
                'kind'=>'LIST','name'=>null,'ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                        'kind'=>'OBJECT','name'=>'__Type'
                    ]
                ]
            ]],
            'possibleTypes'=>['description'=>'','type'=>[
                'kind'=>'LIST','name'=>null,'ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                        'kind'=>'OBJECT','name'=>'__Type'
                    ]
                ]
            ]],
            'enumValues'=>['description'=>'','args'=>[[
                "name"=> "includeDeprecated",
                "type"=> [
                    "kind"=> "SCALAR",
                    "name"=> "boolean",
                    "ofType"=> null
                ],
                "defaultValue"=> "false",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ]],'type'=>[
                'kind'=>'LIST','name'=>null,'ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                        'kind'=>'OBJECT','name'=>'__EnumValue'
                    ]
                ]
            ]],
            'inputFields'=>['description'=>'','args'=>[[
                "name"=> "includeDeprecated",
                "type"=> [
                    "kind"=> "SCALAR",
                    "name"=> "boolean",
                    "ofType"=> null
                ],
                "defaultValue"=> "false",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ]],'type'=>[
                'kind'=>'LIST','name'=>null,'ofType'=>[
                    'kind'=>'NON_NULL','name'=>null,'ofType'=>[
                        'kind'=>'OBJECT','name'=>'__InputValue'
                    ]
                ]
            ]]
        ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__Type'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "The fundamental unit of any GraphQL Schema is the type. There are many kinds of types in GraphQL as represented by the `__TypeKind` enum.\n\nDepending on the kind of a type, certain fields describe information about that type. Scalar types provide no information beyond a name, description and optional `specifiedByURL`, while Enum types provide their values. Object and Interface types provide the fields they describe. Abstract types, Union and Interface, provide the Object types possible at runtime. List and NonNull types compose other types."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection__field(&$results, $node){
        $fields = [
            "name" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    "ofType"=> null
                ]
              ]
            ],
            "description" => [
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ],
            "args" => [
              "args"=> [
                [
                  "name"=> "includeDeprecated",
                  "type"=> [
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
                "name"=> null,
                "ofType"=> [
                    "kind"=> "LIST",
                    "name"=> null,
                    "ofType"=> [
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        "ofType"=> [
                            "kind"=> "OBJECT",
                            "name"=> "__InputValue",
                            "ofType"=> null
                        ]
                    ]
                ]
              ]
            ],
            "type" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "OBJECT",
                    "name"=> "__Type",
                    "ofType"=> null
                ]
              ]
            ],
            "isDeprecated" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                  "name"=> "Boolean",
                  "ofType"=> null
                ]
              ]
            ],
            "deprecationReason" => [
              "args"=> [],
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ]
          ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__Field'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "Object and Interface types are described by a list of Fields, each of which has a name, potentially a list of arguments, and a return type."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection__inputvalue(&$results, $node){
        $fields = [
            "name" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                  "name"=> "String",
                  "ofType"=> null
                ]
              ]
            ],
            "description" => [
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ],
            "type" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "OBJECT",
                    "name"=> "__Type",
                    "ofType"=> null
                ]
              ]
            ],
            "defaultValue"=>[
              "description"=> "A GraphQL-formatted string representing the default value for this input value.",
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ],
            "isDeprecated"=>[
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                  "name"=> "Boolean",
                  "ofType"=> null
                ]
              ]
            ],
            "deprecationReason" => [
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ]
          ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__InputValue'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "Arguments provided to Fields or Directives and the input fields of an InputObject are represented as Input Values which describe their type and optionally a default value."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection_enumvalues($enumvalues, $node){
        $rsts = [];
        foreach ($enumvalues as $enum){
            $rst = [];
            foreach ($node['sub'] as $sub) {
                $subName = strtoupper($sub['name']);
                switch ($subName) {
                    case 'NAME': $rst[$sub['name']] = $enum['name']; break;
                    case 'DESCRIPTION': $rst[$sub['name']] = $enum['description']; break;
                    case 'ISDEPRECATED': $rst[$sub['name']] = $enum['isDeprecated']; break;
                    case 'DEPRECATIONREASON': $rst[$sub['name']] = $enum['deprecationReason']; break;
                }
            }
            $rsts[] = $rst;
        }
        return $rsts;
    }
    /**
     * 返回内省的__typekind类型，指定__typekind中有那些内容
     * @param $results
     * @param $node
     * @return void
     */
    private function _introspection__typekind(&$results, $node){
        $enumvalues = [
            [
                "name"=> "SCALAR",
                "description"=> "Indicates this type is a scalar.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "OBJECT",
                "description"=> "Indicates this type is an object. `fields` and `interfaces` are valid fields.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INTERFACE",
                "description"=> "Indicates this type is an interface. `fields`, `interfaces`, and `possibleTypes` are valid fields.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "UNION",
                "description"=> "Indicates this type is a union. `possibleTypes` is a valid field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "ENUM",
                "description"=> "Indicates this type is an enum. `enumValues` is a valid field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INPUT_OBJECT",
                "description"=> "Indicates this type is an input object. `inputFields` is a valid field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "LIST",
                "description"=> "Indicates this type is a list. `ofType` is a valid field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "NON_NULL",
                "description"=> "Indicates this type is a non-null. `ofType` is a valid field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ]
        ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'ENUM'; break;
                case 'NAME': $rst[$sub['name']] = '__TypeKind'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "An enum describing what kind of type a given `__Type` is."; break;
                case 'FIELDS': $rst[$sub['name']] = null; break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = $this->_introspection_enumvalues($enumvalues, $sub); break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection__enumvalue(&$results, $node){
        $fields = [
            "name" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                  "name"=> "String",
                  "ofType"=> null
                ]
              ]
            ],
            "description" => [
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ],
            "isDeprecated" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "Boolean",
                    "ofType"=> null
                ]
              ]
            ],
            "deprecationReason" => [
              "args"=> [],
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ]
          ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__EnumValue'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "One possible value for a given Enum. Enum values are unique values, not a placeholder for a string or numeric value. However an Enum value is returned in a JSON response as a string."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection__directive(&$results, $node){
        $fields = [
            "name" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "String",
                    "ofType"=> null
                ]
              ]
            ],
            "description"=>[
              "type"=> [
                "kind"=> "SCALAR",
                "name"=> "String",
                "ofType"=> null
              ]
            ],
            "isRepeatable" => [
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "SCALAR",
                    "name"=> "Boolean",
                    "ofType"=> null
                ]
              ]
            ],
            "locations"=>[
              "type"=> [
                "kind"=> "NON_NULL",
                "name"=> null,
                "ofType"=> [
                    "kind"=> "LIST",
                    "name"=> null,
                    "ofType"=> [
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        "ofType"=> [
                            "kind"=> "ENUM",
                            "name"=> "__DirectiveLocation",
                            "ofType"=> null
                    ]
                  ]
                ]
              ]
            ],
            "args"=>[
              "args"=> [
                [
                  "name"=> "includeDeprecated",
                  "description"=> null,
                  "type"=> [
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
                "name"=> null,
                "ofType"=> [
                    "kind"=> "LIST",
                    "name"=> null,
                    "ofType"=> [
                        "kind"=> "NON_NULL",
                        "name"=> null,
                        "ofType"=> [
                            "kind"=> "OBJECT",
                            "name"=> "__InputValue",
                            "ofType"=> null
                    ]
                  ]
                ]
              ]
            ]
          ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'OBJECT'; break;
                case 'NAME': $rst[$sub['name']] = '__Directive'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "A Directive provides a way to describe alternate runtime execution and type validation behavior in a GraphQL document.\n\nIn some cases, you need to provide options to alter GraphQL's execution behavior in ways field arguments will not suffice, such as conditionally including or skipping a field. Directives provide this by describing additional information to the executor."; break;
                case 'FIELDS': $rst[$sub['name']] = $this->_introspection_field($fields, $sub); break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = null; break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }
    private function _introspection__directiveLocation(&$results, $node){
        $enumvalues = [
            [
                "name"=> "QUERY",
                "description"=> "Location adjacent to a query operation.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "MUTATION",
                "description"=> "Location adjacent to a mutation operation.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "SUBSCRIPTION",
                "description"=> "Location adjacent to a subscription operation.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "FIELD",
                "description"=> "Location adjacent to a field.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "FRAGMENT_DEFINITION",
                "description"=> "Location adjacent to a fragment definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "FRAGMENT_SPREAD",
                "description"=> "Location adjacent to a fragment spread.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INLINE_FRAGMENT",
                "description"=> "Location adjacent to an inline fragment.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "VARIABLE_DEFINITION",
                "description"=> "Location adjacent to a variable definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "SCHEMA",
                "description"=> "Location adjacent to a schema definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "SCALAR",
                "description"=> "Location adjacent to a scalar definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "OBJECT",
                "description"=> "Location adjacent to an object type definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "FIELD_DEFINITION",
                "description"=> "Location adjacent to a field definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "ARGUMENT_DEFINITION",
                "description"=> "Location adjacent to an argument definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INTERFACE",
                "description"=> "Location adjacent to an interface definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "UNION",
                "description"=> "Location adjacent to a union definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "ENUM",
                "description"=> "Location adjacent to an enum definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "ENUM_VALUE",
                "description"=> "Location adjacent to an enum value definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INPUT_OBJECT",
                "description"=> "Location adjacent to an input object type definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ],
            [
                "name"=> "INPUT_FIELD_DEFINITION",
                "description"=> "Location adjacent to an input object field definition.",
                "isDeprecated"=> false,
                "deprecationReason"=> null
            ]
        ];
        $rst = [];
        foreach ($node['sub'] as $sub) {
            $subName = strtoupper($sub['name']);
            switch ($subName) {
                case 'KIND': $rst[$sub['name']] = 'ENUM'; break;
                case 'NAME': $rst[$sub['name']] = '__DirectiveLocation'; break;
                case 'DESCRIPTION': $rst[$sub['name']] = "A Directive can be adjacent to many parts of the GraphQL language, a __DirectiveLocation describes one such possible adjacencies."; break;
                case 'FIELDS': $rst[$sub['name']] = null; break;
                case 'INPUTFIELDS': $rst[$sub['name']] = null; break;
                case 'INTERFACES': $rst[$sub['name']] = []; break;
                case 'ENUMVALUES': $rst[$sub['name']] = $this->_introspection_enumvalues($enumvalues, $sub); break;
                case 'POSSIBLETYPES': $rst[$sub['name']] = null; break;
            }
        }
        $results[] = $rst;
    }

}

?>
