<?php
namespace yangzie;
/**
 * model抽象，封装了基本的表与model的映射、操作。
 * yangzie约定表都必需包含以下的字段内容：
 * 	主键
 * 	一个标识一条记录的版本的字段，
 * 
 * 不提供对复合主键的支持
 * 
 * @author liizii
 *
 */
abstract class YZE_Model extends YZE_Object{
	protected $records = array();
	//array("attr"=>array("from"=>"id","to"=>"id","class"=>"","type"=>"one-one"),"attr"=>array("from"=>"id","to"=>"id","class"=>"","type"=>"one-many")  )
	//$this->attr
	protected $objects = array();
	private $cache = array();
	/**
	 * 返回表名
	 */
	public function get_table(){
		$data = array("table"=>$this::TABLE, "module"=>$this->get_module_name());
		$result = \yangzie\YZE_Hook::do_hook("get_table", $data);
		return $result["table"];
	}
	/**
	 * 返回主键字段名,
	 */
	public function get_key_name(){
		return $this::KEY_NAME;
	}
	/**
	 * 返回实体对应的字段名,格式是：array('column'=>array(type,nullable))
	 * @return array
	 */
	public function get_columns(){
		return $this->columns;
	}
	public function get_module_name(){
		return $this::MODULE_NAME;
	}
	public function get_version_name(){
		return $this::VERSION;
	}
	public function get_version_value(){
		return $this->get(@$this::VERSION);
	}

	public function has_set_value($column){
		return array_key_exists($column,$this->records);
	}

	public function has_column($column){
		return array_key_exists($column,$this->columns);
	}
	
	public function get_all(){
		return YZE_DBAImpl::getDBA()->findAll(get_called_class());
	}
	
	/**
	 * 对model转换成json对象
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return string json string
	 */
	public function toJson(){
		return json_encode($this->get_records());
	}
	
	/**
	 * 根据jsonString创建对象, 如果json不是有效的json，返回null
	 */
	public static function from_Json($json){
		$array = json_decode($json, true);
		if(is_null($array))return null;
		
		$class = get_called_class();
		$obj = new $class();
		
		foreach($array as $name => $value){
			$obj->set($name, $value);
		}
		return $obj;
	}
	
	/**
	 * 返回主键值
	 * @return id
	 */
	public function get_key(){
		return $this->get($this->get_key_name());
	}
	/**
	 * 对于时间字段，去掉datetime后面的时间部分，只留日期部分
	 * @param unknown_type $name
	 */
	public function get_date_val($name, $format="y年m月d日"){
		if (!$this->get($name) || $this->get($name)=="0000-00-00 00:00:00"){
			return "";
		}
		return date($format,strtotime($this->get($name)));
	}
	/**
	 * @param unknown_type $name
	 */
	public function get($name){
		return @$this->records[$name];
	}

	public function get_records(){
		return $this->records;
	}
	/**
	 * 设值的时候会根据字段的类型对值进行相应的处理：
	 * 1. 如果是字符型，不变
	 * 2. 如果是integer型，把值转型为int后再设值
	 * 3. 如果是float型，把值转型为float后再设值
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function set($name,$value){
		//数字的“null”字符串与null值都处理成null
		switch ($this->getFieldType($name)) {
			case "integer":
				if (strcasecmp("NULL", $value)==0 || is_null($value)) {
					$value = "null";
				}else{
					$value =  intval($value);
				}
				break;
			case "float":
				if (strcasecmp("NULL", $value)==0 || is_null($value)) {
                    $value = "null";
                }else{
                    $value =  floatval($value);
                }
                break;
            default:
                if (strcasecmp("NULL", $value)==0 || is_null($value)) {
                    $value = "null";
                }
                break;
		}
		$this->records[$name] = $value;
		return $this;
	}

	/**
	 * 根据主键查询对象
	 * id为查询表的主键，
	 * @param unknown_type $id
	 * @return YZE_Model 
	 */
	public static function find($id,$class){
		return YZE_DBAImpl::getDBA()->find($id,$class);
	}
	public static function find_by_id($id){
		return YZE_DBAImpl::getDBA()->find($id,get_called_class());
	}
	/**
	 * 删除数据库中的一条记录
	 * 
	 * @author leeboo
	 * 
	 * @param unknown $id
	 * @throws YZE_DBAException
	 * @return boolean 
	 * 
	 * @return
	 */
	public static function remove_by_id($id){
		$class = get_called_class();
		
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}
		
		$entity = $class instanceof YZE_Model ? $class : new $class;
		
		$sql = new YZE_SQL();
		$sql->delete()->from(get_called_class(), "t");
		if (is_array($id)) {
            $sql->where("t", $entity->get_key_name(), YZE_SQL::IN, $id);
        } else {
            $sql->where("t", $entity->get_key_name(), YZE_SQL::EQ, $id);
        }
        
        YZE_DBAImpl::getDBA()->execute($sql);
        
        return true;
	}
	
	public static function remove_by_attrs($attrs){
		$class = get_called_class();
	
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}
	
		$entity = $class instanceof YZE_Model ? $class : new $class;
	
		$sql = new YZE_SQL();
		$sql->delete()->from(get_called_class(), "t");
		foreach ($attrs as $name => $value){
			$sql->where("t", $name, YZE_SQL::EQ, $value);
		}
		
		YZE_DBAImpl::getDBA()->execute($sql);
		
		return true;
	}
	
	/**
	 * 直接更新数据库中的记录
	 * 
	 * @author leeboo
	 * 
	 * @param unknown $id
	 * @param unknown $attrs
	 * @throws YZE_DBAException
	 * @return boolean
	 * 
	 * @return
	 */
	public static function update_by_id($id, $attrs){
		$class = get_called_class();
	
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}
	
		$entity = $class instanceof YZE_Model ? $class : new $class;
	
		$sql = new YZE_SQL();
		$sql->update("t", $attrs)->from(get_called_class(), "t");
		if (is_array($id)) {
		    $sql->where("t", $entity->get_key_name(), YZE_SQL::IN, $id);
		} else {
		    $sql->where("t", $entity->get_key_name(), YZE_SQL::EQ, $id);
		}
		YZE_DBAImpl::getDBA()->execute($sql);
		
		return true;
	}
	
	public static function find_all(){
		return YZE_DBAImpl::getDBA()->findAll(get_called_class());
	}
	/**
	 * 根据主键数组查询对象。返回关联数组，键为主键，
	 * 
	 * @param $ids
	 * @param $class_name 不设置表示当前调用的类
	 * @return array  key 为索引的数组
	 */
	public static function find_by_keys($class_name,$key_name, array $keys)
	{
	    if( ! $class_name){
	        $class_name = get_called_class();
	    }
		$sql = new YZE_SQL();
		$sql->from($class_name,"o")->where("o", $key_name, YZE_SQL::IN, $keys);
		$objects = YZE_DBAImpl::getDBA()->select($sql);
		$_ = array();
		foreach ($objects as $object){
			$_[$object->get_key()] = $object;
		}
		return $_;
	}
	
	/**
	 * 根据字段属性查找
	 * 
	 * @author leeboo
	 * 
	 * @param array $attrs
	 * @param array $fields 要求查询的字段
	 * @return multitype:Ambigous <\yangzie\array(Model), multitype:Ambigous <NULL, unknown> > 
	 * 
	 * @return
	 */
	public static function find_by_attrs(array $attrs, $fields=array("*"))
	{
		$sql = new YZE_SQL();
		$sql->select("o", $fields)
		    ->from(get_called_class(),"o");
		foreach ($attrs as $att=>$value){
			$sql->where("o", $att, YZE_SQL::EQ, $value);
		}
		
		$objects = YZE_DBAImpl::getDBA()->select($sql);
		$_ = array();
		foreach ($objects as $object){
			$_[$object->get_key()] = $object;
		}
		return $_;
	}
	
	/**
	 * 持久到数据库,返回自己
	 */
	public function save(){
		YZE_DBAImpl::getDBA()->save($this);
		return $this;
	}

	/**
	 * 从数据库删除对象数据，
	 * !!! 但这个对象所包含的数据还存在，只是主键不存在了
	 */
	public function remove(){
		YZE_DBAImpl::getDBA()->delete($this);
		return $this;
	}
	
	/**
	 * 从数据库中刷新
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return
	 */
	public function refresh(){
		$new = YZE_Model::find($this->get_key(), get_class($this));
		if($new){
			foreach ($new->get_records() as $name => $value){
				$this->set($name, $value);
			}
		}
		return $this;
	}
	
	/**
	 * 删除所有记录
	 * @return bool
	 */
	public static function remove_all(){
		$sql = new YZE_SQL();
		$sql->delete()->from(get_called_class(),'a');
		return YZE_DBAImpl::getDBA()->execute($sql);
	}
	/**
	 * 把当前对象的主键值删除
	 */
	public function delete_key(){
		unset($this->records[$this->get_key_name()]);
		return $this;
	}
	
	/**
	 * 从post提交数据中更新自己
	 */
	public function save_from_post($posts,$prefix="")
	{
		foreach ( $this->get_columns() as $name => $define) {
			if (array_key_exists($prefix.$name, $posts)) {
				$this->set($name, $posts[$prefix.$name]);
			}
		}
		return $this->save();
	}

    /**
     * 更新Model的版本字段
     */
    public function update_version()
    {
        if (!$this->get_version_name()){
            return;
        }
        $new_value = $this->get_version_value();
        switch ($this->getFieldType($this->get_version_name())) {
            case "integer":
            case "float":   $new_value = $new_value+1;break;
            case "date":    $new_value = date("Y-m-d H:i:s");break;
        }
        $this->set($this->get_version_name(), $new_value);
    }

	
	public function __get($name){
	    if(array_key_exists($name, $this->objects)){
	        return $this->get_object($name);
	    }
	    return $this->get($name);
	}
	
	public function __set($name, $value){
	    if(array_key_exists($name, $this->objects)){
	        return $this->set_object($name, $value);
	    }
	    return $this->set($name, $value);
	}
	

	private function get_object($field_name){
	    if( @$this->cache[$field_name]) return $this->cache[$field_name];
	    $info = $this->objects[$field_name];
	    if(@$info['method']){
	        $method = $info['method'];
	        $this->cache[$field_name] = $this->$method();
	        return $this->cache[$field_name];
	    }
	    $objs = $info['class']::find_by_attrs(array($info['to'] => $this->get($info['from'])));
	    
	    if( !count($objs) )return null;
	    
	    if($info['type'] == "one-one"){
            $this->cache[$field_name] = reset($objs);
	    }else{
            $this->cache[$field_name] = $objs;
	    }
	    return @$this->cache[$field_name];
	}
	

	private function set_object($field_name, YZE_Model $value){
	    if( ! @$this->cache[$field_name]) return $this->cache[$field_name] = array();
	    
	    $info = $this->objects[$field_name];
	    
	    if($info['type'] == "one-one"){
            $this->cache[$field_name] = $value;
	    }else{
            $this->cache[$field_name][$value->get_key()] = $value;
	    }
	    
	    return $this;
	}

	private function getFieldType($field_name){
	    $columns = $this->get_columns();
	    return @$columns[$field_name]['type'];
	}
}
?>