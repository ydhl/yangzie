<?php

namespace yangzie;
interface GraphqlDatable{
    public function get_data();
}
class GraphqlInputValue implements GraphqlDatable{
    public $name;
    public $description = "";
    private $typename = "__InputValue";
    /**
     * @var GraphqlType
     */
    public $type;
    public $defaultValue = null;
    /**
     * 是否弃用
     * @var bool
     */
    public $isDeprecated = false;
    /**
     * 弃用原因，如果没有弃用必须返回null
     * @var null
     */
    public $deprecationReason = null;

    /**
     * @param $name
     * @param GraphqlType $type
     * @param $description
     * @param $defaultValue
     * @param $isDeprecated
     * @param $deprecationReason
     */
    public function __construct($name, GraphqlType $type, $description="", $defaultValue=null, $isDeprecated=false, $deprecationReason=null){
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->isDeprecated = $isDeprecated;
        $this->deprecationReason = $deprecationReason;
    }
    public function get_data(){
        return [
            "name"=> $this->name,
            "type"=> $this->type->get_data(),
            "description"=> $this->description,
            "__typename"=> $this->typename,
            "defaultValue"=> $this->defaultValue,
            "isDeprecated"=> $this->isDeprecated,
            "deprecationReason"=> $this->deprecationReason,
        ];
    }
}
class GraphqlType implements GraphqlDatable{
    const KIND_SCALAR = "SCALAR";
    const KIND_OBJECT = "OBJECT";
    const KIND_INTERFACE = "INTERFACE";
    const KIND_UNION = "UNION";
    const KIND_ENUM = "ENUM";
    const KIND_INPUT_OBJECT = "INPUT_OBJECT";
    const KIND_LIST = "LIST";
    const KIND_NON_NULL = "NON_NULL";

    /**
     * 字段的名字, 如果是标量类型kind=SCALAR，那么已知的标量类型有String，Int，Boolean，Date，Float，ID
     * @var string
     */
    public $name;
    /**
     * 字段的类型，见GraphqlType::KIND
     * @var string
     */
    public $kind;
    /**
     * 字段描述
     * @var string
     */
    public $description = "";
    private $typename="__Type";
    /**
     * 能查询的字段内容数组
     * @var array<GraphqlField>
     */
    public $fields = [];
    /**
     * @var array<GraphqlField>
     */
    public $interfaces = [];
    /**
     * @var array<GraphqlField>
     */
    public $possibleTypes = [];
    /**
     * @var array<GraphqlField>
     */
    public $enumValues = [];
    /**
     * @var array<GraphqlField>
     */
    public $inputFields = [];
    /**
     * 字段描述
     * @var string
     */
    public $specifiedByURL = "";
    /**
     * 字段描述
     * @var GraphqlType
     */
    public $ofType = null;

    /**
     * @param string|null $name 字段的名字, 如果是标量类型kind=SCALAR，那么已知的标量类型有String，Int，Boolean，Date，Float，ID；如果kind是NON_NULL, LIST name可以为null
     * @param string|null $description
     * @param string $kind 字段的类型，见GraphqlType::KIND
     * @param GraphqlType|null $ofType
     * @param array $fields
     * @param array $interfaces
     * @param array $possibleTypes
     * @param array $enumValues
     * @param array $inputFields
     * @param string $specifiedByURL
     */
    public function __construct(string $name=null, string $description=null, string $kind = GraphqlType::KIND_OBJECT, GraphqlType $ofType=null,
                                array $fields=[], array $interfaces=[], array $possibleTypes=[], array $enumValues=[],
                                array $inputFields=[], string $specifiedByURL="" )
    {
        $this->name = $name;
        $this->ofType = $ofType;
        $this->kind = $kind;
        $this->description = $description;
        $this->fields = $fields;
        $this->interfaces = $interfaces;
        $this->possibleTypes = $possibleTypes;
        $this->enumValues = $enumValues;
        $this->inputFields = $inputFields;
        $this->specifiedByURL = $specifiedByURL;
    }


    public function get_data(){
        $fields = $inputFields = $interfaces = $enumValues = $possibleTypes = [];
        foreach ($this->fields as $field){
            $fields[] = $field->get_data();
        }
        foreach ($this->inputFields as $inputField){
            $inputFields[] = $inputField->get_data();
        }
        foreach ($this->interfaces as $interface){
            $interfaces[] = $interface->get_data();
        }
        foreach ($this->enumValues as $enumValue){
            $enumValues[] = $enumValue->get_data();
        }
        foreach ($this->possibleTypes as $possibleType){
            $possibleTypes[] = $possibleType->get_data();
        }
        return [
            'name' => $this->name,
            'kind' => $this->kind,
            '__typename' => $this->typename,
            'description' => $this->description,
            'specifiedByUrl' => $this->specifiedByURL,
            'fields' => $fields?:[],
            'inputFields' => $inputFields?:null,
            'interfaces' => $interfaces,
            'enumValues' => $enumValues?:null,
            'possibleTypes' => $possibleTypes?:null,
            'ofType' => $this->ofType ? $this->ofType->get_data() :null
        ];
    }
}
class GraphqlField implements GraphqlDatable{
    /**
     * 字段的名字
     * @var string
     */
    public $name;
    /**
     * 字段描述
     * @var string
     */
    public $description = "";
    private $typename="__Field";
    /**
     * @var array<GraphqlInputValue>
     */
    public $args = [];
    /**
     * 字段类型
     * @var GraphqlType
     */
    public $type;
    /**
     * 是否弃用
     * @var bool
     */
    public $isDeprecated = false;
    /**
     * 弃用原因，如果没有弃用必须返回null
     * @var null
     */
    public $deprecationReason = null;

    /**
     * @param string $name
     * @param string $type
     * @param string $description
     * @param array<GraphqlInputValue> $args
     * @param boolean $isDeprecated
     * @param string $deprecationReason
     */
    public function __construct($name, GraphqlType $type, $description="", $args=[], $isDeprecated=false, $deprecationReason=null){
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->args = $args;
        $this->isDeprecated = $isDeprecated;
        $this->deprecationReason = $deprecationReason;
    }
    public function get_data(){
        $args = [];
        foreach ($this->args as $arg){
            $args[] = $arg->get_data();
        }
        return [
            "name"=> $this->name,
            "type"=> $this->type->get_data(),
            "description"=> $this->description,
            "__typename"=> $this->typename,
            "args"=> $args,
            "isDeprecated"=> $this->isDeprecated,
            "deprecationReason"=> $this->deprecationReason,
        ];
    }
}
class GraphqlEnumValue implements GraphqlDatable{
    /**
     * enum的名字
     * @var string
     */
    public $name;
    /**
     * 描述
     * @var string
     */
    public $description = "";
    private $typename="__EnumValue";
    /**
     * 是否弃用
     * @var bool
     */
    public $isDeprecated = false;
    /**
     * 弃用原因，如果没有弃用必须返回null
     * @var null
     */
    public $deprecationReason = null;

    /**
     * @param $name
     * @param $description
     * @param $isDeprecated
     * @param $deprecationReason
     */
    public function __construct($name, $description="",  $isDeprecated=false, $deprecationReason=null){
        $this->name = $name;
        $this->description = $description;
        $this->isDeprecated = $isDeprecated;
        $this->deprecationReason = $deprecationReason;
    }
    public function get_data(){
        return [
            "name"=> $this->name,
            "description"=> $this->description,
            "__typename"=> $this->typename,
            "isDeprecated"=> $this->isDeprecated,
            "deprecationReason"=> $this->deprecationReason,
        ];
    }
}
class GraphqlDirective implements GraphqlDatable{
    const  LOCATION_QUERY = "QUERY";
    const  LOCATION_MUTATION = "MUTATION";
    const  LOCATION_SUBSCRIPTION = "SUBSCRIPTION";
    const  LOCATION_FIELD = "FIELD";
    const  LOCATION_FRAGMENT_DEFINITION = "FRAGMENT_DEFINITION";
    const  LOCATION_FRAGMENT_SPREAD = "FRAGMENT_SPREAD";
    const  LOCATION_INLINE_FRAGMENT = "INLINE_FRAGMENT";
    const  LOCATION_VARIABLE_DEFINITION = "VARIABLE_DEFINITION";
    const  LOCATION_SCHEMA = "SCHEMA";
    const  LOCATION_SCALAR = "SCALAR";
    const  LOCATION_OBJECT = "OBJECT";
    const  LOCATION_FIELD_DEFINITION = "FIELD_DEFINITION";
    const  LOCATION_ARGUMENT_DEFINITION = "ARGUMENT_DEFINITION";
    const  LOCATION_INTERFACE = "INTERFACE";
    const  LOCATION_UNION = "UNION";
    const  LOCATION_ENUM = "ENUM";
    const  LOCATION_ENUM_VALUE = "ENUM_VALUE";
    const  LOCATION_INPUT_OBJECT = "INPUT_OBJECT";
    const  LOCATION_INPUT_FIELD_DEFINITION = "INPUT_FIELD_DEFINITION";
    /**
     * 的名字
     * @var string
     */
    public $name;
    /**
     * 描述
     * @var string
     */
    public $description = "";
    /**
     * @var array<GraphqlInputValue>
     */
    public $args = [];
    private $typename="__Directive";
    /**
     * LOCATION_XX常量
     * @var array
     */
    public $locations = [];
    /**
     *
     * @var boolean
     */
    public $isRepeatable = false;

    /**
     * @param $name
     * @param $description
     * @param $isDeprecated
     * @param $deprecationReason
     */
    public function __construct($name, $description="",  $args=[], $locations=[], $isRepeatable=false){
        $this->name = $name;
        $this->description = $description;
        $this->args = $args;
        $this->locations = $locations;
        $this->isRepeatable = $isRepeatable;
    }
    public function get_data(){
        $args = [];
        foreach ($this->args as $arg){
            $args[] = $arg->get_data();
        }
        return [
            "name"=> $this->name,
            "description"=> $this->description,
            "__typename"=> $this->typename,
            "args"=> $args?:[],
            "locations"=> $this->locations,
            "isRepeatable"=> $this->isRepeatable,
        ];
    }
}
class GraphqlQueryWhere{
    /**
     * 字段名
     * @var string
     */
    public $column;
    /**
     * 比较操作符
     * @var string
     */
    public $op;
    /**
     * @var string|array 查询值，如果op是in find_in_set等操作，可以传入数组
     */
    public $value;
    /**
     * 和下一个where如何拼接
     * @var string and / or
     */
    public $andor;

    /**
     * @param string $column
     * @param string $op
     * @param string|array $value 查询值，如果op是in, 那么值是array
     * @param string $andor
     */
    public function __construct(string $column, string $op, $value, string $andor="and")
    {
        $this->column = $column;
        $this->op = $op;
        $this->value = $value;
        $this->andor = $andor;
    }

    /**
     * @param $wheres
     * @return array<GraphqlQueryWhere>
     */
    public static function build($wheres){
        if (!is_array(reset($wheres))){
            $wheres = [$wheres];
        }
        $_ = [];
        foreach ($wheres as $where){
            $_[] = new GraphqlQueryWhere(@$where['column'], @$where['op'], @$where['value'], @$where['andor']?:'and');
        }
        return $_;
    }
}
class GraphqlQueryClause{
    /**
     *
     * @var string
     */
    public $orderby;
    /**
     * @var string
     */
    public $groupby;
    /**
     * @var string ASC / DESC
     */
    public $sort;
    /**
     * 当前页
     * @var int
     */
    public $page;
    /**
     * 每页条数
     * @var int
     */
    public $count;

    /**
     * @param string $orderby
     * @param string $groupby
     * @param string $sort
     * @param int $page
     * @param int $count
     */
    public function __construct(string $orderby, string $groupby="", string $sort="DESC", int $page=1, int $count=10)
    {
        $this->orderby = $orderby;
        $this->groupby = $groupby;
        $this->sort = $sort;
        $this->page = $page;
        $this->count = $count;
    }

    public static function build($clause){
        return new GraphqlQueryClause(@$clause['orderBy'],@$clause['groupBy'],@$clause['sort'],@$clause['page'],@$clause['count']);
    }
}

/**
 * YZE_GRAPHQL_CUSTOM_QUERY_TYPE Hook中定义的自定义类型, 其是GraphqlType和GraphqlField的集合体
 */
class GraphqlCustomType extends GraphqlType {
    /**
     * 查询参数数组
     * @var array<GraphqlInputValue>
     */
    public $args = [];
    /**
     * 是否弃用
     * @var bool
     */
    public $isDeprecated = false;
    /**
     * 弃用原因，如果没有弃用必须返回null
     * @var null
     */
    public $deprecationReason = null;

    /**
     * @param string|null $name
     * @param string|null $description
     * @param string $kind
     * @param array<GraphqlInputValue> $args
     * @param bool $isDeprecated
     * @param $deprecationReason
     */
    public function __construct(string $name=null, string $description=null, array $args=[], string $kind = GraphqlType::KIND_OBJECT, bool $isDeprecated=false, $deprecationReason=null)
    {
        parent::__construct($name, $description);
        $this->fields = [];
        $this->kind = $kind;
        $this->args = $args;
        $this->isDeprecated = $isDeprecated;
        $this->deprecationReason = $deprecationReason;
    }
    public function get_data()
    {
        return parent::get_data();
    }
}
/**
 * 通过该hook返回自定义的graphql filed，返回的的类型是GraphqlCustomType,
 * 在传入的types中增加自己的自定义type，并return返回types:
 *
 *
 * \yangzie\YZE_Hook::add_hook(YZE_GRAPHQL_CUSTOM_QUERY_TYPE, function ($types){
 *
 *  $types[] = new GraphqlCustomType("your type");
 *
 *  return $types;
 *
 * });
 */
define("YZE_GRAPHQL_CUSTOM_QUERY_TYPE", "YZE_GRAPHQL_CUSTOM_QUERY_TYPE");
/**
 * 对YZE_GRAPHQL_CUSTOM_QUERY_TYPE的内容查询并返回，传入参数是一个数组：
 * [
 * 'search'=>$node,  // GraphqlSearchNode 查询结构体
 * 'rsts'=>[],  // 返回的结果
 * 'total'=>0 // 满足条件的总数
 * ]
 */
define("YZE_GRAPHQL_CUSTOM_SEARCH", "YZE_GRAPHQL_CUSTOM_SEARCH");
?>
