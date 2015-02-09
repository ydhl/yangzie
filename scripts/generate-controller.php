<?php
namespace yangzie;

class Generate_Controller_Script extends AbstractScript{
	private $controller;
	private $novalidate;
	private $view_format;
	private $module_name;
	private $uri;
	private $view_tpl;
	private $entry_file;
	private $model;
	private $uri_args = array();

	
	public function generate(){
		$argv = $this->args;
		$this->controller		= $argv['controller'];
		$this->novalidate		= $argv['novalidate'];
		$this->view_format 		= $argv['view_format'];
		$this->module_name 		= $argv['module_name'];
		$this->uri 				= $argv['uri'];
		$this->view_tpl 		= $argv['view_tpl'];
		//$this->entry_file 		= $argv['entry_file'];

		$generate_module = new Generate_Module_Script(array("module_name" => $this->module_name));
		$generate_module->generate();

		//$this->save_entry();
		$this->save_class();
		$this->save_test();
		
		echo __("update __module__ file :\t");
		$this->update_module();
		echo get_colored_text(__("Ok."), "blue","white")."\r\nDone.";
	}
	
	private function update_module(){
		$module = $this->module_name;
		$path = dirname(dirname(__FILE__))."/app/modules/".$module;

		$module_file = "$path/__module__.php";
		include_once $module_file;
		$module_cls = "\\app\\".$this->module_name."\\".$module."_Module";
		$module = new $module_cls;
		$ref_cls 	= new \ReflectionClass($module_cls);
		$method 	= $ref_cls->getMethod("_config");
		$method->setAccessible(true);
		$configs = $method->invoke($module);
		if($this->uri && !@$configs['routers'][$this->uri]){
			$configs['routers'][$this->uri] = array("controller"=>$this->controller, "args"=>$this->uri_args);
			$config_str = $this->_arr2str($configs, "\t\t");
			$start_line = $method->getStartLine();
			$end_line 	= $method->getEndLine();
//  echo $start_line,", ",$end_line;
			$file_content_arr = file($module_file);
			for($i=$start_line; $i<$end_line; $i++){
				unset($file_content_arr[$i]);
			}
// 			echo "\tprotected function _config(){\r\n\t\treturn ".$config_str."\r\n\t}\r\n";
// 			print_r($file_content_arr);die;
			//Tip 数组的索引从0开始
			$file_content_arr[$start_line-1] = "\tprotected function _config(){\r\n\t\treturn ".$config_str."\r\n\t}\r\n";
			$file_content_arr = array_values($file_content_arr);
			file_put_contents($module_file, implode($file_content_arr));
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
	
	
	private function save_test(){
		$module = $this->module_name;
		$controller = $this->controller;
		if(empty($controller)){
			return;
		}
		echo __("create controller phpt file:\t");
		$class = YZE_Object::format_class_name($controller,"Controller");
		$class_file_path = dirname(dirname(__FILE__))
		."/tests/". $module."/" ."".strtolower($class).".class.phpt";
		$test_file_content = "--TEST--
		$class class Controller Unit Test
--FILE--
<?php
namespace app\\$module;
ini_set(\"display_errors\",0);
chdir(dirname(dirname(dirname(__FILE__))).\"/app/public_html\");
include \"init.php\";
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
namespace app\\$module;
use \\yangzie\\YZE_Resource_Controller;
use \\yangzie\\YZE_Request;
use \\yangzie\\YZE_Redirect;
use \\yangzie\\YZE_Session_Context;
use \\yangzie\\YZE_RuntimeException;

/**
*
* @version \$Id\$
* @package $module
*/
class $class extends YZE_Resource_Controller {
    public function get(){
        \$request = \$this->request;
        //\$this->layout = 'tpl name';
        \$this->set_view_data('yze_page_title', 'this is controller ".$this->controller."');
    }

    public function post(){
        \$request = \$this->request;
    }

    public function delete(){
        \$request = \$this->request;
    }

    public function put(){
        \$request = \$this->request;
    }
    
    public function exception(YZE_RuntimeException \$e){
        \$request = \$this->request;
        //get,post,put,delete处理中出现了异常，如何处理，没有任何处理将显示500页面
        //如果想显示get的返回内容可调用 return \$this->wrapResponse(\$this->get())
    }
    
    public function get_response_guid(){
        //如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
        return null;
    }
    
    /*
     * @see YZE_Resouse_Controller::cleanup()
     */
    public function cleanup(){
        //pass
        parent::cleanup();
    }

}
?>";
		echo __("create controller:\t\t");
		$this->create_file($class_file_path, $class_file_content);
		
		if($this->view_format){
			$this->create_view();
			$this->create_layout();
		}
		
		if(!$this->novalidate){
			$this->create_validate();
			$this->create_validate_test();
		}
	}
	
	
	private function save_class(){
		$module = $this->module_name;
		$controller = $this->controller;
	
		//create controller
		$this->create_controller($controller);
	}
	
	
	private function validate_code_segment($method){
		$code = "";
		if(!$this->model)return $code;
		foreach ($this->input as $input){
			if(stripos(@$input['data-source'], $method)===FALSE)continue;
			$validate_name = strtolower(substr($input['validate']['name'], 0, 10)) == "validate::" ? $input['validate']['name'] : "'{$input['validate']['name']}'";
			$code .= @"\$this->assert('{$input[name]}', {$validate_name}, '{$input[validate][regx]}', '{$input[validate][message]}');
";
		}
		return $code;
	}

	protected function create_view(){
		$module = $this->module_name;
		$controller = $this->controller;
		$formats = explode(" ", $this->view_format);
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/views");
		foreach ($formats as $format){
			$view_file_path = dirname(dirname(__FILE__))
			."/app/modules/". $module."/views/{$controller}.{$format}.php";
			$view_file_content = "<?php
namespace app\\$module;
use \\yangzie\\YZE_Resource_Controller;
use \\yangzie\\YZE_Request;
use \\yangzie\\YZE_Redirect;
use \\yangzie\\YZE_Session_Context;
use \\yangzie\\YZE_RuntimeException;

/**
 * 视图的描述
 * @param type name optional
 *
 */
 
\$data = \$this->get_data('arg_name');
?>

this is {$controller} view";
			echo __("create view {$controller}.{$format}.php:\t\t\t");
			$this->create_file($view_file_path, $view_file_content);
		}

	}
	
	protected function create_layout(){
		$module = $this->module_name;
		$controller = $this->controller;
		$formats = explode(" ", $this->view_format);
		
		foreach ($formats as $format){
			$layout = dirname(dirname(__FILE__))."/app/vendor/layouts/{$format}.layout.php";
			if(!file_exists($layout)){
				$layout_file_content = "<?php
/**
  * {$format}布局
  */
echo \$this->content_of_view()
?>
";
				echo __("create layout {$format} :\t\t\t");
				$this->create_file($layout, $layout_file_content);
			}
		}
	}
	
	protected function create_validate(){
		$module 	= $this->module_name;
		$controller = $this->controller;
		$this->check_dir(dirname(dirname(__FILE__))."/app/modules/". $module."/validates");
	
		$validate_file_path = dirname(dirname(__FILE__))
		."/app/modules/". $module."/validates/{$controller}_validate.class.php";

		$validate_file_content = "<?php
namespace app\\$module;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;
use \yangzie\YZEValidate;
/**
 *
 * @version \$Id\$
 * @package $module
 */
class ".YZE_Object::format_class_name($controller, "Validate")." extends YZEValidate{
	
    public function init_get_validates(){
        ".$this->validate_code_segment("get")."
        //Written Get Validate Rules Code in Here. such as
        //\$this->assert('params name in url', 'validate method name', '', 'error message');
    }
    
    public function init_post_validates(){
        ".$this->validate_code_segment("post")."
        //Written Get Validate Rules Code in Here. such as
        //\$this->assert('params name in post', 'validate method name', '', 'error message');
    }
    
    public function init_put_validates(){
        ".$this->validate_code_segment("put")."
        //Written Get Validate Rules Code in Here. such as
        //\$this->assert('params name in post', 'validate method name', '', 'error message');
    }
    
    public function init_delete_validates(){
        ".$this->validate_code_segment("delete")."
        //Written Get Validate Rules Code in Here. such as
        //\$this->assert('params name in post', 'validate method name', '', 'error message');
    }
}?>";
		echo __("create validate :\t\t");
		$this->create_file($validate_file_path, $validate_file_content);
	}

	protected function create_validate_test(){
		$module 	= $this->module_name;
		$controller = $this->controller;
		$this->check_dir(dirname(dirname(__FILE__))."/tests/". $module);
	
		$validate_file_path = dirname(dirname(__FILE__))."/tests/". $module."/{$controller}_validate.class.phpt";
		
		$test_file_content = "--TEST--
{$controller}_validate class Controller Unit Test
--FILE--
<?php
namespace app\\$module;
ini_set(\"display_errors\",0);
chdir(dirname(dirname(dirname(__FILE__))).\"/app/public_html\");
include \"init.php\";
//write you test code here
?>
--EXPECT--";
		echo __("create validate test :\t\t");
		$this->create_file($validate_file_path, $test_file_content);
	}
}
?>
