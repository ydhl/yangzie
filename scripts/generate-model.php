<?php
namespace yangzie;

class Generate_Model_Script extends AbstractScript{
	protected $base;
	protected $module_name;
	protected $table_name;
	protected $class_name;
	
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
		echo __("create model :\t\t\t");
		$this->save_class($handleResult, $model_class, $this->module_name);
		echo __("create model phpt file :\t");
		$this->save_test($handleResult, $model_class, $this->module_name);
		
		echo __("Done!\n");
	}
	
	public function create_model_code($class){
		$table = $this->table_name;
		$package=$this->module_name;
		
		$app_module = new \app\App_Module();
		$db = mysqli_connect(
			$app_module->get_module_config("db_host"),
			$app_module->get_module_config("db_user"),
			$app_module->get_module_config("db_psw")
		);
		mysqli_select_db($db, $app_module->get_module_config("db_name"));
		mysqli_query($db, "set names utf8");
		$result = mysqli_query($db, "show full columns from $table");
		
		if (!$result) {
			die($table . mysqli_error($db)."\r\n");
		}
		$constant = array();
		while ($row=mysqli_fetch_assoc($result)) {
			$row['Key']=="PRI" ? $key = $row['Field'] : null;
			$type_info = $this->get_type_info($row['Type']);
			$constant = array_merge((array)$constant,(array)$this->getEnumConstant($row['Type']));
			
			@$fielddefine .= "       ".str_pad("'".$row['Field']."'", 12," ")." => array('type' => '".$type_info['type']."', 'null' => ".(strcasecmp($row['Null'],"YES") ? "false" : "true").",'length' => '".$type_info['length']."','default'	=> '".$row['Default']."',),
";
			
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

/**
*
*
* @version \$Id\$
* @package $package
*/
class $class extends YZE_Model{
    $constantdefine
    const TABLE= \"$table\";
    const VERSION = 'modified_on';
    const MODULE_NAME = \"$package\";
    const KEY_NAME = \"$key\";
    protected \$columns = array(
        $fielddefine
    );
    //array('attr'=>array('from'=>'id','to'=>'id','class'=>'','type'=>'one-one||one-many') )
    //\$this->attr
    protected \$objects = array();
}?>";
	}
	
	protected function getEnumConstant($type){
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

	protected function save_class($handleResult,$class,$package){
		$class = strtolower($class);
		$path = dirname(dirname(__FILE__))."/app/modules/".$package."/models";
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/".$package);
		$this->check_dir($path);
	
			
		$class_file_path = dirname(dirname(__FILE__))
			."/app/modules/".$package."/models/{$class}.class.php";
		$this->create_file($class_file_path, $handleResult, true);
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
    
    public function create_model_code($class){
        $table = $this->table_name;
        $package=$this->module_name;
    
        $app_module = new \app\App_Module();
        $db = mysqli_connect(
                $app_module->get_module_config("db_host"),
                $app_module->get_module_config("db_user"),
                $app_module->get_module_config("db_psw")
        );
        mysqli_select_db($db, $app_module->get_module_config("db_name"));
        mysqli_query($db, "set names utf8");
        $result = mysqli_query($db, "show full columns from $table");
    
        if (!$result) {
            die($table . mysqli_error($db)."\r\n");
        }
        $constant = array();
        while ($row=mysqli_fetch_assoc($result)) {
            $row['Key']=="PRI" ? $key = $row['Field'] : null;
            $type_info = $this->get_type_info($row['Type']);
            $constant = array_merge((array)$constant,(array)$this->getEnumConstant($row['Type']));
            	
            @$fielddefine .= "       ".str_pad("'".$row['Field']."'", 12," ")." => array('type' => '".$type_info['type']."', 'null' => ".(strcasecmp($row['Null'],"YES") ? "false" : "true").",'length' => '".$type_info['length']."','default'	=> '".$row['Default']."',),
";
            	
        }
    
        
        $class_file_path = dirname(dirname(__FILE__))
        ."/app/modules/".$package."/models/{$class}.class.php";
        
        $cls = "\\app\\{$package}\\$class";
        $content = file_get_contents($class_file_path);
        
        $constantdefine = '';
        foreach($constant as $c=>$v){
            if( ! constant("{$cls}::$v")){
                $constantdefine .= "
                const $v = '$c';";
            }
        }
        
        $content = preg_replace('{protected\s+\$columns\s+=.+;+?}is', "
    $constantdefine
    protected \$columns = array(
        $fielddefine
    );", $content);
        
        return $content;
    }
    
}
?>