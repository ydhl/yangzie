<?php
/**
 * model抽象，封装了基本的表与model的映射、操作。
 * yangzie约定表都必需包含以下的字段内容：
 * 	主键
 * 	一个标识一条记录的版本的字段，
 * 
 * model的定义都应该包含如下的protected属性：
 * 	映射的表名：
 * 		protected  $table="test"; 
 * 	主键的字段名
 * 		protected $key_name = "id"; 
 * 	版本字段名
 * 		protected $version = "modified_date"; 
 * 	model属于的模块名
 * 		protected $module_name = "posttest";
 * 	model映射的表的字段定义 
 * 		protected $columns = array(
 * 			'id'=>array(),'version'=>array()
 * 		);
 * 
 * 不提供对复合主键的支持
 * 
 * @author liizii
 *
 */
abstract class Model extends YangzieObject{
	protected $records = array();
	/**
	 * 返回表名
	 */
	public function get_table(){
		return $this->table;
	}
	/**
	 * 返回主键字段名,
	 */
	public function get_key_name(){
		return $this->key_name;
	}
	/**
	 * 返回实体对应的字段名,格式是：array('column'=>array(type,nullable))
	 * @return array
	 */
	public function get_columns(){
		return $this->columns;
	}
	public function get_module_name(){
		return $this->module_name;
	}
	public function get_version_name(){
		return $this->version;
	}
	public function get_version_value(){
		return $this->get(@$this->version);
	}

	public function has_set_value($column){
		return array_key_exists($column,$this->records);
	}

	public function has_column($column){
		return array_key_exists($column,$this->columns);
	}
	
	/**
	 * 返回当前的对象与传入的参数对象之间，数据的不同之处，通常在对象设置好数据，并打算更新到数据库之前调用
	 * 
	 * @author leeboo
	 * 
	 * @param Model $old_model
	 * 
	 * @return
	 */
	public function get_diff(Model $old_model){
		if($this->ignore_logs()){
			return null;
		}
		$ignore_column 	= (array)$this->get_diff_ignore_column();
		$old_records 	= $old_model->get_records();
		$new_records 	= $this->get_records();
		$columns		= $this->get_columns();
		$changes = '';
		foreach($old_records as $name => $old_value){
			if(!in_array($name, $ignore_column) && $old_value != $new_records[$name]){
				$changes .= $this->get_diff_column_name($name). "由 "
					.$this->get_diff_column_value($name, $old_value)
				." 修改成 ".$this->get_diff_column_value($name, $new_records[$name]).", ";
			}
		}
		
		//处理关联表的情况
		$old_assocs = $old_model->get_diff_assocs();
		$assocs 	= $this->get_diff_assocs();
		if($old_assocs != $assocs){
			foreach($old_assocs as $name => $old_assoc){
				$changes .= ", 改变了{$name} ";
			}
		}
		return $changes;
	}

	
	/**
	 * 返回关联的要生成操作日志的对象
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return array() 键为对象名，值为对象数组，如array('角色'=>array(Duty_Role_Model,Duty_Role_Model))
	 */
	public function get_diff_assocs(){
		return array();
	}
	
	/**
	 * 返回字段的、用于人读的值，大多数情况都返回值本身，但对于一些外键字段，枚举等需要返回对于人可读性好的内容。
	 * 
	 * @author leeboo
	 * 
	 * @param unknown_type $column_name
	 * @param unknown_type $column_value
	 * 
	 * @return
	 */
	public function get_diff_column_value($column_name, $column_value){
		return $column_value;
	}
	
	/**
	 * 不记录日志
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return
	 */
	public function ignore_logs(){
		return false;
	}
	
	/**
	 * 取得字段的可读性名字，用于人读
	 * 
	 * @author leeboo
	 * 
	 * @param unknown_type $column_name
	 * 
	 * @return
	 */
	public function get_diff_column_name($column_name){
		return $column_name;
	}
	
	/**
	 * 返回对象的对于用户友好的显示名字，用于在界面上显示出信息，！！！实现时注意，在调用方法时该对象可能已经从数据库删除了，实现中要注意判断下
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return string
	 */
	public function get_obj_name(){
		return '';
	}
	
	/**
	 * 
	 * 返回不需要做比较的字段数组
	 * 
	 * @author leeboo
	 * 
	 * @return multitype:
	 * 
	 * @return
	 */
	public function get_diff_ignore_column(){
		return array('id','modified_on','created_on');
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
	 * @return Model 
	 */
	public static function find($id,$class){
		return DBAImpl::getDBA()->find($id,$class);
	}
	public static function find_by_id($id){
		return DBAImpl::getDBA()->find($id,get_called_class());
	}
	/**
	 * 根据主键数组查询对象。返回关联数组，键为主键，
	 * 
	 * @param $ids
	 * @return array
	 */
	public static function find_by_keys($class_name,$key_name, array $keys)
	{
		$sql = new SQL();
		$sql->from($class_name,"o")->where("o", $key_name, SQL::IN, $keys);
		$objects = DBAImpl::getDBA()->select($sql);
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
		DBAImpl::getDBA()->save($this);
		return $this;
	}

	/**
	 * 从数据库删除对象数据，
	 * !!! 但这个对象所包含的数据还存在，只是主键不存在了
	 */
	public function remove(){
		DBAImpl::getDBA()->delete($this);
		return $this;
	}
	
	/**
	 * 删除所有记录
	 * @return bool
	 */
	public static function remove_all($class){
		$sql = new SQL();
		$sql->delete()->from($class,'a');
		return DBAImpl::getDBA()->execute($sql);
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
	public function save_from_post($posts,$prefix)
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
            case "float":$new_value = $new_value+1;break;
            case "date":$new_value = date("Y-m-d H:i:s");break;
        }
        $this->set($this->get_version_name(), $new_value);
    }
	
	private function getFieldType($field_name){
		$columns = $this->get_columns();
		return @$columns[$field_name]['type'];
	}

}
?>