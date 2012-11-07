<?php

chdir("../app/public_html");
include_once 'init.php';
include_once 'load.php';
include_once '../../scripts/generate-controller.php';
include_once '../../scripts/generate-model.php';
include_once '../../scripts/generate-module.php';


$usage = __("

用法:
	
 php generate.php args
	
 生成控制器、action及对应的view、validate, 生成model, 生成module的目录结构
	
 1) ".Generate_Controller_Script::USAGE."

 2) ".Generate_Model_Script::USAGE."

 3) ".Generate_Module_Script::USAGE);

#解析出参数
if(!preg_match("/cli/i",php_sapi_name())){
	echo __("请在命令行下运行,进入到".dirname(__FILE__).", 运行php generate.php");die();
}

if(count($argv)<2){
	echo $usage;die();
}

array_shift($argv);//shift generate.php
if(($key=array_search("-cmd", $argv))!==false){
	$command = $argv[$key+1];
	unset($argv[$key],$argv[$key+1]);
}

$class_name = "Generate_".ucfirst(strtolower($command))."_Script";
if(!class_exists($class_name)){
	echo __("错误的command：$command");
	echo $usage;
	die();
}

$object = new $class_name($argv);
$object->generate();

abstract class AbstractScript{
	protected $args = array();
	public function __construct($args){
		$this->args = $args;
	}
	public abstract function generate();

	public function check_dir($path){
		if(!file_exists($path)){
			$dir = mkdir($path);
			if(empty($dir)){
				die("\r\n\r\n\tcan not make dir: \r\n\r\n\t$path \r\n\r\n");
			}
			chmod($path, 0777);
		}
	}

	public function create_file($file_path,$content,$force=false){
		if(file_exists($file_path) && !$force){
			echo "file exists\r\n";return;
		}

		$f = fopen($file_path,'w+');
		if(empty($f)){
			echo "\r\n\r\n\tcan not open file:\r\n\r\n\t{$file_path}\r\n\r\n";return;
		}
		chmod($file_path,0777);
		fwrite($f,$content);
		fclose($f);
		echo("OK.\r\n");
	}

	protected function create_view($module, $controller){
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/views");
		$view_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/views/{$controller}.tpl.php";
		$view_file_content = "<?php
//TODO 定义视图显示
?>";
		echo("create view :\t\t\t");
		$this->create_file($view_file_path, $view_file_content);
	}


	protected function create_validate($module, $controller){
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/validates");

		$validate_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/validates/{$controller}_validate.class.php";
		$validate_file_content = "<?php
/**
 *
 * @version \$Id\$
 * @package $module
 */
class ".YangzieObject::format_class_name($controller, "Validate")." extends YZEValidate{

	public function init_get_validates(){
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('get', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_post_validates(){
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_put_validates(){
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_delete_validates(){
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
}?>";
		echo("create validate :\t\t");
		$this->create_file($validate_file_path, $validate_file_content);
	}
}
?>