<?php
namespace yangzie;

class YZE_MongoModel{
	protected static $mongo;
	protected static $memcache;
	
	/**
	 * 降序
	 * @var number
	 */
	const ORDER_DESC = -1;
	/**
	 * 升序 
	 * @var number
	 */
	const ORDER_ASC = 1; 
	
	/**
	 * 
	 * @var MongoDB
	 */
	protected static $db;
	protected static $table="";
	public $_id;
	
	private static function init(){
		//if(YZE_DB_USER) $this->mongo =  new \MongoClient("mongodb://".YZE_DB_USER.":".YZE_DB_PASS."@".YZE_DB_HOST_M."/".YZE_DB_NAME);
		try{
			if(YZE_MONGODB_HOST_M && !static::$mongo) {
				if(YZE_MONGODB_USER){
					static::$mongo =  new \MongoClient("mongodb://".YZE_MONGODB_USER.":".YZE_MONGODB_PASS."@".YZE_MONGODB_HOST_M.":".YZE_MONGODB_PORT."/".YZE_MONGODB_NAME);
				}else{
					static::$mongo =  new \MongoClient("mongodb://".YZE_MONGODB_HOST_M.":".YZE_MONGODB_PORT);
					
				}
			}
			static::$db = static::$mongo->selectDB(YZE_MONGODB_NAME);
		}catch(\Exception $e){
			throw new YZE_FatalException($e->getMessage());
		}
	}
	public function __construct(){
		self::init();
	}
	
	/**
	 * 查询多条数据，跟findMore不同的是，返回MongoCursor;如果要获取对象，则可以通过XXModel::buildFromArray(MongoCursor::getNext());
	 * 获取
	 * @param array $query
	 * @param array $fields
	 * @return MongoCursor 
	 */
	public static function search(array $query = array() , array $fields = array()){
		static::init();
		
		$table = static::$db->selectCollection (static::$table);
		return $table->find($query, $fields);
	}

	/**
	 * 将游标转成相应的对象数组
	 * @param MongoCursor $cursor
	 * @return multitype:NULL
	 */
	public static function warpCursor ( $cursor ) {
		$objs = array ();
		$class = get_called_class();
		foreach ( $cursor as $c ) {
			$objs [] = $class::buildFromArray ( $c ); // 返回对象
		}
		return $objs;
	}
	
	/**
	 * 查询多条数据
	 * @param array $query
	 * @param array $fields
	 * @return array 如果指定了查询字段，则指返回这些字段的结果数组；如果没有指定查询字段，则返回对象的数组
	 */
	public static function findMore( array $query = array() , array $fields = array()){
		static::init();
		
		$table = static::$db->selectCollection (static::$table);
		$objects = $table->find($query, $fields);
		if( ! $objects)return array();
		if($fields){
			return $objects;
		}
		
		$objs = array();
		foreach ($objects as $object){
			$objs[] = static::buildFromArray($object);
		}
		
		return $objs;
	}
	
	/**
	 * 返回满足条件的总数
	 * 
	 * @param array $query 
	 * @param int $limit 指定返回数量的上限。
	 * @param int $skip 指定在开始统计前，需要跳过的结果数目。
	 * @return int
	 */
	public static function count(array $query = array(),  $limit = 0 , $skip = 0){
		static::init();
		$table = static::$db->selectCollection (static::$table);
		return $table->count($query, $limit, $skip);
	}
	
	/**
	 * 查找对象，如果不传入第二个参数，则返回对象，如果传入第二个对象，则返回数组
	 * @param array $query
	 * @param array $fields
	 * @param bool $isSingle 默认只返回一条数据，false返回多条数据
	 * @return YZE_MongoModel|array
	 */
	public static function find( array $query = array() , array $fields = array(), $isSingle=true ){
		if( ! $isSingle)return self::findMore($query, $fields);
		static::init();
		$class = get_called_class();
		$table = static::$db->selectCollection (static::$table);
		$object = $table->findOne($query, $fields);
		if( ! $object)return null;
		if($fields){
			return $object;
		}
		return $class::buildFromArray($object);
	}
	/**
	 * 
	 * 如果设置了 "w" 选项，将会返回包含删除状态的 array。 否则返回 TRUE
	 * $options
	 * 
	 * "w"
	 * 
	 * See WriteConcerns. The default value for MongoClient is 1.
	 * 
	 * "justOne"
	 * 
	 * 最多只删除一个匹配的记录。
	 * 
	 * "fsync"
	 * 
	 * Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0.
	 * 
	 * "j"
	 * 
	 * Boolean, defaults to FALSE. Forces the write operation to block until it is synced to the journal on disk. If TRUE, an acknowledged write is implied and this option will override setting "w" to 0.
	 * 
	 * Note: If this option is used and journaling is disabled, MongoDB 2.6+ will raise an error and the write will fail; older server versions will simply ignore the option.
	 * 
	 * "w"
	 * 
	 * See WriteConcerns. The default value for MongoClient is 1.
	 * 
	 * "timeout"
	 * 
	 * Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response. If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.
	 * 
	 * @param array $criteria 待删除记录的描述。
	 * @param array $options 删除的选项。
	 * @return array |bool
	 */
	public static function remove(  array $criteria = array() , array $options = array() ){
		static::init();
		$table = static::$db->selectCollection (static::$table);
		return $table->remove($criteria, $options);
	}
	
	/**
	 * 删除对象自己；
	 * 删除后对象的id变成null，数据库中进行实际删除
	 */
	public function removeMe(){
		$table = static::$db->selectCollection (static::$table);
		$rst = $table->remove(array("_id"=>new \MongoId($this->_id)), array("justOne"=>true));
		$this->_id = null;
		return $rst;
	}
	/**
	 * 插入新记录或者更新记录（看_id是否存在）
	 * @param array $a 不传的话，插入对象自己
	 */
	public function save( $a=null){
		$data = $a;
		if($a==null){
			$data = get_object_vars($this);
			unset($data['mongo'], $data['memcache'], $data['table'], $data['db'], $data['_id']);
		}
		$table = self::$db->selectCollection ($this::$table);
		if($this->_id){
			$table->update(array("_id"=>$this->_id),  array('$set' =>$data));
		}else{
			$table->insert($data);
			$this->_id = $data['_id'];
		}
		return $this;
	}
	
	public static function buildFromArray($array){
		if( ! $array)return null;
		$cls = get_called_class();
		$obj = new $cls();
		foreach ($array as $name=>$value){
			$obj->$name = $value;
		}
		return $obj;
	}
}
