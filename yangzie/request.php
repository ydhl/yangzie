<?php
/**
 * 一次处理上下文，是一个缓存机制，负责管理一次请求中使用到的数据库连接，实体缓存
 * 及其它需要缓存到会话中的内容
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii, <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     www.yangzie.net
 */
class Request extends YZE_Object
{
	private $orgi_uri;
	private $controller;
	private $controller_class;
	private $controller_obj;
	private $module_class;
	private $module_obj;
	/**
	 * App_Module
	 * @var App_Module
	 */
	private $app_config;
	private $module;
	private $action;
	private $view_path;
	private $method;
	private $vars;
	
	/*给error controller用*/
	private $request_view_path;
	private $request_module;
	private $request_controller;
	private $request_controller_class;
	private $request_controller_obj;

	/**
	 *
	 * @var Request_Data
	 */
	private $request_data;

	/**
	 * 会话中查询出来的实体:
	 * array(
	 * 	'table name'=>array(
	 * 		'key hash'=>entity
	 * 	)
	 * )
	 *
	 * @var array
	 */
	private $entity_cache=array();
	/**
	 *
	 *
	 * @var PDO
	 */
	private $conn;
	/**
	 * 通用缓存，hash map
	 *
	 * @var array
	 */
	private $cache = array();

	private static $instance;


	private function __construct()
	{
		$this->conn = YZE_DBAImpl::getDBA();
	}

	/**
	 * 从实体缓存中取得实体，没有取到返回null
	 *
	 * @param unknown_type $id
	 */
	public function get_entity_from_cache($class_name,$id)
	{
		if (empty($id) || empty($this->entity_cache)) return null;
		if (is_array($id)) {
			return $this->entity_cache[$class_name][hash('md5', implode(",", array_values($id)))];
		}else{
			return $this->entity_cache[$class_name][hash('md5', $id)];
		}
	}
	/**
	 * 把实体保存在缓存中
	 *
	 * @param unknown_type $entity
	 */
	public function save_entity_to_cache(Model $entity)
	{
		if (empty($entity))return;
		$this->entity_cache[get_class($entity)][hash('md5', implode(",", array_values($entity->the_key())))] = $entity;
		return;
	}
	public function remove_entity_from_cache($class_name, $id)
	{
		if (empty($id)) return;
		$this->entity_cache[$class_name][hash('md5', $id)] = null;//TODO
		return;
	}

	/**
	 *
	 *
	 * @return Request
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	/**
	 * 在create，update，delete操作时开启事务
	 */
	public function begin_transaction()
	{
		if ($this->is_get()) {
			return;
		}
		//TODO 如果没有数据库支持，则不用
		$this->conn->beginTransaction();
	}

	/**
	 * Short description of method commit
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return void
	 */
	public function commit()
	{
		if ($this->is_get()) {
			return;
		}
		do_action(YZE_HOOK_TRANSACTION_COMMIT);
		$this->conn->commit();
	}

	/**
	 * Short description of method rollback
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return void
	 */
	public function rollback()
	{
		if ($this->is_get()) {
			return;
		}
		try{
			$this->conn->rollBack();
		}catch (Exception $e){}
	}


	public function get_from_cache($key, $default=null)
	{
		if(array_key_exists($key, $this->cache)){
			return @$this->cache[$key];
		}
		return $default;
	}
	public function save_to_cache($key, $value)
	{
		$this->cache[$key] = $value;
	}
	/**
	 * 预处理请求数据，把get，post，cookie等数据进行urldecode后编码
	 */
	public function prepare_request_data()
	{
		$this->request_data = new Request_Data();
	}
	public function the_post_data()
	{
		return $this->request_data->the_post_data();
	}
	public function get_from_post($name, $default=null)
	{
		return @$this->request_data->get_from_post($name, $default);
	}
	public function get_from_get($name, $default=null)
	{
		return @$this->request_data->get_from_get($name, $default);
	}
	public function get_from_cookie($name, $default=null, $default)
	{
		return @$this->request_data->get_from_cookie($name);
	}
	/**
	 * 系统处理入口，该方法在入口文件index.php中调用，
	 * 该方法的作用是根据URI把资源的请求操作转发到正确的资源上，并把处理结果返回。
	 *
	 * @return IResponse
	 * @author liizii, <libol007@gmail.com>
	 *
	 */
	public function dispatch()
	{
		$controller = $this->controller_obj;
		//如果控制器配置了缓存，则判断是否有缓存，有则直接输出缓存
		if(($cache_html = $controller->has_response_cache())){
			return new Notpl_View($cache_html, $controller);
		}else{
			$method = "do_".$this->method();
			return $controller->$method();
		}
	}
	public function init_request()
	{
		$request_method = self::the_val($this->get_from_post("yze_method"), strtolower($_SERVER['REQUEST_METHOD']));
		$this->method($request_method);

		if  (!$this->is_get() && $this->the_post_data()) {
			Session::get_instance()->save_post_datas($this->the_uri(), $this->the_post_data());
		}

		//如果有back_uri，把它放在会话里,使用的地方，使用后清空
		if (@$_REQUEST["back_uri"]) {
			Session::get_instance()->save_back_uri($_REQUEST["back_uri"]);
		}

		//默认按照 /module/controller/var/ 解析
		$uri = $this->request_data->the_uri();
		$uri_split 			= explode("/", trim($uri, "/"));
		$curr_module 		= $this->the_val(strtolower($uri_split[0]), "home");
		$def_controller_name= $this->the_val(strtolower(@$uri_split[1]), "index");

		$this->orgi_uri($uri)
		->controller($def_controller_name)
		->module($curr_module)
		->request_module($curr_module)
		->request_controller($def_controller_name);

		$routers = Router::get_instance()->get_routers();

		//根据配置中的routes来映射 TODO 性能优化
		$router_info 		= $this->_get_routers($routers, $uri);
		if($router_info){
			$controller_name 	= @$router_info['controller_name'];
			$curr_module 		= @$router_info['module'];
			$config_args 		= @$router_info['args'];
		}

		$this->vars(array_merge(HTTP::parse_uri_to_args($uri), @(array)$config_args));

		if (!empty($controller_name)) {
			$this->controller($controller_name)
			->module($curr_module)
			->request_module($curr_module)
			->request_controller($controller_name);
		}

		$controller_cls = $this->controller_class();
		$controller_file = $this->controller_file();
		@include APP_PATH."modules/".$this->module()."/controllers/".$controller_file;
		if (!class_exists($controller_cls)) {
			throw new YZE_Controller_Not_Found_Exception($controller_cls);
		}
		
		$controller = $this->controller_obj = new $controller_cls();
		if (!method_exists($controller, $request_method)) {
			throw new YZE_Action_Not_Found_Exception($controller_cls."::".$request_method);
		}
		return $this;
	}
	public function orgi_uri()
	{
		return $this->get_set_property("orgi_uri", func_get_args());
	}
	public function request_controller()
	{
		$return = $this->get_set_property("request_controller", func_get_args());
		if (func_get_args()) {
			#清空缓存相关的东东
			$this->request_controller_class = null;
			$this->request_controller_obj = null;
		}
		return $return;
	}
	public function request_controller_class()
	{
		if ($this->request_controller_class) {
			return $this->request_controller_class;
		}
		$this->request_controller_class = self::format_class_name($this->request_controller(), "Controller");
		return $this->request_controller_class;
	}
	public function request_controller_obj()
	{
		if ($this->request_controller_obj) return $this->request_controller_obj;
		$class = $this->request_controller_class();
		$this->request_controller_obj = new $class;
		return $this->request_controller_obj;
	}
	public function request_controller_file()
	{
		$class = $this->request_controller_class();
		return strtolower($class).".class.php";
	}

	public function controller()
	{
		$return = $this->get_set_property("controller", func_get_args());
		if (func_get_args()) {
			#清空缓存相关的东东
			$this->controller_class = null;
			$this->controller_obj = null;
		}
		return $return;
	}
	public function controller_class()
	{
		if ($this->controller_class) {
			return $this->controller_class;
		}
		$this->controller_class = self::format_class_name($this->controller(), "Controller");
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
		if ($this->controller_obj) return $this->controller_obj;
		$class = $this->controller_class();
		$this->controller_obj = new $class;
		return $this->controller_obj;
	}
	public function controller_file()
	{
		$class = $this->controller_class();
		return strtolower($class).".class.php";
	}
	public function module()
	{
		$return = $this->get_set_property("module", func_get_args());
		if(func_get_args()){
			#清空缓存相关的东东
			$this->module_class = null;
			$this->module_obj = null;
		}
		return $return;
	}
	/**
	 *
	 * @return Base_Module
	 */
	public function module_class()
	{
		if ($this->module_class) {return $this->module_class;}

		foreach (explode("_", $this->module()) as $word) {
			$class[] = ucfirst(strtolower($word));
		}
		$this->module_class = join("_", $class)."_Module";
		return $this->module_class;
	}
	/**
	 * @return Base_Module;
	 */
	public function module_obj()
	{
		if ($this->module_obj) {return $this->module_obj;}
		$class = $this->module_class();
		$this->module_obj = new $class();
		return $this->module_obj;
	}

	public function view_path()
	{
		return APP_PATH."modules/".$this->module()."/views";
	}
	/**
	 * 请求的方法：get,post,put,delete
	 */
	public function method()
	{
		return $this->get_set_property("method", func_get_args());
	}
	public function vars()
	{
		return $this->get_set_property("vars", func_get_args());
	}
	public function get_var($key, $default=null)
	{
		$vars = $this->vars();
		return array_key_exists($key, $vars) ? $vars[$key] : $default;
	}

	public function request_view_path()
	{
		return $this->request_module()."/views/".$this->request_controller();
	}
	public function request_module()
	{
		return $this->get_set_property("request_module", func_get_args());
	}


	/**
	 * 请求的资源的URI，每次请求，URI是唯一且在一次请求内是不变的
	 * 返回的只是uri中的路径部分，query部分不包含，如/people-1/question-2/answers?p=3
	 * 只返回/people-1/question-2/answers
	 * @return string
	 * @author liizii, <libol007@gmail.com>
	 */
	public function the_uri()
	{
		return $this->request_data->the_uri();
	}
	/**
	 * 请求的路径及query strin
	 * @return unknown
	 */
	public function the_full_uri()
	{
		return $this->request_data->the_full_uri();
	}
	public function the_query()
	{
		return $this->request_data->the_query();
	}

	/**
	 * 每个人每次请求的token都是唯一的
	 */
	public function the_request_token()
	{
		return $this->request_data->the_request_token();
	}
	public function getScheme()
	{
		return $this->request_data->getScheme();
	}
	public function the_server_uri()
	{
		return $this->request_data->the_server_uri();
	}

	public function is_post()
	{
		return strcasecmp($this->method(), "post")===0;
	}
	public function is_get()
	{
		return strcasecmp($this->method(), "get")===0;
	}

	/**
	 *
	 * @param $just_path 如果为true只显示uri的path部分
	 */
	public function the_referer_uri($just_path=false)
	{
		$referer = $this->request_data->the_referer_uri();
		if (!$just_path) {
			return $referer;
		}
		return parse_url($referer, PHP_URL_PATH);
	}

	public function auth()
	{
		$req_method = $this->method();
		if($this->need_auth($req_method)) {//需要验证
			$app_module = new App_Module();
			if ( !class_exists("App_Auth")) {
				throw new YZE_Not_Found_Class_Exception(
				vsprintf(__("未找到认证实现类App_Auth")));
			}
			
			$authobject = new App_Auth();
			
			//先验证是否登录 
			$authobject->do_auth();
			
			//验证请求的方法是否有权限调用
			$acl = YZE_ACL::get_instance();
			$aco_name = "/".$this->module()."/".$this->controller()."/".$req_method;
			if(!$acl->check_byname($authobject->get_request_aro_name(), $aco_name)){
				throw new YZE_Permission_Deny_Exception(vsprintf(__("没有访问资源 <strong>%s</strong> 的权限"), 
				array(yze_get_aco_desc($aco_name))));
			}
		}
	}


	/**
	 *
	 * 如果uri指定有验证，则对请求数据进行验证，验证失败抛出YZE_Request_Validate_Failed异常
	 */
	public function validate()
	{
		$request_method = $this->method();
// 		YZE_Object::silent_include_file($this->module().'/validates/'.$this->controller()."_validate.class.php");
		$validate_cls = self::format_class_name($this->controller(),"Validate");

		if(!class_exists("$validate_cls"))return;
		$validate = new $validate_cls();
		$validate_method = "init_{$request_method}_validates";
		$validate->$validate_method();
		return $validate->do_validate($request_method);
	}

	public function check_request(YZE_HttpCache $cache)
	{
		if (!$cache)return;
		if ($cache->last_modified() && @$this->request_data->get_from_server('HTTP_IF_MODIFIED_SINCE')) {
			if (strtotime($cache->last_modified()) == strtotime($this->request_data->get_from_server('HTTP_IF_MODIFIED_SINCE'))) {
				throw new YZE_Not_Modified_Exception();
			}
		}
		if ($cache->etag() && @$this->request_data->get_from_server('HTTP_IF_NONE_MATCH')) {
			if (strcasecmp($cache->etag(),$this->request_data->get_from_server('HTTP_IF_NONE_MATCH'))==0) {
				throw new YZE_Not_Modified_Exception();
			}
		}
	}
	
	
	/**
	 * 
	 * 取得请求指定的输出格式
	 * 
	 * @author leeboo
	 * 
	 * 
	 * @return
	 */
	public function get_output_format(){
		$format = $this->get_var("__format__");//api 指定的输出格式,如http://domain/action.json
		if($format){
			return $format;
		}elseif($this->is_mobile_client()){//客户端是移动设备
			return "mob";
		}
		return "";//default
	}
	
	public function is_mobile_client(){
		return preg_match("/android|iphone|ipad/i", $_SERVER['HTTP_USER_AGENT']);
	}
	
	private function get_set_property()
	{
		$property = func_get_arg(0);
		$args = func_get_arg(1);
		if ($args) {
			$this->$property = $args[0];
			return $this;
		}
		return $this->$property;
	}


	private function need_auth($req_method)
	{
		$need_auth_methods = $this->get_auth_methods($this->controller(), "need");
		$no_auth_methods = $this->get_auth_methods($this->controller(), "noneed");
		
		//不需要验证
		if($no_auth_methods && ($no_auth_methods=="*" || preg_match("/$no_auth_methods/", $req_method))) {
			return false;
		}
		if($need_auth_methods && ($need_auth_methods=="*" || preg_match("/$need_auth_methods/", $req_method))) {//需要验证
			return true;
		}
		return false;
	}


	private function get_auth_methods($controller_name, $type){
		if($type=="need"){
			$auth_methods = @$this->module_obj()->auths[$controller_name];
			if($auth_methods)return $auth_methods;
				
			$auth_methods = @$this->module_obj()->auths["*"];
			if($auth_methods)return $auth_methods;
		}elseif($type=="noneed"){
			$auth_methods = @$this->module_obj()->no_auths[$controller_name];
			if($auth_methods)return $auth_methods;
				
			$auth_methods = @$this->module_obj()->no_auths["*"];
			if($auth_methods)return $auth_methods;
		}
		return null;
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

/**
 * 请求数据操作类
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii, <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     www.yangzie.net
 */
class Request_Data extends YZE_Object
{
	private $post = array();
	private $get = array();
	private $cookie = array();
	private $server = array();
	private $env = array();


	/**
	 * 预处理请求数据，把get，post，cookie等数据进行urldecode后编码
	 */
	public function Request_Data()
	{
		$this->post 	= self::filter_special_chars($_POST, INPUT_POST);
		$this->get 		= self::filter_special_chars($_GET, INPUT_GET);
		$this->cookie 	= self::filter_special_chars($_COOKIE, INPUT_COOKIE);
		$this->env 		= self::filter_special_chars($_ENV, INPUT_ENV);
		$this->server 	= self::filter_special_chars($_SERVER, INPUT_SERVER);
	}
	public function the_post_data()
	{
		return $this->post;
	}
	public function get_from_post($name, $default=null)
	{
		if(array_key_exists($name, $this->post)){
			return @$this->post[$name];
		}
		return $default;
	}
	public function get_from_server($name)
	{
		return @$this->server[$name];
	}
	public function get_from_cookie($name, $default=null)
	{
		if(array_key_exists($name, $this->cookie)){
			return @$this->cookie[$name];
		}
		return $default;
	}
	public function get_from_get($name, $default=null)
	{
		if(array_key_exists($name, $this->get)){
			return @$this->get[$name];
		}
		return $default;
	}
	public function the_referer_uri()
	{
		return @$_SERVER['HTTP_REFERER'];
	}

	/**
	 * 请求的资源的URI，每次请求，URI是唯一且在一次请求内是不变的
	 * 返回的只是uri中的路径部分，query部分不包含，如/people-1/question-2/answers?p=3
	 * 只返回/people-1/question-2/answers
	 * 返回的url进行了urldecode
	 * @return string
	 * @author liizii, <libol007@gmail.com>
	 */
	public function the_uri()
	{
		$uri = do_filter(YZE_HOOK_FILTER_URI,urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
		return is_array($uri) ? "/".implode("/",$uri) : $uri;
	}
	/**
	 * 请求的路径及query strin
	 * 返回的url没有urldecode
	 * @return unknown
	 */
	public function the_full_uri()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function the_query()
	{
		return $_SERVER['QUERY_STRING'];
	}

	/**
	 * 每个人每次请求的token都是唯一的
	 */
	public function the_request_token()
	{
		return session_id().'_'.$_SERVER['REQUEST_TIME'];
	}
	/**
	 */
	public function getScheme()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$scheme .= 's';
		}
		return $scheme;
	}
	/**
	 */
	public function the_server_uri()
	{
		return sprintf("%s://%s:%s/", getScheme(), $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
	}
}
?>