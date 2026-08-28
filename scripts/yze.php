<?php
namespace yangzie;
define("YZE_SCRIPT_LOGO", "
================================================================
		YANGZIE(V%s) Generate Script
		易点互联®
================================================================");

define("YZE_METHED_HEADER", "
================================================================
		%s
================================================================");
define("YZE_SCRIPT_USAGE", YZE_SCRIPT_LOGO."

\t1.  Generate module, controller, view Scaffolding file
\t2.  Generate model (database to code)	
\t3.  Delete module
\t4.  Delete controller and view file	
\t5.  Phar a module
\t6.  Run unit
\t0.  Quit

please input number to select: ");

define("YZE_SCRIPT_OPTION", YZE_SCRIPT_LOGO."

1.Usage CLI: php scripts/yze.php [options]

 Generate model (database to code):
  -m, --model          Generate model mode
  -d, --db=DB_NAME     Database name
  -t, --table=TABLE    Table name
  -M, --module=MODULE  Module name

  Example:
    php scripts/yze.php --model --table=acl --module=admin
	
 Generate module, controller, view Scaffolding file:
  -c, --mvc				Generate mvc mode
  -M, --module=MODULE 			Module name
  -C, --controller=Controller		controller name
  -a, --action=Action  			action name, default is index
  -r, --route=Route Url 		you can use regex like foobar/(?P<id>\\d+):

  Example:
    php scripts/yze.php --mvc -C=index --module=admin
		
 Phar a module:
  -p, --phar        	Phar a module
  -M, --module=MODULE  	Module name
  -k, --key=Key file

  Example:
    php scripts/yze.php --phar --module=admin

 -h, --help           Show this help message

2. Usage Wizard:
  php scripts/yze.php

");

global $language, $db;
$language = "zh-cn";

if(!preg_match("/cli/i",php_sapi_name())){
	echo wrap_output(sprintf(__("please run in command mode: php scrips/yze.php",dirname(__FILE__))));die();
}


chdir("./app/public_html");
include_once 'init.php';
include_once '../../scripts/generate-controller.php';
include_once '../../scripts/generate-model.php';
include_once '../../scripts/generate-module.php';

// 解析命令行参数
$options = getopt("mcpC:a:r:d:t:M:h", ["model","mvc","phar", "controller:", "action:", "route:", "db:", "table:", "module:", "help"]);

// 检查是否请求帮助
if (isset($options["h"]) || isset($options["help"])) {
	echo wrap_output(sprintf(YZE_SCRIPT_OPTION, YZE_Object::VERSION));
	die();
}

// 检查是否是cli生成模式
if ($options) {
	$cmds = get_options($options);
	if ($cmds){
		$command = $cmds["cmd"];
		clear_terminal();
		echo get_colored_text(wrap_output(__('begin generate...')), "blue", "white")."\r\n";
		$class_name = "\yangzie\Generate_".ucfirst(strtolower($command))."_Script";
		$object = new $class_name($cmds);
		$object->generate();
		echo "\r\n".get_colored_text(wrap_output(__('generate done.')), "blue", "white")."\r\n";
		//fgets(STDIN);
	}
	die();
}

// 原有的交互式流程
clear_terminal();
while(($cmds = display_home_wizard())){
	$command = $cmds["cmd"];
	clear_terminal();
	echo get_colored_text(wrap_output(__('begin generate...')), "blue", "white")."\r\n";
	$class_name = "\yangzie\Generate_".ucfirst(strtolower($command))."_Script";
	$object = new $class_name($cmds);
	$object->generate();
	echo "\r\n".get_colored_text(wrap_output(__('generate done.')), "blue", "white")."\r\n";
	//fgets(STDIN);
	die();
}


function get_options($options){
	if (isset($options["model"]) || isset($options["m"])){
		$database = isset($options["d"]) ? $options["d"] : (isset($options["db"]) ? $options["db"] : "");
		$table = isset($options["t"]) ? $options["t"] : (isset($options["table"]) ? $options["table"] : "");
		$module = isset($options["M"]) ? $options["M"] : (isset($options["module"]) ? $options["module"] : "");

		if (empty($table)) {
			echo wrap_output("Error: Table name is required\n");
			die(1);
		}

		if (empty($module)) {
			echo wrap_output("Error: Module name is required\n");
			die(1);
		}
		if (!is_validate_name($module)) {
			echo get_colored_text(wrap_output(__("module name is invalid, please try again\n")), "red");
			die(1);
		}

		// 验证数据库
		if (!is_validate_db($database)) {
			echo wrap_output("Error: Invalid database\n");
			die(1);
		}

		// 验证表
		if (!is_validate_table($database, $table)) {
			echo wrap_output("Error: Invalid table\n");
			die(1);
		}

		// 构建命令参数
		return array(
			"cmd" => "model",
			"base" => "table",
			"module_name" => $module,
			"db_name" => $database,
			"class_name" => $table,
			"table_name" => $table,
		);
	}
	if (isset($options["mvc"]) || isset($options["c"])){
		$uri = isset($options["r"]) ? $options["r"] : (isset($options["route"]) ? $options["route"] : "");
		$action = isset($options["a"]) ? $options["a"] : (isset($options["action"]) ? $options["action"] : "");
		$controller = isset($options["C"]) ? $options["C"] : (isset($options["controller"]) ? $options["controller"] : "");
		$module = isset($options["M"]) ? $options["M"] : (isset($options["module"]) ? $options["module"] : "");

		if (empty($controller)) {
			echo wrap_output("Error: controller name is required\n");
			die(1);
		}
		if (!is_validate_name($controller)){
			echo get_colored_text(wrap_output(__("controller name is invalid, please try again\n")), "red");
            die(1);
		}
		if (empty($module)) {
			echo wrap_output("Error: Module name is required\n");
			die(1);
		}

		if ($action && !is_validate_name($action)) {
			echo get_colored_text(wrap_output(__("action name is invalid, please try again\n")), "red");
			die(1);
		}
		if (!is_validate_name($module)) {
			echo get_colored_text(wrap_output(__("module name is invalid, please try again\n")), "red");
			die(1);
		}
		return array(
			"cmd" => "controller",
			"controller"=>$controller,
			"action"=>$action?:'index',
			"uri"=>$uri,
			"module_name"=>$module,
			"view_format"=>"tpl"
		);
	}
	if (isset($options["phar"]) || isset($options["p"])){
		$module = isset($options["M"]) ? $options["M"] : (isset($options["module"]) ? $options["module"] : "");
		$key_path = isset($options["k"]) ? $options["k"] : (isset($options["key"]) ? $options["key"] : "");

		if (!is_validate_name($module)) {
			echo get_colored_text(wrap_output(__("module name is invalid, please try again")), "red");
			die(1);
		}
		phar_module($module, $key_path);
		return array();
	}
	return array();
}

function display_home_wizard(){
	clear_terminal();
	echo wrap_output(sprintf(YZE_SCRIPT_USAGE, YZE_Object::VERSION));

	while(!in_array(($input = fgets(STDIN)), array(0,1, 2, 3, 4, 5, 6))){
		echo wrap_output(__("please input number to select: "));
	}

	switch ($input){
		case 1: return display_mvc_wizard();
		case 2:  return display_model_wizard();
		case 3:  return display_delete_module_wizard();
		case 4:  return display_delete_controller_wizard();
		case 5:  return display_phar_wizard();
		case 6:  return _run_test();
		case 0:  die(wrap_output("\r\n
Quit.\r\n
"));
		default: return array();
	}
}

function _run_test(){
	clear_terminal();
	echo wrap_output(sprintf(__( YZE_METHED_HEADER."
	
Choose unit to run，%sBack

"), "run unit test", get_colored_text(" 0 ", "red", "white")));

	$index = 0;
	$tests = array();
	foreach(glob("../../tests/*")  as $f){
		if(is_dir($f)) {
			$index++;
			$test = basename($f);
			$tests[$index] = $f;
			echo "\t".($index).". {$test} \n";
		}
	}
	if($tests){
		$tests[0] = "";
		echo wrap_output(__("\t0. run all tests\n:"));
	}else{
		echo wrap_output(__("\tno unit test\n"));
	}
	while (!array_key_exists(($selectedIndex = get_input()), $tests)){
		echo get_colored_text(wrap_output(__("\ttest not exist, please choose again:  ")), "red");
	}

	include "../../tests/config.php";
	$php = getenv("TEST_PHP_EXECUTABLE");
	if (empty($php) || !file_exists($php)) {
		echo get_colored_text(wrap_output(__("please modify TEST_PHP_EXECUTABLE in tests/config.php ")), "red");die;
	}


	if($selectedIndex==="0"){
		system("php ../../tests/run-tests.php  ../../tests");
	}else{
		system("php ../../tests/run-tests.php ".$tests[$selectedIndex]);
	}
}


function display_phar_wizard(){
	clear_terminal();
	echo wrap_output(sprintf(__( YZE_METHED_HEADER."
	
phar a module，%s back
1. (1/2)please input module name:  "), "phar module", get_colored_text(" 0 ", "red", "white")));

	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\tname is invalid, pleae input again:  ")), "red");
	}

	if( ! file_exists(dirname(dirname(__FILE__))."/app/modules/".$module)){
		echo wrap_output("module not exist");
	}

	echo wrap_output(__("2. (2/2)phar signature key file name (pem file in the tmp folder) 
	
if you need create pem file, do such as:
1.cd tmp
2.openssl genrsa -out mykey.pem 1024
3.openssl rsa -in mykey.pem -pubout -out mykey.pub

if not need signature please press enter 

:"));
	$key_path = trim(get_input());
	if ($key_path){
		while (!file_exists(($key_path = YZE_INSTALL_PATH."tmp/".$key_path))){
			echo get_colored_text(wrap_output(vsprintf(__("\t%s file not exist:  "), $key_path)), "red");
		}
	}

	phar_module($module, $key_path);

	return array();
}

function phar_module($module, $key_path){
	@mkdir(dirname(dirname(__FILE__))."/tmp/");
	try{
		echo ini_get('phar.readonly');
		$phar = new \Phar(dirname(dirname(__FILE__))."/tmp/".$module.'.phar', 0, $module.'.phar');
	}catch (\Exception $e){
		echo wrap_output($e->getMessage());
		die();
	}
	$phar->buildFromDirectory(dirname(dirname(__FILE__))."/app/modules/".$module);
	//$phar->setStub($phar->createDefaultStub('__config__.php'));
	$phar->compressFiles(\Phar::GZ);
	if($key_path){
		$private = openssl_get_privatekey(file_get_contents($key_path));
		$pkey = '';
		openssl_pkey_export($private, $pkey);
		$phar->setSignatureAlgorithm(\Phar::OPENSSL, $pkey);
	}

	@unlink(YZE_APP_PATH."modules/{$module}.phar");
	yze_move_file(YZE_INSTALL_PATH."tmp/{$module}.phar", YZE_APP_PATH."modules");
	echo wrap_output(sprintf(__("phar saved at modules/%s.phar\r\n"),$module));
	if($key_path){
		$key_name = pathinfo(basename($key_path), PATHINFO_FILENAME);
		copy(YZE_INSTALL_PATH."tmp/{$key_name}.pub", YZE_APP_PATH."modules/{$module}.phar.pubkey");
		echo wrap_output(sprintf(__("%s.phar.pubkey saved at modules/%s.phar.pubkey\r\n"),$module,$module), 'green');
	}
}

function display_delete_controller_wizard(){
	clear_terminal();
	echo sprintf(wrap_output(__( YZE_METHED_HEADER."

delete controller and view，%s back
1. (1/2)module name: ")), "delete controller",get_colored_text(" 0 ", "red", "white"));

	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\tmodule not found:  ")), "red");
	}

	echo wrap_output(__("2. (2/2)controller name:  "));
	while (!is_validate_name(($controller = get_input()))){
		echo get_colored_text(wrap_output(__("\tcontroller not found:  ")), "red");
	}

	if( ! file_exists(dirname(dirname(__FILE__))."/app/modules/{$module}/controllers/{$controller}.controller.php")){
		echo wrap_output(__("controller not found"));
	}else{
		unlink(dirname(dirname(__FILE__))."/app/modules/{$module}/controllers/{$controller}.controller.php");
		foreach (glob(dirname(dirname(__FILE__))."/app/modules/{$module}/views/{$controller}.*") as $file){
			unlink($file);
		}
		unlink(dirname(dirname(__FILE__))."/tests/{$module}/{$controller}.controller.phpt");
		echo wrap_output(__("deleted"));
	}

	return array();
}

function display_delete_module_wizard(){
	clear_terminal();
	echo sprintf(wrap_output(__( YZE_METHED_HEADER."
	
module name，%s back:  ")), "delete module", get_colored_text(" 0 ", "red", "white"));

	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\tmodule not found:  ")), "red");
	}

	if( ! file_exists(dirname(dirname(__FILE__))."/app/modules/".$module)){
		echo wrap_output(__("module not found "));
	}else{
		rrmdir(dirname(dirname(__FILE__))."/app/modules/".$module);
		rrmdir(dirname(dirname(__FILE__))."/tests/".$module);
		echo wrap_output(__("deleted"));
	}

	return array();
}

function display_mvc_wizard(){
	clear_terminal();
	echo wrap_output(sprintf(__( YZE_METHED_HEADER."
  
generate controller and view，%s back:
1. (1/4)module name:  "), "generate controller", get_colored_text(" 0 ", "red", "white")));

	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\tname is invalid, please type again:  ")), "red");
	}

	echo wrap_output(__("2. (2/4)controller name:  "));
	while (!is_validate_name(($controller = get_input()))){
		echo get_colored_text(wrap_output(__("\tname is invalid, please type again:  ")), "red");
	}

	echo wrap_output(__("3. (3/4)action name，default is index:  "));
	while(true){
		$action = get_input() ?: 'index';
		if (!is_validate_name($action)){
			echo get_colored_text(wrap_output(__("\tname is invalid, please type again:  ")), "red");
			continue;
		}

		if(($uris = is_controller_exists($action, $controller, $module))){
			echo wrap_output(sprintf(__("3. (3/4)%s->%s is exist，it's URI:\n\n"), $controller, $action));
			foreach ($uris as $index => $uri){
				echo "\t ".($index+1).". {$uri}\n";
			}
			echo wrap_output(__("please reenter:"));
			continue;
		}
		break;
	}

	echo wrap_output(__("4. (4/4)URI route, default uri is /{$module}/{$controller}/{$action}, you can use regex like foobar/(?P<id>\\d+):  "));
	$uri = get_input();

	// ai@2026-05-27 去掉冗余 @，array() 不会产生错误
	return array(
		"cmd" => "controller",
		"controller"=>$controller,
		"action"=>$action,
        "uri"=>$uri,
        "module_name"=>$module,
        "view_format"=>"tpl" ,

// 		"model"=>$model,
// 		"view_tpl"=>$view_tpl
	);
}

function is_controller_exists($action, $controller, $module){
	$controller = strtolower($controller);
	if(!file_exists(YZE_APP_MODULES_INC.$module."/__config__.php")) return false;
	if(!file_exists(YZE_APP_MODULES_INC.$module."/controllers/{$controller}.controller.php")) return false;
	include_once YZE_APP_MODULES_INC.$module."/controllers/{$controller}.controller.php";
	$controllerClass = ucfirst($controller).'_Controller';
	if (!method_exists('app\\user\\'.$controllerClass, $action)) return false;

	include_once YZE_APP_MODULES_INC.$module."/__config__.php";
	$class = "\\app\\".$module."\\".ucfirst(strtolower($module))."_Module";
	$object = new $class();
	return $object->get_uris_of_controller($controller, $action) ?: ["/{$module}/{$controller}/{$action}"];

}

function display_model_wizard(){
    global $db;
	clear_terminal();

	$app_module = new \app\App_Module();
	$db_name = $app_module->get_module_config('default_db');

	echo wrap_output(sprintf(__( YZE_METHED_HEADER."

generate model，%s back:
1. (1/3)database name, default is %s: "), "generate model", get_colored_text(" 0 ", "red", "white"), $db_name));

	while (!is_validate_db(($database = get_input()))){
		echo get_colored_text(wrap_output(sprintf(__("\tdb not exist (%s)，please check:  "), $database)), "red");
	}

	echo wrap_output(__("2. (2/3)table name:  "));

	while (!is_validate_table($database, ($table=get_input()))){
		echo get_colored_text(wrap_output(sprintf(__("\ttable not exist (%s)，please check:  "), mysqli_error($db))), "red");
	}

	echo wrap_output(__("3. (3/3)module name:  "));
	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\tmodule is invalid, please check:  ")), "red");
	}


	return array(
		"cmd" => "model",
		"base"=>"table",
		"module_name"=>$module,
		"db_name"=>$database,
		"class_name"=>$table,
		"table_name"=>$table,
	);
}

function get_colored_text($text, $fgcolor=null, $bgcolor=null){
	if(PHP_OS=="WINNT")return $text;
	//return "\033[40m\033[31m some colored text \033[0m"; // red
	if(!$fgcolor && !$bgcolor)return $text;

	$_fgcolor = get_fgcolor($fgcolor);
	$_bgcolor = get_bgcolor($bgcolor);

	$colored_string = "";
	if ($_fgcolor) {
		$colored_string .= "\033[" . $_fgcolor . "m";
	}

	if ($_bgcolor) {
		$colored_string .= "\033[" . $_bgcolor . "m";
	}

	$colored_string .=  $text . "\033[0m";
	return $colored_string;
}

function get_bgcolor($color){
	switch(strtolower($color)){
	case 'black': return'0;30';
	case 'dark_gray': return'1;30';
	case 'blue': return'0;34';
	case 'light_blue': return'1;34';
	case 'green': return'0;32';
	case 'light_green': return'1;32';
	case 'cyan': return'0;36';
	case 'light_cyan': return'1;36';
	case 'red': return'0;31';
	case 'light_red': return'1;31';
	case 'purple': return'0;35';
	case 'light_purple': return'1;35';
	case 'brown': return'0;33';
	case 'yellow': return'1;33';
	case 'light_gray': return'0;37';
	case 'white': return'1;37';

		default: return null;
	}
}
function get_fgcolor($color){
	switch(strtolower($color)){
	case 'black': return'40';
	case 'red': return'41';
	case 'green': return'42';
	case 'yellow': return'43';
	case 'blue': return'44';
	case 'magenta': return'45';
	case 'cyan': return'46';
	case 'light_gray': return'47';
	default: return null;
	}
}

function get_input(){
	$input = strtolower(trim(fgets(STDIN)));
	is_back($input);
	return $input;
}

function is_back($input){
	if(strlen($input) >0 && $input=="0"){display_home_wizard();die;}
}

function is_validate_name($input){
	return preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $input);
}

function is_validate_db($db_name){
    global $db;
	$app_module = new \app\App_Module();
	$db_name = $db_name ?: $app_module->get_module_config('default_db');
	$db_connection = $app_module->get_module_config('db_connections')[$db_name];

	if (!$db_connection) return false;

	$db = mysqli_connect(
		$db_connection["db_host"],
		$db_connection["db_user"],
		$db_connection["db_psw"],
		$db_name,
		intval($db_connection["db_port"])
	);
	return $db;
}


function is_validate_table($db_name, $table){
    global $db;
	$app_module = new \app\App_Module();
	$db_name = $db_name ?: $app_module->get_module_config('default_db');

	mysqli_select_db($db, $db_name);
	return mysqli_query($db, "show full columns from `$table`");
}


function clear_terminal(){
	if(PHP_OS=="WINNT"){
		$clear = "cls";
	}else{
		$clear = "clear";
	}
	exec($clear);
}

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function wrap_output($msg){
//	if(PHP_OS=="WINNT"){
//		return iconv("UTF-8", "GB2312//IGNORE", $msg);
//	}else{
		return $msg;
//	}
}

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
			echo get_colored_text("file exists", "red", "white")."\r\n";return;
		}

		$f = fopen($file_path,'w+');
		if(empty($f)){
			echo get_colored_text("can not open file:{$file_path}");return;
		}
		chmod($file_path,0777);
		fwrite($f,$content);
		fclose($f);
		echo get_colored_text("OK.","blue","white")."\r\n";
	}

}
?>
