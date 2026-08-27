<?php
namespace yangzie;
/**
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射
 */
/**
 * 路由管理类
 *
 * 该文件中定义了系统的所有资源及这些资源对应的控制器映射，
 * 负责收集并加载各模块配置中的路由表（routers）。
 *
 * @package yangzie
 */
class YZE_Router extends YZE_Object {
	/**
	 * 路由表单例实例
	 *
	 * @var YZE_Router|null
	 */
	private static $instance;

	/**
	 * 各模块的路由映射表，结构：
	 * [
	 *   '模块名' => [
	 * 	    'uri地址'=>["controller"=>'控制器名', 'action'=>'执行的方法', "args"=>["固定参数名"=>"参数值"]]
	 *   ]
	 * ]
	 *
	 * @var array
	 */
	private $mappings = array();

	/**
	 * 私有构造函数，禁止外部直接实例化，请使用 get_Instance() 获取单例
	 */
	private function __construct(){}

	/**
	 * 获取路由管理单例
	 *
	 * @return YZE_Router 路由管理实例
	 */
	public static function get_Instance(){
		if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
	}

	/**
	 * 设置指定模块的路由映射表
	 *
	 * @param string $module 模块名
	 * @param array  $vars   该模块的路由映射表
	 * @return void
	 */
	public function set_Routers($module,$vars){
		$this->mappings[$module] = $vars;
	}

	/**
	 * 获取路由映射表
	 *
	 * @param string|null $module 模块名，为 null 时返回全部模块的路由表
	 * @return array 路由映射表
	 */
	public function get_Routers($module=null){
		return $module ? $this->mappings[$module] : $this->mappings;
	}

	/**
	 * 加载所有模块的路由配置
	 *
	 * 遍历 YZE_APP_MODULES_INC 目录下的每个模块，读取其 __config__.php，
	 * 实例化模块类并获取 routers 配置写入路由表
	 *
	 * @return void
	 */
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
			}
		}
	}
}

?>
