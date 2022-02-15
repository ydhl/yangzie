<?php

namespace yangzie;
interface GraphqlDatable{
    public function get_data();
}
class GraphqlInputValue implements GraphqlDatable{
    public $name;
    public $description = "";
    public $typename = "__InputValue";
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
     * 字段的名字
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
    public $typename="__Type";
    /**
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
     * @param string $name
     * @param string $kind 字段的类型，见GraphqlType::KIND
     * @param string $description
     * @param GraphqlField[] $fields
     * @param GraphqlField[] $interfaces
     * @param GraphqlField[] $possibleTypes
     * @param GraphqlField[] $enumValues
     * @param GraphqlField[] $inputFields
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
            'fields' => $fields?:null,
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
    public $typename="__Field";
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


?>
