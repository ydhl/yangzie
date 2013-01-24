<?php
/**
 * 与数据库进行交互DatabaseAdvisor接口，负责对数据库的crud操作，并且返回model，
 * 以及到事务的支持。
 * @author liizii
 *
 */
class YZE_DBAImpl extends YZE_Object
{
	private $conn;
	private static $me;

	private function __construct(){
		if($this->conn)return $this->conn;
		$app_module = new App_Module();

		if(!$app_module->db_name)return;

		$this->conn =  new \PDO(
				'mysql:dbname='.$app_module->get_module_config('db_name').';host='.$app_module->get_module_config('db_host'),
				$app_module->get_module_config('db_user'),
				$app_module->get_module_config('db_psw')
		);
		$this->conn->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
		$this->conn->query('SET NAMES '.$app_module->get_module_config('db_charset'));
	}

	/**
	 * @return YZE_DBAImpl
	 */
	public static function getDBA(){
		if(!isset(self::$me)){
			$c = __CLASS__;
			self::$me = new $c;
		}
		return self::$me;
	}


	public function find_by(array $ids, $class)
	{
		if(!($class instanceof Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}

		$entity = $class instanceof Model ? $class : new $class;

		$sql = new SQL();
		$sql->from(get_class($entity),"a")
		->where("a",$entity->get_key_name(),SQL::IN,$ids);

		return $this->select($sql);
	}

	/**
	 * 根据主键查询记录，返回实体
	 * $key为查询表的主键，如果是复合主键，$key为数组，键为字段名。如:array('qo_item_id'=>123,'part_item_id'=>456)
	 * @param unknown_type $key
	 * @param string|Model $class
	 * @return Model
	 */
	public function find($key,$class){
		if(!($class instanceof Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}

		$entity = $class instanceof Model ? $class : new $class;

		$sql = new SQL();
		$sql->from(get_class($entity),"a")->limit(1);

		$sql->where("a",$entity->get_key_name(),SQL::EQ,$key);
		$rst = $this->select($sql);
		return @$rst[0];
	}
	/**
	 * 查询所有的记录，返回实体数组,键为主键值
	 * @param string|Model $class
	 * @return array(Model)
	 */
	public function findAll($class){
		if(!($class instanceof Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}
		$entity = $class instanceof Model ? $class : new $class;
		$sql = new SQL();
		$sql->from(get_class($entity),"t");
		return $this->select($sql);
	}
	/**
	 * 原生查询，不返回对象，返回结果数组，如果不是ddl，返回影响的行数
	 * 返回的结果数据由DBMySQLWrapper封装,它封装了DBMysql类
	 * @see ResultWrapper
	 * @param SQL $class
	 * @return ResultWrapper
	 */
	public function nativeQuery(SQL $sql){
		return $this->nativeQuery2($sql->__toString());
	}
	public function nativeQuery2($sql){
		return new PDOStatementWrapper($this->conn->query($sql));
	}
	/**
	 * 同select，区别是直接返回一个对象
	 * @param SQL $sql
	 * @return Model
	 */
	public function getSingle(SQL $sql){
		$sql->limit(1);
		$result = $this->select($sql);
		return @$result[0];
	}

	/**
	 * 根据条件查询所有的记录，返回实体数组,
	 * !!!如果是联合查询，没有数据的对象返回null
	 *
	 * @param SQL $sql
	 * @return array(Model)
	 */
	public function select(SQL $sql){
		$classes = $sql->get_select_classes(true);
		$statement = $this->conn->query($sql->__toString());
		if(empty($statement)){
			throw new YZE_DBAException(join(",", $this->conn->errorInfo()));
		}
		$raw_result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$num_rows = $statement->rowCount();
		$more_entity = count($classes) > 1;
		$entity_objects = array();
		for($i=0;$i<$num_rows;$i++){#所有的对象
			foreach($classes as $cls){
				if($more_entity){
					$e = new $cls();
					$entity_objects[$i][$sql->get_alias($e->get_table())] = $e;
				}else{
					$entity_objects[$i] = new $cls();
				}
			}
		}
		$select_tables = $sql->get_select_table();
		$entities = array();
		$row=0;
		while ($raw_result){
			$raw_row_data = array_shift($raw_result);#每行
			if($more_entity){
				foreach($entity_objects[$row] as &$entity){
					$this->_build_entity($entity,
							$raw_row_data,
							$select_tables);
				}
			}else{
				$this->_build_entity($entity_objects[$row],
						$raw_row_data,
						$select_tables);
			}
			$row++;
		}
		//把没有数据的对象设置为null
		$objects = array();
		foreach ($entity_objects as $index => $o) {
			if (is_array($o)) {
				foreach ($o as $n => $v) {
					$objects[$index][$n] = $v && $v->get_records() ? $v : null;
				}
			}else{
				$objects[$index] = $o && $o->get_records() ? $o : null;
			}
		}
		return $objects;
	}
	/**
	 * 根据条件删除记录
	 * @param Model $entity
	 * @return int 返回操作成功的记录数
	 */
	public function delete(Model $entity){
		$sql = new SQL();
		$sql->delete()->from(get_class($entity),"t");
		$sql->where("t",$entity->get_key_name(),SQL::EQ,$entity->get_key());
		$affected_row = $this->execute($sql);
		if($affected_row){
			$entity->delete_key();
		}
		do_action("db-delete", $entity);
		return $entity;
	}
	/**
	 * 执行SQL更改语句，返回影响的记录，出错报出 YZE_DBAException
	 * @param SQL $sql
	 * @return array(Model)
	 */
	public function execute(SQL $sql){
		if(empty($sql))return false;
		$affected = $this->conn->exec($sql->__toString());
		if ($affected===false) {
			throw new YZE_DBAException(join(", ", $this->conn->errorInfo()));
		}
		return $affected;
	}

	public function exec($sql){
		if(empty($sql))return false;
		$affected = $this->conn->exec($sql);
		if ($affected===false) {
			throw new YZE_DBAException(join(", ", $this->conn->errorInfo()));
		}
		return $affected;
	}
	/**
	 * 保存(update,insert)记录
	 * @param Model $entity
	 * @return int 返回操作成功的实体的主键
	 */
	public function save(Model $entity){
		if(empty($entity)){
			throw new YZE_DBAException("save Model is empty");
		}
		$sql = new SQL();
		if($entity->get_key()){//update
			do_action("db-update", $entity);
				
			//自动把version更新
			$entity->update_version();
			$sql->update('t',$entity->get_records())
			->from(get_class($entity),"t");
			$sql->where("t",$entity->get_key_name(),SQL::EQ,$entity->get_key());
			$this->execute($sql);
			return $entity->get_key();
		}else{//insert
				
			$sql->insert('t',$entity->get_records())
			->from(get_class($entity),"t");
			$this->execute($sql);
			$insert_id = $this->conn->lastInsertId();
				
			if(empty($insert_id)){
				throw new YZE_DBAException(join(", ", $this->conn->errorInfo()));
			}
				
			$entity->set($entity->get_key_name(),$insert_id);
			do_action("db-insert", $entity);
			return $insert_id;
		}
		return false;
	}

	public function beginTransaction(){
		if($this->conn)$this->conn->beginTransaction();
	}
	public function commit(){
		if($this->conn)$this->conn->commit();
	}
	public function rollBack(){
		if($this->conn)$this->conn->rollBack();
	}

	private function _build_entity(Model $entity,$raw_datas,$select_tables){
		foreach ($raw_datas as $field_name => $field_value) {
			//如果从数据库中取出来的值为null，则不用对相应的对象属性赋值，因为默认他们就是null。
			//而赋值后再同步到数据库的时候，这些null值会被处理成''，0，如果字段是外键就会出错误，看_quoteValue
			if (is_null($field_value)) {
				continue ;
			}
			$alias = array_search($entity->get_table(), $select_tables)."_";
			if (substr($field_name, 0, strlen($alias)) !== $alias) {
				continue;//当前字段不属于$entity
			}
			$field_name = substr($field_name, strlen($alias));
			if (/*$entity->has_column($field_name) && */!$entity->has_set_value($field_name)) {
				$entity->set( $field_name , self::filter_var($field_value));#数据库取出来编码
			}
		}
	}
}
class PDOStatementWrapper extends YZE_Object{
	/**
	 * @var PDOStatement
	 */
	private $db;
	private $result;
	private $index = -1;
	public function __construct(PDOStatement $db_mysql){
		$this->db = $db_mysql;
		$this->result = $this->db->fetchAll(PDO::FETCH_ASSOC);
	}

	public function next(){
		$this->index +=1;
		return @$this->result[$this->index];
	}
	public function f($name,$table_alias=null){
		return self::filter_var($this->result[$this->index][$table_alias ? "{$table_alias}_{$name}" : $name]);#数据库取出来编码
	}
}
?>