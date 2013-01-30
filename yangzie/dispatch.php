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
		$this->conn = YZE_DBAImpl::getDBA();
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
		$request = Request::get_instance();
		//如果控制器配置了缓存，则判断是否有缓存，有则直接输出缓存
		if(($cache_html = $controller->has_response_cache())){
			return new Notpl_View($cache_html, $controller);
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
	public function init($controller = null){
		$request = Request::get_instance();
		$request_method = $request->method();
		
		if($controller==""){//from entry
			//nothing todo, will goto yangzie default controller
		}elseif( !$controller ){
			//默认按照 /module/controller/var/ 解析
			$uri = $request->the_uri();
			$uri_split 			= explode("/", trim($uri, "/"));
			$curr_module 		= strtolower($uri_split[0]);
			$controller_name= $this->the_val(strtolower(@$uri_split[1]), "index");
			
			if($curr_module){
				$this->set_module($curr_module)->set_controller($controller_name);
			}
			
			$routers = YZE_Router::get_instance()->get_routers();
			
			//根据配置中的routes来映射
			$router_info 		= $this->_get_routers($routers, $uri);
			if($router_info){
				$controller_name 	= @$router_info['controller_name'];
				$curr_module 		= @$router_info['module'];
				$config_args 		= @$router_info['args'];
			}
		}else{//from entry
			$uri_split 				= explode("/", trim($controller, "/"));
			$def_curr_module 		= strtolower($uri_split[0]);
			$def_controller_name= $this->the_val(strtolower(@$uri_split[1]), "index");
				
			if($def_curr_module){
				$this->set_module($def_curr_module)->set_controller($def_controller_name);
			}
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
		@include APP_PATH."modules/".$this->module()."/controllers/".$this->controller_file();
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
		foreach (explode("_", $module) as $word) {
			$class[] = ucfirst(strtolower($word));
		}
		$this->module_class = join("_", $class)."_Module";
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
	 * @return Base_Module
	 */
	public function module_class()
	{
		return $this->module_class;
	}
	/**
	 * @return Base_Module;
	 */
	public function module_obj()
	{
		return $this->module_obj;
	}
	
	public function view_path()
	{
		return APP_PATH."modules/".$this->module()."/views";
	}
	

	/**
	 *
	 *
	 * @param unknown_type $routers
	 * @param unknown_type $uri
	 *
	 * @return Array('controller_name'=>, 'module'=>, 'args'=>)
	 */
	private function _get_routers($routers, $uri)
	{
		$_ = array();
		foreach ($routers as $module=>$router_info) {
			foreach ($router_info as $router => $acontroller) {
				if (preg_match("#^{$router}$#i", $uri, $matches)) {
					$_['controller_name'] = strtolower($acontroller['controller']);
					foreach ((array)$acontroller['args'] as $name => $value){
						if (substr($value, 0, 2) == "r:") {
							$name = substr($value,2);
							$config_args[$name] = @$matches[$name];
						}else{
							$config_args[$name] = $value;
						}
					}
					$_['args'] 	= @$config_args;
					$_['module'] = $module;
					return $_;
				}
			}
		}
		return $_;
	}
}
?>