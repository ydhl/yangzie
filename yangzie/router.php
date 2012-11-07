<?php

/**
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射
 */
class Router{
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
		$app_module = new App_Module();
		foreach($app_module->get_module_config('modules') as $module){
			if(file_exists(APP_MODULES_INC."/{$module}/__module__.php")){
				include_once APP_MODULES_INC."/{$module}/__module__.php";
				
				$class = ucfirst(strtolower($module))."_Module";
				$object = new $class();
				$mappings = $object->get_module_config('routers');
				if($mappings){
					Router::get_Instance()->set_Routers($module,$mappings);
				}
			}
		}
	}
}
?>