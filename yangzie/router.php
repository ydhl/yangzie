<?php
namespace yangzie;
/**
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射
 */
class YZE_Router{
	private static $instance;
	private $mappings = array();
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
			if(@file_exists("{$module}/__module__.php")){
				include_once "{$module}/__module__.php";
				$module_name = strtolower(basename($module));
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
?>