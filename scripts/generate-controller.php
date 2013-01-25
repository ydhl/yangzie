<?php
class Generate_Controller_Script extends AbstractScript{
	private $controller;
	private $novalidate;
	private $view_format;
	private $module_name;
	private $uri;
	private $view_tpl;
	private $model;
	private $uri_args = array();

	protected $http_methods = array("get","post","delete","put");
	
	public function generate(){
		$argv = $this->args;
		$this->controller		= $argv['controller'];
		$this->novalidate		= $argv['novalidate'];
		$this->view_format 		= $argv['view_format'];
		$this->module_name 	= $argv['module_name'];
		$this->uri 				= $argv['uri'];
		$this->view_tpl 			= $argv['view_tpl'];

		$generate_module = new Generate_Module_Script(array("module_name" => $this->module_name));
		$generate_module->generate();

		$this->parse_uri_args();
		$this->save_class();
		$this->save_test();
		$this->check_action();
		echo "update __module__ file :\t";
		$this->update_module();
		echo get_colored_text("Ok.\r\nDone.", "blue");
	}
	
	private function update_module(){
		$module = $this->module_name;
		$path = dirname(dirname(__FILE__))."/app/modules/".$module;
		//配置res的默认router
		$module_file = "$path/__module__.php";
		include_once $module_file;
		$module_cls = $module."_Module";
		$module = new $module_cls;
		$ref_cls 	= new ReflectionClass($module_cls);
		$method 	= $ref_cls->getMethod("_config");
		$method->setAccessible(true);
		$configs = $method->invoke($module);
		if($this->uri && !@$configs['routers'][$this->uri]){
			$configs['routers'][$this->uri] = array("controller"=>$this->controller, "args"=>$this->uri_args);
			$config_str = $this->_arr2str($configs, "\t\t");
				
			$start_line = $method->getStartLine();
			$end_line 	= $method->getEndLine();
			$new_content= "";
			$fp 		= fopen($module_file, "r+");
			$cur_line 	= 1;
			while ( ($line = fgets($fp)) !== false) {
				if($cur_line >= $start_line && $cur_line <= $end_line){
					if($cur_line == $end_line){
						$new_content .= "\tprotected function _config(){\r\n\t\treturn ".$config_str."\r\n\t}\r\n";
					}
				}else{
					$new_content .= $line;
				}
				++$cur_line;
			}
			fseek($fp, 0);
			ftruncate($fp, 0);
			fwrite($fp, $new_content);
			fclose($fp);
		}
		
	}
	
	
	private function _arr2str(array $array, $tab, $is_end=true)
	{
		$str = "array(\r\n";
		foreach ($array as $name => $value){
			$str .= $tab."\t".(is_numeric($name) ? $name : "'$name'")."\t=> ";
			if(is_array($value)){
				$str .= $this->_arr2str($value, $tab."\t", false);
			}else{
				$str .= "'$value',\r\n";
			}
		}
		$str .= $tab.")".($is_end ? ";" : ",\r\n");
		return $str;
	}
	private function parse_uri_args(){
		if(preg_match_all("/\?P\<(?P<args>[^\>]+)\>/i", $this->uri, $matches)){
			foreach($matches['args'] as $arg){
				$this->uri_args[] = 'r:'.$arg;
			}
		}
	
	}
	
	private function save_test(){
		$module = $this->module_name;
		$controller = $this->controller;
		if(empty($controller)){
			return;
		}
		echo "create controller phpt file:\t";
		$class = YZE_Object::format_class_name($controller,"Controller");
		$class_file_path = dirname(dirname(__FILE__))
		."/tests/". $module."/" ."".strtolower($class).".class.phpt";
		$test_file_content = "--TEST--
		$class class Controller Unit Test
--FILE--
<?php
ini_set(\"display_errors\",0);
chdir(dirname(dirname(dirname(__FILE__))).\"/app/public_html\");
include \"init.php\";
include \"load.php\";
//write you test code here
?>
--EXPECT--";
		$this->create_file($class_file_path,$test_file_content);
	}
	
	
	
	private function create_controller($controller){
		$module = $this->module_name;
	
		$class = YZE_Object::format_class_name($controller,"Controller");
		$class_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/controllers/".strtolower($class).".class.php";
		$class_file_content = "<?php
/**
*
* @version \$Id\$
* @package $module
*/
class $class extends YZE_Resource_Controller {
	public function get_response_guid(){
		//如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
		//该id根据请求的输入参数生成
		return null;
	}
	protected function post_result_of_ajax(){
		//这里返回该控制器在ajax请求时返回地数据
		return array();
	}
	protected \$module_name = \"$module\";
	protected \$models = array();
	
}
?>";
		echo "create controller:\t\t";
		$this->create_file($class_file_path, $class_file_content);
	}
	
	private function save_class(){
		$module = $this->module_name;
		$controller = $this->controller;
	
		//create controller
		$this->create_controller($controller);
	}
	
	
	private function check_action(){
		//找到controller，判断其中有没有action，如果没有则生成action，及对应的view，validate，test
		$controller_file = '../modules/'.strtolower($this->module_name).'/controllers/'.strtolower($this->controller).'_controller.class.php';
		include_once $controller_file;
		$controller_class = YZE_Object::format_class_name($this->controller, "Controller");
		$refl = new ReflectionClass($controller_class);
		$methods = $this->http_methods;
		$action_code = "";
		
		foreach($methods as $method){
			if(!$refl->hasMethod($method)){
				$action_code .= "		
	public function $method(){
		//Your Code Written in Here.
				
		".($method=="get" ? '$this->set_view_data("yze_page_title", "this is controller '.$this->controller.'");' : "")."
	}
	";
			}

			if($method == "get" && $this->view_format){
				$this->create_view();
			}
		}
		
		if(!$this->novalidate){
			$this->create_validate();
		}
	
		$contents = file_get_contents($controller_file);
		$contents = preg_replace("/(class $controller_class extends YZE_Resource_Controller {)/m", "\\1\r\n$action_code", $contents);
		echo "update controller :\t\t";
		$this->create_file($controller_file, $contents,true);
	}
	
	private function validate_code_segment($method){
		$code = "";
		if(!$this->model)return $code;
		foreach ($this->input as $input){
			if(stripos(@$input['data-source'], $method)===FALSE)continue;
			$validate_name = strtolower(substr($input['validate']['name'], 0, 10)) == "validate::" ? $input['validate']['name'] : "'{$input['validate']['name']}'";
			$code .= @"\$this->set_validate_rule('{$method}', '{$input[name]}', {$validate_name}, '{$input[validate][regx]}', '{$input[validate][message]}');
";
		}
		return $code;
	}

	protected function create_view(){
		$module = $this->module_name;
		$controller = $this->controller;
		$format = explode(" ", $this->view_format);
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/views");
		foreach ($format as $_format){
			$view_file_path = dirname(dirname(__FILE__))
			."/app/modules/". $module."/views/{$controller}.{$_format}.php";
			$view_file_content = "<?php
/**
 * 视图的描述
 * @param type name optional
 *
 */
?>

this is {$controller} view";
			echo("create view :\t\t\t");
			$this->create_file($view_file_path, $view_file_content);
		}
	}
	protected function create_validate(){
		$module 	= $this->module_name;
		$controller = $this->controller;
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/validates");
	
		$validate_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/validates/{$controller}_validate.class.php";

		$validate_file_content = "<?php
/**
 *
 * @version \$Id\$
 * @package $module
 */
class ".YZE_Object::format_class_name($controller, "Validate")." extends YZEValidate{
	
	public function init_get_validates(){
		".$this->validate_code_segment("get")."
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('get', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_post_validates(){
		".$this->validate_code_segment("post")."
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_put_validates(){
		".$this->validate_code_segment("put")."
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_delete_validates(){
		".$this->validate_code_segment("delete")."
		//Written Get Validate Rules Code in Here. such as
		//\$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
}?>";
		echo("create validate :\t\t");
		$this->create_file($validate_file_path, $validate_file_content);
	}
}
?>
