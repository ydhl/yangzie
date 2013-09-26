<?php
namespace yangzie;

class Generate_Model_Script extends AbstractScript{
	private $base;
	private $module_name;
	private $table_name;
	private $class_name;
	
	public function generate(){
		$argv = $this->args;
		$this->base 				= $argv['base'];
		$this->module_name 	= $argv['module_name'];
		$this->table_name 		= $argv['table_name'];
		$this->class_name 		= $argv['class_name'];
		
		if(empty($this->module_name) || empty($this->table_name)  || empty($this->class_name) ){
			die(__(Generate_Model_Script::USAGE));
		}
		
		$generate_module = new Generate_Module_Script(array("module_name" => $this->module_name));
		$generate_module->generate();
		
		//Model 
		$model_class = YZE_Object::format_class_name($this->class_name,"Model");
		$handleResult = $this->create_model_code($model_class);
		echo "create model :\t\t\t";
		$this->save_class($handleResult, $model_class, $this->module_name);
		echo "create model phpt file :\t";
		$this->save_test($handleResult, $model_class, $this->module_name);
		
		echo "Done!\r\n";
	}
	
	public function create_model_code($class){
		$table = $this->table_name;
		$package=$this->module_name;
		
		$app_module = new \app\App_Module();
		$db = mysql_connect(
			$app_module->get_module_config("db_host"),
			$app_module->get_module_config("db_user"),
			$app_module->get_module_config("db_psw")
		);
		mysql_select_db($app_module->get_module_config("db_name"),$db);
		mysql_query("set names utf8",$db);
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
namespace app\\$package;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;

/**
 * 
 * 
 * @version \$Id\$
 * @package $package
 */
class $class extends YZE_Model{
	$constantdefine
	protected \$table= \"$table\";
	protected \$version = 'modified_on';
	protected \$module_name = \"$package\";
	protected \$key_name = \"$key\";
	protected \$columns = array($fielddefine);

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
