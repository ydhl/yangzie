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
	/**
	 * @var YZE_SQL
	 */
	private $sql;
	protected $records = array();
	/**
	 * 映射：array("attr"=>array("from"=>"id","to"=>"id","class"=>"","type"=>"one-one"),"attr"=>array("from"=>"id","to"=>"id","class"=>"","type"=>"one-many")  )
	 *
	 * 获取：$this->attr;
	 */
	protected $objects = array();
	/**
	 * 需要进行加密的字段名，
	 * 加密是对称的，对于这类指定的加密字段，通过yangzie Api进行读取和写入时（get,set）是自动进行加密解密的，对开发者是无感的
	 * 如果不配置，开发者也可通过YZE_DBAImpl->encrypt,YZE_DBAImpl->decrypt加解密设置，
	 * 通过yangzie接口对数据进行读写的都支持加解密处理, 但如果是开发者自己写原生sql，则由开发者自行处理
	 * <br/>
	 * 加解密不同的数据库实现会不同，Mysql是通过AES_ENCRYPT、AES_DECRYPT实现的；加解密的秘钥在__config__.php中YZE_DB_CRYPT_KEY
	 * 但要注意加密的内容是二进制格式（blob），或者自行通过bin2hex等转换成字符串存储，所以需要设置合适的字段类型
	 *
	 *
	 * @var array
	 */
	public $encrypt_columns = array();
	private $cache = array();
	/**
	 * 如果在INSERT插入行后会导致在一个UNIQUE索引或PRIMARY KEY中出现重复值，
	 * 则在出现重复值的行执行UPDATE；并用unique_key 配置的字段作为update的条件
	 * 如果不会导致唯一值列重复的问题，则插入新行. 用法：
	 * $unique_key = array("A"=>"Key_name_A","B"=>"Key_name_B","C"=>"Key_name_B","D"=>"Key_name_D","E"=>"Key_name_D");
	 *
	 * A,B,C三个是独立唯一的字段，D,E是联合起来唯一的字段
	 * @var array
	 */
	protected $unique_key = array();

	public function get_unique_key(){
	    return $this->unique_key;
	}
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
		return static::$columns;
	}
	/**
	 * 返回所有的字段，已,分隔，如果指定了pre，则字段会起别名：{$pre}.{$item} as {$pre}_{$item}
	 * @param string $pre
	 */
	public static function get_column_string($pre=""){
		$cls = get_called_class();
		$obj = new $cls;
		return join(",", array_map(function($item) use ($pre){
			return $pre ? "{$pre}.{$item} as {$pre}_{$item}" :  "{$item}";
		}, array_keys($obj->get_columns())));
	}
	public function get_module_name(){
		return $this::MODULE_NAME;
	}

	public function has_set_value($column){
		return array_key_exists($column,$this->records);
	}

	public function has_column($column){
		return array_key_exists($column,static::$columns);
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

	public function get_records(){
		return $this->records;
	}
	/**
	 *
	 * @param unknown_type $name
	 */
	public function get($name){
		return @$this->records[$name];
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
				$value = is_null($value) ? null : intval($value);
				break;
			case "float":
				$value = is_null($value) ? null : floatval($value);
                break;
            default:
                if (is_null($value)) {
                    $value = null;
                }
                break;
		}
		$this->records[$name] = $value;
		return $this;
	}

	/**
	 * 根据主键查询当前类映射表数据
	 *
	 * @param unknown $id
	 */
	public static function find_by_id($id){
		return YZE_DBAImpl::getDBA()->find($id,get_called_class());
	}

	/**
	 * 查找对象并以id作为键返回数组
	 * @param string|array $ids
	 */
	public static function find_by_ids($ids){
	    $arr = [];
	    if (is_string($ids)){
	        $ids = explode(",", $ids);
	    }
	    $ids = array_filter($ids);
	    if( ! $ids)return $arr;
	    foreach (YZE_DBAImpl::getDBA()->find_by($ids, get_called_class()) as $obj){
	        $arr[ $obj->id ] = $obj;
	    }
	    return $arr;
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
	public function isEmptyDate($name){
		return !$this->get($name) || $this->get($name)=="0000-00-00" || $this->get($name)=="0000-00-00 00:00:00";;
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
	 * 持久到数据库,返回自己;如果有主键，则更新；没有则插入；
	 * 插入情况，根据$type进行不同的插入策略
	 * INSERT_NORMAL：普通插入语句
	 * INSERT_NOT_EXIST： 指定的where条件查询不出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_NOT_EXIST_OR_UPDATE： 指定的$sql条件查询不出数据时才插入, 查询数据则更新这条数据；如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_EXIST： 指定的$sql条件查询出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_ON_DUPLICATE_KEY_UPDATE： 有唯一健冲突时更新其它字段
	 * INSERT_ON_DUPLICATE_KEY_REPLACE： 有唯一健冲突时先删除原来的，然后在插入
	 * INSERT_ON_DUPLICATE_KEY_IGNORE： 有唯一健冲突时忽略，不抛异常
	 * @param string $sql 完整的判断查询sql
	 */
	public function save($type=YZE_SQL::INSERT_NORMAL, YZE_SQL $sql=null){
	    YZE_DBAImpl::getDBA()->save($this, $type, $sql);
		return $this;
	}

	/**
	 * 判断传入的字段值，如果这些值已存在，则更新，否则插入
	 *
	 * @author liizii
	 * @param array $fileds
	 * @return YZE_Model
	 */
	public function insertOrUpdate( $checkFields ){
	    if ( ! $checkFields){
	        $this->save();
	        return $this;
	    }

	    $sql = new YZE_SQL();
	    $sql->from($this::CLASS_NAME, "__mine__");
	    foreach ($checkFields as $field){
	       $sql->where("__mine__", $field, YZE_SQL::EQ, $this->get($field));
	    }
	    $this->save(YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE, $sql);
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
		$new = YZE_DBAImpl::getDBA()->find($this->get_key(), get_class($this));
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


    public function delete_field($key){
        unset($this->records[$key]);
        return $this;
    }

	/**
	 * 持久到数据库,返回自己;如果有主键，则更新；没有则插入；
	 * 插入情况，根据$type进行不同的插入策略
	 * INSERT_NORMAL：普通插入语句
	 * INSERT_NOT_EXIST： 指定的where条件查询不出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_NOT_EXIST_OR_UPDATE： 指定的$sql条件查询不出数据时才插入, 查询数据则更新这条数据；如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_EXIST： 指定的$sql条件查询出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_ON_DUPLICATE_KEY_UPDATE： 有唯一健冲突时更新其它字段
	 * INSERT_ON_DUPLICATE_KEY_REPLACE： 有唯一健冲突时先删除原来的，然后在插入
	 * INSERT_ON_DUPLICATE_KEY_IGNORE： 有唯一健冲突时忽略，不抛异常
	 * @param string $sql 完整的判断查询sql
	 */
	public function save_from_post($posts,$prefix="", $type=YZE_SQL::INSERT_NORMAL, YZE_SQL $sql=null)
	{
		foreach ( $this->get_columns() as $name => $define) {
			if (array_key_exists($prefix.$name, $posts)) {
				$this->set($name, $posts[$prefix.$name]);
			}
		}
		return $this->save($type, $sql);
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
	    $value = $this->get($name);
	    if (in_array($name, $this->encrypt_columns)){
	    	$value = YZE_DBAImpl::getDBA()->decrypt($value, YZE_DB_CRYPT_KEY);
		}
	    return $value;
	}

	public function __set($name, $value){
		if (in_array($name, $this->encrypt_columns)){
			$value = YZE_DBAImpl::getDBA()->encrypt($value, YZE_DB_CRYPT_KEY);
		}
	    return $this->set($name, $value);
	}

	/////////////////  Model SQL ////////////////////////////
	public static function from($myAlias=null){
		$obj = new static;
		$obj->initSql ($myAlias);
		return $obj;
	}
	/**
	 * 调用方式where: ("CHAR_LENGTH(title)=:title and (id>10 or id<20)")
	 *
	 * @var static
	 */
	public function where($where){
		$this->initSql();
		$this->sql->nativeWhere($where);
		return $this;
	}

	public function order_By($column, $sort, $alias=null){
		$this->initSql();
		$this->sql->order_by($alias ? $alias : "m", $column, $sort);
		return $this;
	}

	public function group_By($column, $alias=null){
		$this->initSql();
		$this->sql->group_by($alias ? $alias : "m", $column);
		return $this;
	}

	public function limit($start, $limit){
		$this->initSql();
		$this->sql->limit($start, $limit);
		return $this;
	}

	public function left_join($joinClass, $joinAlias, $join_on){
		$this->initSql ();

		$this->sql->left_join($joinClass, $joinAlias ? $joinAlias : "m", $join_on);
		return $this;
	}
	public function right_join( $joinClass, $joinAlias, $join_on){
		$this->initSql ();

		$this->sql->right_join($joinClass, $joinAlias ? $joinAlias : "m", $join_on);
		return $this;
	}
	public function join($joinClass, $joinAlias, $join_on){
		$this->initSql ();

		$this->sql->join($joinClass, $joinAlias ? $joinAlias : "m", $join_on);
		return $this;
	}

	/**
	 * 该方法需要在最后调用，该方法直接返回查询结果数组, 该方法调用后sql中where部分会保留
	 *
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias要选择的对象的别名，如果有联合查询，没有指定alias则返回所有的数据
	 * @return array
	 */
	public function select(array $params=array(), $alias=null){
		$this->initSql ();
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		if ($alias){
			$this->sql->select($alias);
		}

		$obj = YZE_DBAImpl::getDBA()->select($this->sql, $params);

		$this->sql->clean_select();
		return $obj;
	}
	/**
	 * 该方法需要在最后调用，该方法直接返回查询结果对象, 该方法调用后sql中where部分会保留
	 *
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias要选择的对象的别名，如果有联合查询，没有指定alias则返回所有的数据
	 * @return static
	 */
	public function getSingle(array $params=array(), $alias=null){
		$this->initSql ();

		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		if ($alias){
			$this->sql->select($alias);
		}
		$this->sql->limit(1);

		$obj = YZE_DBAImpl::getDBA()->getSingle($this->sql, $params);
		$this->sql->clean_select();
		return $obj;
	}
	/**
	 * 返回count结果, 该方法调用后sql中where部分会保留
	 * @param unknown $field count 字段
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias alias要选择的对象的别名，如果有联合查询；没有指定alias，则默认是直接类，也就是第一个调用的静态类，如TestModel::where()->Left_jion()->count()中的TestModel
	 * @param bool $distinct
	 * @return int
	 */
	public function count($field, array $params=array(), $alias=null,$distinct=false){
		$this->initSql();
		if ( ! $alias){
			$alias = "m";
		}
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		$this->sql->count($alias , $field, "COUNT_ALIAS", $distinct);

		$obj = YZE_DBAImpl::getDBA()->getSingle($this->sql, $params);
		$this->sql->clean_select();
		if ( ! $obj)return 0;
		$obj = is_array($obj) ? $obj[$alias] : $obj;
		return $obj ? $obj->Get("COUNT_ALIAS") : 0;
	}
	/**
	 * 返回sum结果, 该方法调用后sql中where部分会保留
	 * @param unknown $field count 字段
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias alias要选择的对象的别名，如果有联合查询；没有指定alias，则默认是直接类，也就是第一个调用的静态类，如TestModel::where()->Left_jion()->sum()中的TestModel
	 * @return int
	 */
	public function sum($field, array $params=array(), $alias=null){
		$this->initSql();
		if ( ! $alias){
			$alias = "m";
		}
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		$this->sql->sum($alias, $field, "SUM_ALIAS");

		$obj = YZE_DBAImpl::getDBA()->getSingle($this->sql, $params);
		$this->sql->clean_select();
		if ( ! $obj)return 0;
		$obj = is_array($obj) ? $obj[$alias] : $obj;
		return $obj ? $obj->Get("SUM_ALIAS") : 0;
	}
	/**
	 * 返回max结果, 该方法调用后sql中where部分会保留
	 * @param unknown $field count 字段
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias alias要选择的对象的别名，如果有联合查询；没有指定alias，则默认是直接类，也就是第一个调用的静态类，如TestModel::where()->Left_jion()->sum()中的TestModel
	 * @return int
	 */
	public function max($field, array $params=array(), $alias=null){
		$this->initSql();
		if ( ! $alias){
			$alias = "m";
		}
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		$this->sql->max($alias, $field, "MAX_ALIAS");

		$obj = YZE_DBAImpl::getDBA()->getSingle($this->sql, $params);
		$this->sql->clean_select();
		if ( ! $obj)return 0;
		$obj = is_array($obj) ? $obj[$alias] : $obj;
		return $obj ? $obj->Get("MAX_ALIAS") : 0;
	}
	/**
	 * 返回max结果, 该方法调用后sql中where部分会保留
	 * @param unknown $field count 字段
	 * @param array $params [:field=>value]格式的数组
	 * @param unknown $alias alias要选择的对象的别名，如果有联合查询；没有指定alias，则默认是直接类，也就是第一个调用的静态类，如TestModel::where()->Left_jion()->sum()中的TestModel
	 * @return int
	 */
	public function min($field, array $params=array(), $alias=null){
		$this->initSql();
		if ( ! $alias){
			$alias = "m";
		}
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		$this->sql->min($alias, $field, "MIN_ALIAS");

		$obj = YZE_DBAImpl::getDBA()->getSingle($this->sql, $params);
		$this->sql->clean_select();
		if ( ! $obj)return 0;
		$obj = is_array($obj) ? $obj[$alias] : $obj;
		return $obj ? $obj->Get("MIN_ALIAS") : 0;

	}

	public function delete(array $params=array(), $alias=null){
		$this->initSql();
		if ( ! $alias){
			$alias = "m";
		}
		if ( ! $this->sql->has_from()){
			$this->sql->from(static::CLASS_NAME, $alias ? $alias : "m");
		}
		$statement = YZE_DBAImpl::getDBA()->getConn()->prepare($this->sql->delete()->__toString());
		if(! $statement->execute($params) ){
		    throw new YZE_FatalException(join(",", $statement->errorInfo()));
		}
		$this->sql->clean_select();
		return $this;
	}
	public function clean(){
		$this->sql->clean();
	}
	public function clean_where(){
		$this->sql->clean_where();
	}
	/**
	 * 清空表中所有数据，主键重新从1开始
	 */
	public static function truncate(){
		$sql = "TRUNCATE `".YZE_DB_DATABASE."`.`".static::TABLE."`;";
		YZE_DBAImpl::getDBA()->exec($sql);
	}

	private function initSql($alias=null) {
		if ($this->sql == null){
			$this->sql = new YZE_SQL();
		}

		if ( ! $this->sql->has_from() && $alias){
		    $this->sql->from(static::CLASS_NAME, $alias);
		}
		return $this->sql;
	}

	private function getFieldType($field_name){
	    $columns = $this->get_columns();
	    return @$columns[$field_name]['type'];
	}


    public static function uuid() {
        $sql = "select uuid() as uuid";
        $rst = YZE_DBAImpl::getDBA()->nativeQuery($sql);
        $rst->next();
        return $rst->f("uuid");
    }

	/**
	 * 返回每个字段的具体的面向用户可读的含义，比如login_name表示登录名，
	 * 由子类实现
	 * @param $column
	 * @return mixed
	 */
    public function get_column_mean($column){
		return $column;
	}
}
?>
