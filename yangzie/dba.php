<?php
namespace yangzie;
use \PDO;
use \PDOStatement;
use \app\App_Module;

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

		$this->conn =  new PDO(
				'mysql:dbname='.$app_module->get_module_config('db_name').';port='.$app_module->get_module_config('db_port').';host='.$app_module->get_module_config('db_host'),
				$app_module->get_module_config('db_user'),
				$app_module->get_module_config('db_psw')
		);
		$this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
		$this->conn->query('SET NAMES '.$app_module->get_module_config('db_charset'));
	}
	
	public function getConn(){
		return $this->conn;
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
	
	public function quote($value){
	    return $this->conn->quote($value);
	}

	public function find_by(array $ids, $class)
	{
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}

		$entity = $class instanceof YZE_Model ? $class : new $class;

		$sql = new YZE_SQL();
		$sql->from(get_class($entity),"a")
		->where("a",$entity->get_key_name(),YZE_SQL::IN,$ids);

		return $this->select($sql);
	}

	/**
	 * 根据主键查询记录，返回实体
	 * $key为查询表的主键，如果是复合主键，$key为数组，键为字段名。如:array('qo_item_id'=>123,'part_item_id'=>456)
	 * @param unknown_type $key
	 * @param string|Model $class
	 * @return YZE_Model
	 */
	public function find($key,$class){
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}

		$entity = $class instanceof YZE_Model ? $class : new $class;

		$sql = new YZE_SQL();
		$sql->from(get_class($entity),"a")->limit(1);

		$sql->where("a",$entity->get_key_name(),YZE_SQL::EQ,$key);
		$rst = $this->select($sql);
		return @$rst[0];
	}
	/**
	 * 查询所有的记录，返回实体数组,键为主键值
	 * @param string|Model $class
	 * @return array(YZE_Model)
	 */
	public function findAll($class){
		if(!($class instanceof YZE_Model) && !class_exists($class)){
			throw new YZE_DBAException("Model Class $class not found");
		}
		$entity = $class instanceof YZE_Model ? $class : new $class;
		$sql = new YZE_SQL();
		$sql->from(get_class($entity),"t");
		return $this->select($sql);
	}
	/**
	 * 原生查询，不返回对象，返回结果数组，如果不是ddl，返回影响的行数
	 * 返回的结果数据由DBMySQLWrapper封装,它封装了DBMysql类
	 * @see ResultWrapper
	 * @param YZE_SQL $class
	 * @return ResultWrapper
	 */
	public function nativeQuery(YZE_SQL $sql){
		return $this->nativeQuery2($sql->__toString());
	}
	
	public function nativeQuery2($sql){
	    $pdo = $this->conn->query($sql);
	    if( ! $pdo){
	        throw new YZE_DBAException("sql error " . $sql . ":" . join(",",$this->conn->errorInfo()));
	    }
		return new YZE_PDOStatementWrapper($pdo);
	}
	/**
	 * 同select，区别是直接返回一个对象
	 * @param YZE_SQL $sql
	 * @param array $params :column类型的字段值
	 * @return YZE_Model
	 */
	public function getSingle(YZE_SQL $sql, $params=array()){
		$sql->limit(1);
		$result = $this->select($sql, $params);
		return @$result[0];
	}

	/**
	 * 根据条件查询所有的记录，返回实体数组,
	 * !!!如果是联合查询，没有数据的对象返回null
	 *
	 * @param YZE_SQL $sql
	 * @param string $key_field 返回的数组的索引，没有指定则是数字自增，指定指定名，则以该字段的值作为索引
	 * @param array $params :column类型的字段值
	 * 
	 * @return array(Model)
	 */
	public function select(YZE_SQL $sql, $params=array(), $key_field=null){
		$classes = $sql->get_select_classes(true);
		
		if($params){
			$statement = $this->conn->prepare($sql->__toString());
			$statement->execute($params);
		}else{
			$statement = $this->conn->query($sql->__toString());
		}
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
	 * @param YZE_Model $entity
	 * @return int 返回操作成功的记录数
	 */
	public function delete(YZE_Model $entity){
		$sql = new YZE_SQL();
		$sql->delete()->from(get_class($entity),"t");
		$sql->where("t",$entity->get_key_name(),YZE_SQL::EQ,$entity->get_key());
		$affected_row = $this->execute($sql);
		if($affected_row){
			$entity->delete_key();
		}
		\yangzie\YZE_Hook::do_hook(YZE_HOOK_MODEL_DELETE, $entity);
		return $entity;
	}
	/**
	 * 执行YZE_SQL更改语句，返回影响的记录，出错报出 YZE_DBAException
	 * @param YZE_SQL $sql
	 * @return integer
	 */
	public function execute(YZE_SQL $sql){
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
	
    
	private function _save_update(YZE_Model $entity){
	    \yangzie\YZE_Hook::do_hook(YZE_HOOK_MODEL_UPDATE, $entity);
	    $sql = new YZE_SQL();
	    //自动把version更新
	    $entity->update_version();
	    $sql->update('t',$entity->get_records())
	    ->from(get_class($entity),"t");
	    $sql->where("t",$entity->get_key_name(),YZE_SQL::EQ,$entity->get_key());
	    $this->execute($sql);
	    return $entity->get_key();
	}
	
	/**
	 * 保存(update,insert)记录；如果有主键，则更新；没有则插入；
	 * 插入情况，根据$type进行不同的插入策略
	 * INSERT_NORMAL：普通插入语句
	 * INSERT_NOT_EXIST： 指定的where条件查询不出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_NOT_EXIST_OR_UPDATE： 指定的$checkSql条件查询不出数据时才插入, 查询数据则更新这条数据；如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_EXIST： 指定的$checkSql条件查询出数据时才插入，如果插入、更新成功，会返回主键值，如果插入失败会返回0，这是的entity->get_key()返回0
	 * INSERT_ON_DUPLICATE_KEY_UPDATE： 有唯一健冲突时更新其它字段
	 * INSERT_ON_DUPLICATE_KEY_REPLACE： 有唯一健冲突时先删除原来的，然后在插入
	 * INSERT_ON_DUPLICATE_KEY_IGNORE： 有唯一健冲突时忽略，不抛异常
	 * @param string $sql 完整的判断查询sql
	 * @param YZE_Model $entity
	 * @param string $type YZE_SQL::INSERT_XX常量
	 * @throws YZE_DBAException
	 * @return string
	 */
	public function save(YZE_Model $entity, $type=YZE_SQL::INSERT_NORMAL, YZE_SQL $checkSql=null){
		if(empty($entity)){
			throw new YZE_DBAException("save YZE_Model is empty");
		}
		
		if($entity->get_key()){//update
			return $this->_save_update($entity);
		}
		$sql = new YZE_SQL();
		$extra_info = $type==YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE ? array_keys($entity->get_unique_key()) : $checkSql;
		//insert
		$sql->insert('t',$entity->get_records(), $type, $extra_info)
		->from(get_class($entity),"t");
		
		$rowCount = $this->execute($sql);
		$insert_id = $this->conn->lastInsertId();
		
		if($type == YZE_SQL::INSERT_EXIST || $type == YZE_SQL::INSERT_NOT_EXIST){
		    if( $rowCount ){
		      //这种情况下last insert id 得不到?
		        $entity->set($entity->get_key_name(), $insert_id);
		    }
		}elseif($type == YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE){
		    if( ! $rowCount ){
		        $alias = $checkSql->get_alias($entity->get_table());
		        $checkSql->update($alias, $entity->get_records());
		        $this->execute($checkSql);
		        $checkSql->select($alias, array($entity->get_key_name()));
		        $obj = $this->getSingle($checkSql);
		        $insert_id = $obj->get_key();
		    }
		    $entity->set($entity->get_key_name(), $insert_id);
		}else if($type==YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE){
		    //0 not modified, 1 insert, 2 update
		    if($rowCount==2 && count($entity->get_unique_key())>1){
		        $records = $entity->get_records();
		        $entity->refresh();
		        //当$update_on_duplicate_key是考虑有多个唯一健的更新情况；可能会由于某个唯一值冲突，导致其它唯一值没有更新的情况
		        //所以这里在update一下
		        foreach ($entity->get_unique_key() as $field){
		            $entity->set($field, $records[$field]);
		        }
		        $entity->save();
		    }
		}else if($type==YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE){
		    $insert_id = 0;
		}
		
		$entity->set($entity->get_key_name(),$insert_id);
		
		\yangzie\YZE_Hook::do_hook(YZE_HOOK_MODEL_INSERT, $entity);
		return $insert_id;
	}
	
	/**
	 * 是否开启自动提交
	 * @param unknown $boolean
	 */
	public function autoCommit($boolean){
	    if($this->conn){
	       $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, $boolean ? 1 : 0);
	    }
	}

	public function beginTransaction(){
		try{
			if($this->conn)$this->conn->beginTransaction();
		}catch(\Exception $e){}
	}
	public function commit(){
		try{
			if($this->conn)$this->conn->commit();
		}catch(\Exception $e){}
	}
	public function rollBack(){
		try{
		if($this->conn)$this->conn->rollBack();
		}catch(\Exception $e){}
	}

	private function _build_entity(YZE_Model $entity,$raw_datas,$select_tables){
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
	/**
	 * 查字段
	 * @param string $field 字段名
	 * @param string $table 表
	 * @param string $where "a=:b and c=:d"
	 * @param array $values array(":b"=>"",":d"=>)
	 * @return unknown
	 */
	public function lookup($field, $table, $where, array $values=array()) {
	    $sql = "SELECT $field as f FROM `{$table}` WHERE {$where}";
	    $stm = $this->conn->prepare($sql);
	    if(@$stm->execute($values)){
	        $row = $stm->fetch(PDO::FETCH_ASSOC);
	        return @$row['f'];
	    }
	    return null;
	}
	
	/**
	 * 查询结果集，返回满足条件的一条结果数组
	 * 
	 * @param string $fields
	 * @param string $table
	 * @param string $where
	 * @param array $values
	 * @return array
	 */
	public function lookup_record($fields, $table, $where="", array $values=array()) {
	    $sql = "SELECT {$fields} FROM {$table}".($where ? " WHERE {$where}" :"");
	    $stm = $this->conn->prepare($sql);
	    if($stm->execute($values)){
	       return $stm->fetch(PDO::FETCH_ASSOC);
	    }
	    
	    return array();
	}
	
	/**
	 * 查询结果集，返回所有结果数组
	 * 
	 * @param string $fields
	 * @param string $table
	 * @param string $where
	 * @param array $values
	 * @return array
	 */
	public function lookup_records($fields, $table, $where="", array $values=array()) {
	    $sql = "SELECT $fields FROM $table";
	    if ($where) $sql .= " WHERE $where";
	    $stm = $this->conn->prepare($sql);
	    if($stm->execute($values)){
	       return $stm->fetchAll(PDO::FETCH_ASSOC);
	    }
	    return array();
	}
	
	/**
	 * 更新记录，返回受影响的行数
	 * @param string $table
	 * @param string $fields
	 * @param string $where
	 * @param array $values
	 * @return boolean|Ambigous <boolean, NULL, string>
	 */
	public function update($table, $fields, $where, array $values=array()) {
	    $sql = "UPDATE $table SET $fields";
	    if ($where) $sql .= " WHERE $where";
	
	    $stm = $this->conn->prepare($sql);
	    if ( ! $stm ) return false;
	    return $stm->execute($values);
	}
	
	/**
	 * 删除记录返回受影响的行数
	 * @param string $table
	 * @param string $where
	 * @param array $values
	 * @return boolean
	 */
	public function deletefrom($table, $where, array $value=array()) {
	    $sql = "DELETE FROM $table";
	    if ($where) $sql .= " WHERE $where";
	    $stm = $this->conn->prepare($sql);
	    if ( ! $stm ) return false;
	    return $stm->execute($values);
	}
	/**
	 * 插入记录; 成功返回新记录的主键
	 *
	 * @param string $table
	 * @param array $info array("field"=>"value");
	 * @param string $checkSql 检查的表
	 * @param array $checkInfo array(":field"=>"value");检查表的条件子
	 * @param boolean $exist true，表示存在是插入；false，表示不存在时插入
	 * @param boolean $update 是否在存在是更新
	 * @param string $key table 主键名称
	 * @return boolean|unknown
	 */
	public function checkAndInsert($table, $info, $checkSql, $checkInfo, $exist=false, $update=false,$key="id") {
	    $sql_fields     = "";
	    $sql_values     = "";
	    $set            = "";
	    $values         = $checkInfo;
	    foreach ($info as $f => $v) {
	        $sql_fields  .= "`" . $f . "`,";
	        $sql_values  .= ":" . $f . ",";
	        $set  .= "`{$f}`=:{$f},";
	        $values[":" . $f] = $v;
	    }
	    $sql_fields  = rtrim($sql_fields, ",");
	    $sql_values  = rtrim($sql_values, ",");
	    $set         = rtrim($set, ",");
	
	    $sql = "INSERT INTO `{$table}` ({$sql_fields}) SELECT {$sql_values} FROM dual WHERE ".($exist?"":"NOT")." EXISTS ({$checkSql})";
	    $stm = $this->conn->prepare($sql);
	    $stm->execute($values);
	    if ( !  $stm->rowCount()) {
	        if( ! $update){
	            return false;
	        }
	        $where = preg_replace("/^.+where/", "", $checkSql);
	        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";
	        $stm = $this->conn->prepare($sql);
	        if (! $stm->execute($values) ){
	            return false;
	        }
	        return $this->lookup($key, $table, $where, $values);
	    }
	     
	    return $this->conn->lastInsertId();
	}
	/**
	 * 插入记录; 成功返回新记录的主键
	 * 
	 * @param string $table
	 * @param array $info array("field"=>"value");
	 * @param array $duplicate_key array("field0","field1"); 指定表的唯一字段，如果指定，则会生成INSET INTO ON DUPLICATE KEY UPDATE 语句，
	 * 再指定的字段有唯一健冲突时执行更新
	 * @param $keyname 表主键名称 指定了$duplicate_key一定要设置
	 * @return boolean|unknown 成功返回主键，失败返回false
	 */
	public function insert($table, $info, $duplicate_key=array(), $keyname="") {
	    if ( ! is_array($info) || empty($info) || empty($table))
	        return false;
	
	
	    $sql_fields     = "";
	    $sql_values     = "";
	    $values         = array();
	    $update         = array();
	    foreach ($info as $f => $v) {
	        $sql_fields  .= "`" . $f . "`,";
	        $sql_values  .= ":" . $f . ",";

	        $values[":" . $f] = $v;
	        if(array_search($f, $duplicate_key) === false){
	            $update[] = "`{$f}`=VALUES(`{$f}`)";
	        }
	    }
	    $sql_fields  = rtrim($sql_fields, ",");
	    $sql_values  = rtrim($sql_values, ",");
	
	    $sql = "INSERT INTO {$table} ({$sql_fields}) VALUES ({$sql_values})";
	    if($duplicate_key){
	        $sql .= " ON DUPLICATE KEY UPDATE {$keyname} = LAST_INSERT_ID({$keyname}), 
	        ".join(",", $update);
	    }
	
	    $stm = $this->conn->prepare($sql);
	    if ( ! $stm->execute($values) ) {
	        return false;
	    }
	    
	    return $this->conn->lastInsertId();
	}
	/**
	 * 插入记录, 在有为一件冲突是忽略插入，成功返回新记录的主键
	 *
	 * @param string $table
	 * @param array $info array("field"=>"value");
	 * @param array $duplicate_key array("field0","field1"); 指定表的唯一字段，如果指定，则会生成INSET INTO ON DUPLICATE KEY UPDATE 语句，
	 * 再指定的字段有唯一健冲突时执行更新
	 * @param $keyname 表主键名称 指定了$duplicate_key一定要设置
	 * @return boolean|unknown
	 */
	public function insertOrIgnore($table, $info) {
	    $sql_fields     = "";
	    $sql_values     = "";
	    $values         = array();
	    $update         = array();
	    foreach ($info as $f => $v) {
	        $sql_fields  .= "`" . $f . "`,";
	        $sql_values  .= ":" . $f . ",";
	
	        $values[":" . $f] = $v;
	    }
	    $sql_fields  = rtrim($sql_fields, ",");
	    $sql_values  = rtrim($sql_values, ",");
	
	    $sql = "INSERT IGNORE INTO {$table} ({$sql_fields}) VALUES ({$sql_values})";
	
	    $stm = $this->conn->prepare($sql);
	    if ( ! $stm->execute($values) ) {
	    return false;
	    }
	     
	    return $this->conn->lastInsertId();
	}
	
	/**
	 * 插入记录, 在有为一件冲突是忽略插入替换，成功返回新记录的主键
	 *
	 * @param string $table
	 * @param array $info array("field"=>"value");
	 * @param array $duplicate_key array("field0","field1"); 指定表的唯一字段，如果指定，则会生成INSET INTO ON DUPLICATE KEY UPDATE 语句，
	 * 再指定的字段有唯一健冲突时执行更新
	 * @param $keyname 表主键名称 指定了$duplicate_key一定要设置
	 * @return boolean|unknown
	 */
	public function replace($table, $info) {
	    $sql_fields     = array();
	    $values         = array();
	    foreach ($info as $f => $v) {
	        $sql_fields[] = "`{$f}`=:{$f}";
	
	        $values[":" . $f] = $v;
	    }
	    $sql_fields  = join(",", $sql_fields);
	
	    $sql = "REPLACE INTO {$table} SET {$sql_fields}";
	
	    $stm = $this->conn->prepare($sql);
	    if ( ! $stm->execute($values) ) {
	        return false;
	    }
	
	    return $this->conn->lastInsertId();
	}
	
	public function table_fields($table) {
	    $sql="show columns from $table";
	    $stm=$this->conn->query($sql);
	
	    $fileds = array();
	    if($stm){
	        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	            $fileds[] = $row['Field'];
	        }
	    }
	
	    return $fileds;
	}
	
	/**
	 * CODE TO DB同步，把指定的model同步字段到数据库，如果表没有建立则建立表，建立就同步字段
	 * 这是初步实现了功能，不稳定，请勿使用在产品环境
	 * 
	 * @param unknown $class
	 */
	public static function migration($class, $reCreate=false){
		$columns = $class::$columns;
		$table = $class::TABLE;
		$column_segments = array();
		
		$uniqueKey = "";
		
		foreach ($columns as $column => $defines){
			switch (strtoupper($defines['type'])){
				case "INTEGER": $type = "INT";break;
				case "TIMESTAMP": $type = "TIMESTAMP";break;
				case "DATE": $type = "date";break;
				case "FLOAT": $type = "FLOAT";break;
				case "ENUM": $type = "ENUM";break;
				case "STRING": 
				default : $type = "VARCHAR(".(@$defines['length'] ? $defines['length'] : 45).")";break;
			}
			
			$uniqueKey .= $defines["unique"] ? ",UNIQUE INDEX `{$column}_UNIQUE` (`{$column}` ASC)" : "";
			$nullable = $defines["null"] ? "NULL" : "NOT NULL";
			$primaryID = $column==$class::KEY_NAME ? "AUTO_INCREMENT" : "";
			$default  = $defines["default"] != '' ? "DEFAULT ".$defines["default"] : "";
			
			$column_segments[] = "`{$column}` {$type} {$nullable} {$primaryID} {$default}";
		}
		$primary = "";
		if ($class::KEY_NAME){
			$primary = " , PRIMARY KEY (`".$class::KEY_NAME."`)";
		}
		if ($reCreate){
			$drop = "DROP TABLE `".YZE_MYSQL_DB."`.`{$table}`";
		}
		
		
		$sql = "CREATE TABLE IF NOT EXISTS `".YZE_MYSQL_DB."`.`{$table}` (".join(",", $column_segments)."{$primary}{$uniqueKey})
		ENGINE = InnoDB;";
		
		if ($drop){
			self::getDBA()->exec($drop);
		}
		
		self::getDBA()->exec($sql);
	}
	
}
class YZE_PDOStatementWrapper extends YZE_Object{
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
	public function reset(){
		$this->index = -1;
	}
	public function next(){
		$this->index +=1;
		return @$this->result[$this->index];
	}
	/**
	 * 如果提供了alias,则会已{$table_alias}_{$name}为字段名查找
	 * @param unknown $name
	 * @param unknown $table_alias
	 */
	public function f($name,$table_alias=null){
		return self::filter_var($this->result[$this->index][$table_alias ? "{$table_alias}_{$name}" : $name]);#数据库取出来编码
	}
	

	public function getEntity(YZE_Model $entity, $alias=""){
	   foreach (array_keys($entity->get_columns()) as $field_name) {
            $field_value = $this->f($field_name, $alias);
            if (is_null($field_value)) {
                continue ;
            }
            $entity->set( $field_name , $field_value);#数据库取出来编码
        }
	    return $entity;
	}
}
?>