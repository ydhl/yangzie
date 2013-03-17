<?php
class YZE_Dispatch extends YZE_Object{
	private static $instance;
	private $controller;
	private $controller_class;
	private $controller_obj;
	private $module_class;
	private $module_obj;
	private $module;
	private $view_path;

	private function __construct()
	{
		
	}

	/**
	 * 
	 * @return YZE_Dispatch
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	public function dispatch()
	{
		$controller = $this->controller_obj;
		$request = YZE_Request::get_instance();
		//如果控制器配置了缓存，则判断是否有缓存，有则直接输出缓存
		if(($cache_html = $controller->has_response_cache())){
			return new YZE_Notpl_View($cache_html, $controller);
		}else{
			$method = "do_".$request->method();
			return $controller->$method();
		}
	}
	
	/**
	 * 
	 * 
	 * @author leeboo
	 * 
	 * @param string $controller 如果传入则不解析url，不查找routers；而是直接解析controller，controller的格式是/module name/controller name
	 * @throws YZE_Controller_Not_Found_Exception
	 * @throws YZE_Action_Not_Found_Exception
	 * 
	 * @return
	 */
	public function init($uri = null){
		$request = YZE_Request::get_instance();
		$request_method = $request->method();
		
		if( ! $uri ){
			$uri = $request->the_uri();
		}
			
		$routers = YZE_Router::get_instance()->get_routers();
		$router_info 		= YZE_Request::parse_url($routers, $uri);

		if($router_info){
			$controller_name 	= @$router_info['controller_name'];
			$curr_module 		= @$router_info['module'];
		}

		
		if (  @$curr_module && $controller_name) {
			$this->set_module($curr_module)->set_controller($controller_name);
		}elseif( !$this->controller() ){
			$this->controller = "yze_default";
			$this->controller_class = "YZE_Default_Controller";
			$this->controller_obj = new YZE_Default_Controller();
		}
		
		$controller_cls = $this->controller_class();

		if (!($controller = $this->controller_obj())) {
			throw new YZE_Controller_Not_Found_Exception("Controller $controller_cls Not Found");
		}

		if (!method_exists($controller, $request_method)) {
			throw new YZE_Action_Not_Found_Exception($controller_cls."::".$request_method);
		}
	}
	
	public function set_controller($controller){
		$this->controller_class = self::format_class_name($controller, "Controller");
		$class = $this->controller_class;
		if(file_exists(YZE_APP_PATH."modules/".$this->module()."/controllers/".$this->controller_file())){
			include_once YZE_APP_PATH."modules/".$this->module()."/controllers/".$this->controller_file();
		}
		if(class_exists($this->controller_class)){
			$this->controller_obj = new $class;
		}
		$this->controller = $controller;
		return $this;
	}
	public function controller(){
		return $this->controller;
	}
	
	public function controller_class()
	{
		return $this->controller_class;
	}
	/**
	 *
	 *
	 * @author leeboo
	 *
	 * @return YZE_Resource_Controller
	 */
	public function controller_obj()
	{
		return $this->controller_obj;
	}
	public function controller_file()
	{
		$class = $this->controller_class();
		return strtolower($class).".class.php";
	}
	public function set_module($module)
	{
		$this->module = $module;
		$this->module_class = YZE_Object::format_class_name($module, "Module");
		
		$class = $this->module_class;
		if(class_exists($class)){
			$this->module_obj = new $class();
		}
		return $this;
	}
	public function module()
	{
		return $this->module;
	}
	/**
	 *
	 * @return YZE_Base_Module
	 */
	public function module_class()
	{
		return $this->module_class;
	}
	/**
	 * @return YZE_Base_Module;
	 */
	public function module_obj()
	{
		return $this->module_obj;
	}
	
	public function view_path()
	{
		return YZE_APP_PATH."modules/".$this->module()."/views";
	}
}
?>