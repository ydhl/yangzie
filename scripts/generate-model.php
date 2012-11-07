<?php
class Generate_Model_Script extends AbstractScript{
	private $methodlist;
	private $module_name;
	private $table_name;
	private $class_name;
	const USAGE = "根据表生成模型对象,用法:

  php generate.php -cmd model -mod module_name -tbl table_name -cls class_name   [-mtd methodlist]
    -cmd controller：命令名
    -mod module_name：模块名
    -tbl table_name：表名
    -cls class_name：Model 类名
    -mtd methodlist： 实体类中定义的方法名，逗号分隔，逗号之间不能有空格
    
    
";
	
	public function generate(){
		$argv = $this->args;
		
		while($argv){
			$option = strtolower(trim(array_shift($argv)));
			switch ($option){
				case '-mtd':
					$this->methodlist = trim(array_shift($argv));break;
				case '-mod':
					$this->module_name = array_shift($argv);break;
				case '-tbl':
					$this->table_name = array_shift($argv);break;
				case '-cls':
					$this->class_name = array_shift($argv);break;
				default:break;#忽略其它
			}
		}
		
		if(empty($this->module_name) || empty($this->table_name)  || empty($this->class_name) ){
			die(__(Generate_Model_Script::USAGE));
		}
		
		$path = dirname(dirname(__FILE__))."/app/modules/".$this->module_name;
		if(!file_exists($path)){
			die(__("module not exist, please generate first."));
		}
		
		//Model 
		$model_class = YangzieObject::format_class_name($this->class_name,"Model");
		$handleResult = $this->create_model_code($model_class);
		echo "create model :\t\t\t";
		$this->save_class($handleResult, $model_class, $this->module_name);
// 		echo "create model phpt file :\t";
// 		$this->save_test($handleResult, $model_class, $this->module_name);
		
		$class = YangzieObject::format_class_name($this->class_name,"");
		$handleResult = $this->create_class_code($class);
		echo "create object :\t\t\t";
		$this->save_class($handleResult,$class,$this->module_name);
		echo "create object phpt file :\t";
		$this->save_test($handleResult,$class,$this->module_name);
		echo "Done!\r\n";
	}
	
	
	public function create_class_code($class){
		$methoddefine='';
		$package=$this->module_name;
		foreach((array)$this->methodlist as $method){
			$methoddefine .= "
	/*
	 * @param
	 * @return
	 */
	public function $method(){
		//Write you code here
	}
";
		}
		
		$code = "<?php
/**
 *
 * @version \$Id\$
 * @package $package
 */
class $class{
	private \$model;
	
	public function $class(\$key){
		\$this->model = Model::find(\$key, '{$class}_Model');
	}
	
	$methoddefine
}?>";
	return $code;
	}
	
	public function create_model_code($class){
		$table = $this->table_name;
		$package=$this->module_name;
		$methodlist = $this->methodlist;
		
		$app_module = new App_Module();
		$db = mysql_connect(
			$app_module->get_module_config("db_host"),
			$app_module->get_module_config("db_user"),
			$app_module->get_module_config("db_psw")
		);
		mysql_select_db($app_module->get_module_config("db_name"),$db);
		$result = mysql_query("show full columns from $table",$db);
		
		if (!$result) {
			die($table . mysql_error($db)."\r\n");
		}
		$constant = array();
		while ($row=mysql_fetch_assoc($result)) {
			$row['Key']=="PRI" ? $key = $row['Field'] : null;
			$type_info = $this->get_type_info($row['Type']);
			$constant = array_merge((array)$constant,(array)$this->getEnumConstant($row['Type']));
			
			@$fielddefine .= "
	'".$row['Field']."'=>array(
		'type'		=> '".$type_info['type']."',
		'null'		=> ".(strcasecmp($row['Null'],"YES") ? "false" : "true").",
		'length'	=> '".$type_info['length']."',
		'default'	=> '".$row['Default']."',
		'comment'	=> '".$row['Comment']."'
	),";
			
		}
		
		$constantdefine = '';
		foreach($constant as $c=>$v){
			$constantdefine .= "
		const $v = '$c';";
		}
		$code = "<?php
/**
 * DO NOT MODIFY THIS FILE, THIS FILE IS AUTO GENERATE BY YDHL TOOL.
 * 
 * @version \$Id\$
 * @package $package
 */
class $class extends Model{
	$constantdefine
	protected \$table= \"$table\";
	protected \$version = 'modified_on';
	protected \$module_name = \"$package\";
	protected \$key_name = \"$key\";
	protected \$columns = array($fielddefine);
	
	private \$object;
	
	public function $class(){
		\$this->object = new ".preg_replace("/_Model$/", "", $class)."();
	}
}?>";
		return $code;
	}
	
	private function getEnumConstant($type){
		if(preg_match("/^enum\((?<v>.+)\)/",$type,$matches)){
			foreach(explode(",",$matches['v']) as $c){
				$c = trim($c,"'");
				$constant[$c] = strtr(strtoupper($c),array("-"=>"_"," "=>"_"));
			}
			return $constant;
		}
	}
	/**
	 * 
	 * 
	 * @author leeboo
	 * 
	 * @param unknown_type $type
	 * @return string
	 * 
	 * @return array('type','length')
	 */
	private function get_type_info($type){
		$ret = array("type"=>"","length"=>"");
		
		if(preg_match("/^int/i",$type)||
		preg_match("/^tinyint/i",$type)||
		preg_match("/^smallint/i",$type)||
		preg_match("/^mediumint/i",$type)||
		preg_match("/^bigint/i",$type)){
			if(preg_match("/\((\d+)\)/", $type, $matchs)){
				$ret['length']=@$matchs[1];
			}
			$ret['type']="integer";
			
			return $ret;
		}
		if(preg_match("/^decimal/i",$type)||
		preg_match("/^float/i",$type)||
		preg_match("/^double/i",$type)){
			if(preg_match("/\((\d+)\)/", $type, $matchs)){
				$ret['length']=@$matchs[1];
			}
			$ret['type']="float";
				
			return $ret;
		}
		if(preg_match("/^timestamp/",$type)||
		preg_match("/^date/",$type)||
		preg_match("/^datetime/",$type)||
		preg_match("/^time/",$type)||
		preg_match("/^year/",$type)){
			$ret['type']="date";
			return $ret;
		}
		if(preg_match("/^enum/",$type)){
			$ret['type']="enum";
			return $ret;
		}
		
		if(preg_match("/\((\d+)\)/", $type, $matchs)){
			$ret['length']=@$matchs[1];
		}
		$ret['type']="string";
		return $ret;
	}
	
	
	private function save_test($handleResult,$class,$package){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/tests/".$package;
		$this->check_dir($path);
			
		$class_file_path = dirname(dirname(__FILE__))
			."/tests/". $package."/" ."{$class}.class.phpt";

		$test_file_content = "--TEST--
	$class class Model Unit Test
--FILE--
<?php
	ini_set(\"display_errors\",0);
	chdir(dirname(dirname(dirname(__FILE__))).\"/app/public_html\");
	include \"init.php\";
	include \"load.php\";
	//write you test code here
?>
--EXPECT--
	";
		
		$this->create_file($class_file_path, $test_file_content);
	}

	private function save_class($handleResult,$class,$package){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/app/modules/".$package."/models";
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/".$package);
		$this->check_dir($path);
	
			
		$class_file_path = dirname(dirname(__FILE__))
			."/app/modules/".$package."/models/{$class}.class.php";
		$this->create_file($class_file_path, $handleResult);
	}
	
}
?>
