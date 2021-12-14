<?php
namespace yangzie;

class Generate_Model_Script extends AbstractScript{
	protected $base;
	protected $module_name;
	protected $table_name;
	protected $class_name;
	static $chain_tables = [];

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
        $method_class = $model_class."_Method";
        $code = $this->create_method_code($method_class);
        echo __("create model method {$method_class} ");
        $this->save_class($code, $method_class, $this->module_name, 'trait', false);
		$handleResult = $this->create_model_code($model_class, $method_class);
		echo __("create model {$model_class} :");
		$this->save_class($handleResult, $model_class, $this->module_name);
		echo __("create model {$model_class} phpt file :");
		$this->save_test($handleResult, $model_class, $this->module_name);

		echo __("Done!\n");
	}

    public function create_method_code($class){
        $package=$this->module_name;

        return "<?php
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
trait $class{
    // 这里实现model的业务方法 
}?>";
    }


	public function create_model_code($class, $method_class){
		$table = $this->table_name;
		$package=$this->module_name;
		$app_module = new \app\App_Module();

		$db = mysqli_connect(
				$app_module->get_module_config("db_host"),
				$app_module->get_module_config("db_user"),
				$app_module->get_module_config("db_psw"),
				$app_module->get_module_config("db_name"),
				$app_module->get_module_config("db_port")
				);

		$importClass = "";
		$assocFields = "";
		$assocFieldFuncs = "";
		$enumFunction = "";
		mysqli_select_db($db, "INFORMATION_SCHEMA");
		$result = mysqli_query($db, "select COLUMN_NAME,CONSTRAINT_NAME,
		REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME from KEY_COLUMN_USAGE
		where TABLE_SCHEMA = '".$app_module->get_module_config("db_name")."' and TABLE_NAME = '{$table}'
		and referenced_column_name is not NULL");
		while ($row=mysqli_fetch_assoc($result)) {
			$col = rtrim($row['REFERENCED_TABLE_NAME'], "s");//先假定复数形式
			$col_class = $this->get_class_of_table($row['REFERENCED_TABLE_NAME']);
			$importClass .= "use $col_class;\r\n";
			$sortClass = substr($col_class, strripos($col_class, "\\")+1);
			$assocFields .= "
	private \${$col};
";
			$assocFieldFuncs .= "
	public function get_{$col}(){
		if( ! \$this->{$col}){
			\$this->{$col} = {$sortClass}::find_by_id(\$this->get(self::F_".strtoupper($row['COLUMN_NAME'])."));
		}
		return \$this->{$col};
	}
	
	/**
	 * @return $class
	 */
	public function set_{$col}({$sortClass} \$new){
		\$this->{$col} = \$new;
		return \$this;
	}
";
		}


		mysqli_select_db($db, $app_module->get_module_config("db_name"));
		mysqli_query($db, "set names ".$app_module->get_module_config("db_charset"));


		$unique_key = array();
		$result = mysqli_query($db, "SHOW INDEX FROM  `$table`");
		while ($row=mysqli_fetch_assoc($result)) {
		    $unique_key[$row['Column_name']] = $row['Key_name'];
		}
		$constant   = array();
		$column_mean= [];

		$result = mysqli_query($db, "show full columns from `$table`");
		while ($row=mysqli_fetch_assoc($result)) {
			$row['Key']=="PRI" ? $key = $row['Field'] : null;
			$type_info = $this->get_type_info($row['Type']);
			$currEnums = (array)$this->getEnumConstant($row['Field'], $row['Type']);
			$constant = array_merge((array)$constant, $currEnums);

			if ($currEnums){
			$enumFunction .= "
	public function get_{$row['Field']}(){
		return ['".join("','", array_keys($currEnums))."'];
	}";
			}

			@$fielddefine .= "      ".str_pad("'".$row['Field']."'", 12," ")." => ['type' => '".$type_info['type']."', 'null' => ".(strcasecmp($row['Null'],"YES") ? "false" : "true").",'length' => '".$type_info['length']."','default'	=> '".$row['Default']."'],
";
			@$properConst .= "
    /**
     * {$row['Comment']}
     * @var {$type_info['type']}
     */
    const F_".strtoupper($row['Field'])." = \"{$row['Field']}\";";
			$column_mean[] = "case self::F_".strtoupper($row['Field']).": return \"".($row['Comment']?:$row['Field'])."\";";
		}

		$constantdefine = '';
		foreach($constant as $c=>$v){
		    $constantdefine .= "
    const $v = '$c';";
		}

		return "<?php
namespace app\\$package;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;
{$importClass}
/**
 *
 *
 * @version \$Id\$
 * @package $package
 */
class $class extends YZE_Model{
    use $method_class;
    $constantdefine
    const TABLE= \"$table\";
    const MODULE_NAME = \"$package\";
    const KEY_NAME = \"$key\";
    const CLASS_NAME = 'app\\$package\\$class';
    /**
     * @see YZE_Model::\$encrypt_columns 
     */
    public \$encrypt_columns = array();
    $properConst
    public static \$columns = [
    ".trim($fielddefine)."
    ];
    /**
     * @see YZE_Model::\$unique_key
     */
    protected \$unique_key = ".var_export($unique_key, true).";
    		
    {$assocFields}
	{$assocFieldFuncs}
	{$enumFunction}
	/**
	 * 返回每个字段的描述文本
	 * @param \$column
	 * @return mixed
	 */
    public function get_column_mean(\$column){
    	switch (\$column){
    	".join("\r\n\t\t",$column_mean)."
    	default: return \$column;
    	}
		return \$column;
	}
    /**
	 * 返回表的描述
	 * @param \$column
	 * @return mixed
	 */
    public function get_description(){
		return '';
	}
}?>";
	}

	protected function getEnumConstant($name, $type){
		if(preg_match("/^enum\((?<v>.+)\)/",$type,$matches)){
			foreach(explode(",",$matches['v']) as $c){
				$c = trim($c,"'");
				$constant[$c] = strtoupper($name)."_".strtr(strtoupper($c),array("-"=>"_"," "=>"_"));
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
	protected function get_type_info($type){
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


	protected function save_test($handleResult,$class,$package){
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
	//write you test code here
?>
--EXPECT--
	";

		$this->create_file($class_file_path, $test_file_content);
	}

	protected function save_class($handleResult,$class,$package, $format="class", $overwrite=true){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/app/modules/".$package."/models";
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/".$package);
		$this->check_dir($path);


		$class_file_path = dirname(dirname(__FILE__))
			."/app/modules/".$package."/models/{$class}.{$format}.php";
		if (!$overwrite && file_exists($class_file_path)){
			echo get_colored_text("file exists", "red", "white")."\r\n";return;
		}
		$this->create_file($class_file_path, $handleResult, true);
	}

	protected function get_class_of_table($table){
		global $db;
		$class_name = YZE_Object::format_class_name(rtrim($table, "s"),"Model");
		if(class_exists($class_name)){
			return $class_name;
		}

		if ( @ self::$chain_tables[$table] ){//之前已经处理过了
			return self::$chain_tables[$table];
		}

		clear_terminal();

		echo wrap_output(sprintf(__("    ================================================================
		
    未能识别关联表%s的Model类，请输入该类所在的module名（默认当前模块）："), $table));
		$module = get_input();

		if ( ! $module){
			$module = $this->module_name;
		}

		self::$chain_tables[$table] = $module;

		if (class_exists("\\app\\{$module}\\{$class_name}")){
			return "\\app\\{$module}\\{$class_name}";
		}

		echo get_colored_text(wrap_output(sprintf(__("    开始生成 %s..."), "\\app\\{$module}\\{$class_name}")), "blue", "white")."\r\n";
		$object = new \yangzie\Generate_Model_Script(array("cmd" => "model",
			"base"       => "table",
			"module_name"=> $module,
			"class_name" => preg_replace('/_model$/i', "", $class_name),
			"table_name" => $table));
		$object->generate();
		echo "\r\n".get_colored_text(wrap_output(sprintf(__("    生成结束 %s ."), "\\app\\{$module}\\{$class_name}")), "blue", "white")."\r\n";


		return "\\app\\{$module}\\{$class_name}";
	}
}

class Generate_Refreshmodel_Script extends Generate_Model_Script{
	public function generate(){
		$argv = $this->args;
		$cls = $argv['class_name'];

		$argv['module_name'] = constant("$cls::MODULE_NAME");
		$argv['table_name']  = constant("$cls::TABLE");
		$argv['class_name']  = preg_replace("{_model}", "",
				preg_replace("{\|?app\|".$argv['module_name']."\|}", "",
						str_replace("\\", "|", $cls)));
		$this->args = $argv;
		parent::generate();
	}


}
?>
