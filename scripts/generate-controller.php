<?php
class Generate_Controller_Script extends AbstractScript{
	private $controller;
	private $novalidate;
	private $noview;
	private $module_name;
	private $uri;
	private $view_tpl;
	private $yaml;
	private $input;
	private $output;
	private $redirect;
	private $uri_args = array();
	
	const USAGE = "根据yaml文件配置生成生成控制器、action及其对应的view、validate。用法:

php generate.php -cmd controller -gen gen yaml

-cmd 		: controller ：命令名
-gen 		: yaml 配置文件名

eg. php generate.php -cmd controller -gen example.yaml
";

	protected $http_methods = array("get","post","delete","put");
	
	public function generate(){
		$argv = $this->args;
		while($argv){
			$option = strtolower(trim(array_shift($argv)));
			switch ($option){
				case '-gen':
					$this->yaml	= trim(array_shift($argv));break;
				default:break;#忽略其它
			}
		}
		
		if(empty($this->yaml)){
			die(__(Generate_Controller_Script::USAGE));
		}
		
		if(!file_exists(dirname(dirname(__FILE__))."/gen/".$this->yaml)){
			die(__(dirname(dirname(__FILE__))."/gen/".$this->yaml." not found."));
		}
		
		
		$this->load_yaml();
		
		
		if(empty($this->module_name) || empty($this->controller)){
			die("Yaml Config File Not Valid");
		}
		
		$generate_module = new Generate_Module_Script(array("-mod",$this->module_name));
		$generate_module->generate();
		
		if(!$this->uri){
			$this->uri = "/".$this->module_name."/".$this->controller."/?";
		}
		
		$this->parse_uri_args();
		$this->save_class();
		$this->save_test();
		$this->check_action();
		echo "update __module__ file :\t";
		$this->update_module();
		echo "Ok.\r\nDone.";
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
		if(!@$configs['routers'][$this->uri]){
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
				$this->uri_args[] = '"r:'.$arg.'"';
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
		$class = YangzieObject::format_class_name($controller,"Controller");
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
	
		$class = YangzieObject::format_class_name($controller,"Controller");
		$class_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/controllers/".strtolower($class).".class.php";
		$class_file_content = "<?php
/**
*
* @version \$Id\$
* @package $module
*/
class $class extends Resource_Controller {
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
	
	private function load_yaml(){
		$array = Spyc::YAMLLoad(dirname(dirname(__FILE__))."/gen/".$this->yaml);
		$this->controller 	= $array['name'];
		$this->module_name 	= $array['module'];
		$this->uri 			= $array['uri'];
		$this->novalidate 	= !strcasecmp($array['validate'], "yes");
		$this->noview 		= !strcasecmp($array['view'], "yes");
		$this->view_tpl		= $array['view-tpl'];
		$this->input		= $array['input'];
		$this->output		= $array['output'];
		$this->redirect		= $array['redirect'];
	}
	
	private function check_action(){
		//找到controller，判断其中有没有action，如果没有则生成action，及对应的view，validate，test
		$controller_file = '../modules/'.strtolower($this->module_name).'/controllers/'.strtolower($this->controller).'_controller.class.php';
		include_once $controller_file;
		$controller_class = YangzieObject::format_class_name($this->controller, "Controller");
		$refl = new ReflectionClass($controller_class);
		$methods = $this->http_methods;
		$action_code = "";
		foreach($methods as $method){
			if(!$refl->hasMethod($method)){
				$action_code .= "		
	public function $method(){
		".($method=="get" ? '$this->set_view_data(Yangzie_Const::PAGE_TITLE, "set page title");' : "")."
		//Your Code Written in Here.
	}
	";
			}

			if($method == "get" && !$this->noview){
				$this->create_view($this->module_name, $this->controller);
			}
		}
		
		if(!$this->novalidate){
			$this->create_validate($this->module_name, $this->controller);
		}
	
		$contents = file_get_contents($controller_file);
		$contents = preg_replace("/(class $controller_class extends Resource_Controller {)/m", "\\1\r\n$action_code", $contents);
		echo "update controller :\t\t";
		$this->create_file($controller_file, $contents,true);
	}
}
?>
