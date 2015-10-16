<?php
namespace yangzie;
/**
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射
 */
class YZE_Router{
	private static $instance;
	private $mappings = array(/*"__yze__"=>array(
			'yze.rpc'	=> array('controller'	=> 'yangzie\yze_default',
        		'args'	=> array(),
        	))*/);
	private function __construct(){}
	/**
	 * 
	 *
	 * @return Router
	 */
	public static function get_Instance(){
		if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
	}
	public function set_Routers($module,$vars){
		$this->mappings[$module] = $vars;
	}
	public function get_Routers($module=null){
		return $module ? $this->mappings[$module] : $this->mappings;
	}

	public static function load_routers(){
		
		foreach(glob(YZE_APP_MODULES_INC."*") as $module){
			$phar_wrap = is_file($module) ? "phar://" :"";
			
			if(@file_exists("{$phar_wrap}{$module}/__module__.php")){
				include_once "{$phar_wrap}{$module}/__module__.php";
				$module_name = strtolower(basename($module));
				if($phar_wrap) {
					$module_name = ucfirst(preg_replace('/\.phar$/',"", $module_name));
				}
				$class = "\\app\\{$module_name}\\".ucfirst($module_name)."_Module";
				$object = new $class();
				$mappings = $object->get_module_config('routers');
				if($mappings){
					YZE_Router::get_Instance()->set_Routers($module_name,$mappings);
				}
			}
		}
	}
}

YZE_Hook::add_hook(YZE_FILTER_BEFORE_CHECK_REQUEST_TOKEN, function($data){
	$refer_uri = trim(YZE_Request::get_instance()->the_uri(), "/");
	
	if( ! in_array(strtolower($refer_uri), array("yze.rpc"))){
		return $data;
	}

	return array("saved_token"=>"anything", "post_request_token"=>"anything");
})

?>