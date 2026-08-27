<?php
namespace yangzie;
/**
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射
 */
class YZE_Router extends YZE_Object {
	private static $instance;
	/**
	 * [
	 *   'routers' => [
	 * 	    'uri地址'=>["controller"=>'控制器名', 'aciton'=>'执行的方法',"args"=>["固定参数名"=>"参数值"]]
	 *   ]
	 * ]
	 * @var array
	 */
	private $mappings = array();
	private function __construct(){}
	/**
	 *
	 *
	 * @return YZE_Router
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

			// ai@2026-05-27 去掉冗余 @，file_exists 不会产生错误
			if(file_exists("{$phar_wrap}{$module}/__config__.php")){
				include_once "{$phar_wrap}{$module}/__config__.php";
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

				\yangzie\YZE_Object::set_loaded_modules($module_name, array(
					"is_phar" => $phar_wrap ? true : false
				));
			}
			YZE_Hook::include_hooks($module_name, "{$phar_wrap}{$module}/hooks");
		}
	}
}

?>
