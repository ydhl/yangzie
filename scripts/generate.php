<?php
if(!preg_match("/cli/i",php_sapi_name())){
	echo wrap_output(__("请在命令行下运行,进入到".dirname(__FILE__).", 运行php generate.php"));die();
}


chdir("../app/public_html");
include_once 'init.php';
include_once 'load.php';
include_once '../../scripts/generate-controller.php';
include_once '../../scripts/generate-model.php';
include_once '../../scripts/generate-module.php';

if(true){
	while(($cmds = display_home_wizard())){
		$command = $cmds["cmd"];
		clear_terminal();
		echo get_colored_text(wrap_output("开始生成...\r\n"), "blue");
		$class_name = "Generate_".ucfirst(strtolower($command))."_Script";
		$object = new $class_name($cmds);
		$object->generate();
		echo get_colored_text(wrap_output("\r\n生成结束, 回车返回.\r\n"), "blue");
		fgets(STDIN);
	}
}


function display_home_wizard(){
	clear_terminal();
	echo wrap_output(__( "
================================================================
\t\tYANGZIE Generate Script
\t\t易点互联®
================================================================
  
伙计，你想要生成什么？
	
\t\t1.  生成VC代码：\t\tcontroller，view，validate文件
\t\t2.  生成Model：	\t\t根据表生成Model文件
\t\t3.  goodbye
	
请选择(1 or 2 or 3): "));
	
	while(!in_array(($input = fgets(STDIN)), array(1, 2, 3))){
		echo wrap_output("请选择(1 or 2 or 3): ");
	}
	
	switch ($input){
		case 1: return display_mvc_wizard();
		case 2:  return display_model_wizard();
		case 3:  die(wrap_output("
退出.
"));
		default: return array();
	}
}
function display_mvc_wizard(){
	clear_terminal();
	echo vsprintf(wrap_output(__( "
================================================================
		YANGZIE Generate Script
		易点互联®
================================================================
  
你将生成VC代码结构，请根据提示进操作，%s返回上一步：
1. (1/8)所在功能模块:  ")), get_colored_text(" CTRL+B ", "red", "white"));
	
	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\t命名遵守PHP变量命名规则，请重输:  ")), "red");
	}
	
	echo wrap_output(__("2. (2/8)控制器的名字:  "));
	while (!is_validate_name(($controller = get_input()))){
		echo get_colored_text(wrap_output(__("\t命名遵守PHP变量命名规则，请重输:  ")), "red");
	}
	
	if(($uris = is_controller_exists($controller, $module))){
		echo wrap_output(__("3. (3/8)控制器已存在，映射URI的是:\r\n\r\n"));
		foreach ($uris as $index => $uri){
			echo "\t ".($index+1).". {$uri}\r\n";
		}
		echo wrap_output(__("\r\n\t选择一个或者输入新的, 回车表示不映射:"));
		$uri = get_input();
		
		if (is_numeric($uri)){
			$uri = $uris[$uri-1];
		}
	}else{
		echo wrap_output(__("3. (3/8)映射URI, 回车表示不映射:  "));
		$uri = get_input();
	}
	
	
	
	echo wrap_output(__("4. (4/8)视图格式(如tpl, xml, json)，多个用空格分隔，为空表示不生成视图:  "));
	$view_format = get_input();
	
	echo wrap_output(__("5. (5/8)是否生成验证器(yn),默认为y:  "));
	$need_validate = get_input();
	$need_validate = $need_validate ? $need_validate : "y";
	
	if($view_format || $need_validate){
		if($view_format && $need_validate){
			echo wrap_output(__("6. (6/8)验证器、视图使用的Model:  "));
		}else if($view_format){
			echo wrap_output(__("6. (6/8)视图使用的Model:  "));
		}else if($need_validate){
			echo wrap_output(__("6. (5/8)验证器使用的Model:  "));
		}
		$model = get_input();
	}
	
	if($view_format){
		echo wrap_output(__("7. (7/8)视图样板组件名:  "));
		$view_tpl = get_input();
	}
	
	echo wrap_output(__("8. (8/8)是否生成Entry文件(y|n):  "));
	$has_entry = get_input();
	
	if($has_entry=="y"){
		echo wrap_output(__("8. (8/8)Entry文件名，回车表示使用控制器名 {$controller}:  "));
		$entry_file = get_input();
		if(!$entry_file){
			$entry_file = $controller;
		}
	}
	
	return array(
		"cmd" => "controller",
		"controller"=>$controller,
		"model"=>$model,
		"novalidate"=>strtolower($need_validate)!="y",
		"view_format"=>$view_format ,
		"module_name"=>$module,
		"uri"=>$uri,
		"view_tpl"=>$view_tpl,
		"entry_file"=>$entry_file,
		"controller"=>$controller,
	);
}

function is_controller_exists($controller, $module){
	if(file_exists(APP_MODULES_INC.$module."/__module__.php")){
		include_once APP_MODULES_INC.$module."/__module__.php";
		$class = ucfirst(strtolower($module))."_Module";
		$object = new $class();
		return $object->get_uris_of_controller($controller);
		
	}
	return false;
}

function display_model_wizard(){
	clear_terminal();
	echo wrap_output(vsprintf(__( "
================================================================
\t\tYANGZIE Generate Script
\t\t易点互联®
================================================================

你将生成Model代码结构，请根据提示进操作，%s返回上一步：
1. (1/4)表名: ")), get_colored_text(" CTRL+B ", "red", "white"));
	while (!is_validate_table(($table=get_input()))){
		echo get_colored_text(wrap_output(vsprintf(__("\t表不存在(%s)，请重输:  "), mysql_error())), "red");
	}

	echo wrap_output(__("2. (2/4)Model类名:  "));
	while (!is_validate_name(($model = get_input()))){
		echo get_colored_text(wrap_output(__("\t类名遵守PHP变量命名规则,  请重输:  ")), "red");
	}
	echo wrap_output(_("3. (3/4)功能模块名,  遵守PHP变量命名规则:  "));
	while (!is_validate_name(($module = get_input()))){
		echo get_colored_text(wrap_output(__("\t功能模块名,  请重输:  ")), "red");
	}

	echo wrap_output(__("4. (4/4)同步方向(model or table), 默认table:  "));
	while (!in_array(($base = get_input()), array("model","table"))){
		echo get_colored_text(wrap_output(__("\t请输入model 或 table:  ")), "red");
	}
	
	return array(
			"cmd" => "model",
			"base"=>$base,
			"module_name"=>$module,
			"class_name"=>$model,
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
	$input = trim(fgets(STDIN));
	is_back($input);
	return $input;
}



function is_back($input){
	if(ord($input)==2){display_home_wizard();die;}
}

function is_validate_name($input){
	return preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $input);
}

function is_validate_table($table){
	$app_module = new App_Module();
	$db = mysql_connect(
			$app_module->get_module_config("db_host"),
			$app_module->get_module_config("db_user"),
			$app_module->get_module_config("db_psw")
	);
	mysql_select_db($app_module->get_module_config("db_name"),$db);
	return mysql_query("show full columns from $table",$db);
}


function clear_terminal(){
	if(PHP_OS=="WINNT"){
		$clear = "cls";
	}else{
		$clear = "clear";
	}
	exec("cls");
}


function wrap_output($msg){
	if(PHP_OS=="WINNT"){
		return iconv("UTF-8", "GB2312//IGNORE", $msg);
	}else{
		return $msg;
	}
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
			echo get_colored_text("file exists\r\n", "red");return;
		}

		$f = fopen($file_path,'w+');
		if(empty($f)){
			echo get_colored_text("can not open file:{$file_path}");return;
		}
		chmod($file_path,0777);
		fwrite($f,$content);
		fclose($f);
		echo get_colored_text("OK.\r\n","blue");
	}

}
?>