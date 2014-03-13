<?php
namespace yangzie;
/**
 * 一次处理上下文，是一个缓存机制，负责管理一次请求中使用到的数据库连接，实体缓存
 * 及其它需要缓存到会话中的内容
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii, <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     yangzie.yidianhulian.com
 */
class YZE_Request extends YZE_Object
{
	private $method;
	private $vars;

	private $post = array();
	private $get = array();
	private $cookie = array();
	private $server = array();
	private $env = array();
	
	private $controller_name;
	private $controller_class;
	private $controller;
	private $module_class;
	private $module_obj;
	private $module;
	private $view_path;

	private $uri;
	private $full_uri;
	private $queryString;
	
	public function the_post_datas()
	{
		return $this->post;
	}
	
	public function the_get_datas()
	{
		return $this->get;
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

	/**
	 * 请求的资源的URI，每次请求，URI是唯一且在一次请求内是不变的
	 * 返回的只是uri中的路径部分，query部分不包含，如/people-1/question-2/answers?p=3
	 * 只返回/people-1/question-2/answers
	 * 返回的url进行了urldecode
	 *
	 * 如果使用了rewrite则url请实际的地址，如果使用的是path_info，则url为path_info部分，如果是普通的请求，则url是yze_action参数值
	 * 因为采用的是单入口，所以对于后两种请求，真实的url都是domain/index.php
	 *
	 * @return string
	 * @author liizii, <libol007@gmail.com>
	 */
	public function the_uri($uri = null){
		return $this->uri;
	}

	/**
	 * 请求的路径及query strin
	 * 返回的url没有urldecode
	 * @return unknown
	 */
	public function the_full_uri()
	{
		return $this->full_uri;
	}
	
	public function the_query()
	{
		return $this->queryString;
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
	 * 通用缓存，hash map
	 *
	 * @var array
	 */
	private $cache = array();

	private static $instance;


	private function __construct()
	{
		//预处理请求数据，把get，post，cookie等数据进行urldecode后编码

		$this->post 	= $_POST ? self::filter_special_chars($_POST, INPUT_POST) : array();
		$this->get 		= $_GET ? self::filter_special_chars($_GET, INPUT_GET) : array();
		$this->cookie 	= $_COOKIE ? self::filter_special_chars($_COOKIE, INPUT_COOKIE) : array();
		$this->env 		= $_ENV ? self::filter_special_chars($_ENV, INPUT_ENV) : array();
		$this->server 	= $_SERVER ? self::filter_special_chars($_SERVER, INPUT_SERVER) : array();
	}

	/**
	 *
	 *
	 * @return YZE_Request
	 */
	public static function get_instance($newInstance = false)
	{
		if (!isset(self::$instance) && $newInstance) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}
	
	private function _init($newUri){
		$this->method = null;
		$this->vars = null;
		
		$this->controller_name = null;
		$this->controller_class = null;
		$this->controller = null;
		$this->module_class = null;
		$this->module_obj = null;
		$this->module = null;
		$this->view_path = null;
		
		if( ! $newUri){
			switch (YZE_REWRITE_MODE){
				case YZE_REWRITE_MODE_PATH_INFO:	$this->uri = $_SERVER['PATH_INFO']; break;
				case YZE_REWRITE_MODE_REWRITE: 	$this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);break;
				case YZE_REWRITE_MODE_NONE:
				default : $this->uri = $this->get_from_get("yze_action", "/");
			}
			$this->full_uri 		= $_SERVER['REQUEST_URI'];
			$this->queryString 	= $_SERVER['QUERY_STRING'];
		}else{
			$this->uri = parse_url($newUri, PHP_URL_PATH);
			$this->full_uri = $newUri;
			$this->queryString = parse_url($newUri, PHP_URL_QUERY);
		}
		$uri = do_filter(YZE_HOOK_FILTER_URI, urldecode($this->uri));
		$this->uri = is_array($uri) ? "/".implode("/",$uri) : $uri;
	}

	/**
	 * 初始化请求
	 * 解析请求的uri，如果没有传入url，默认解析当前请求的uri
	 * 
	 * @param string $uri
	 * @return YZE_Request
	 */
	public function init($newUri=null){
		//init
		$this->_init($newUri);
		
		$request_method = self::the_val($this->get_from_post("yze_method"), 
				strtolower($_SERVER['REQUEST_METHOD']));
		$this->set_method($request_method);
		
		$uri = $this->the_uri($newUri);
		if( $newUri ){
			parse_str(parse_url($newUri, PHP_URL_QUERY), $args);
			if ($args){
				$this->get = array_merge($this->get, $args);
			}
		}

		$routers = YZE_Router::get_instance()->get_routers();

		$config_args 		= self::parse_url($routers, $uri);

		$this->set_vars(@(array)$config_args['args']);
		
		if($config_args){
			$controller_name 	= @$config_args['controller_name'];
			$curr_module 		= @$config_args['module'];
		}

		if (  @$curr_module && $controller_name) {
			$this->set_module($curr_module)->set_controller_name($controller_name);
		}else/*if( !$this->controller_name() )*/{
			$this->controller_name = "yze_default";
			$this->controller_class = "Yze_Default_Controller";
			$this->controller = new Yze_Default_Controller();
		}
		
		$controller_cls = $this->controller_class();
		
		if (!($controller = $this->controller())) {
			throw new YZE_Resource_Not_Found_Exception("Controller $controller_cls Not Found");
		}
		
		if ( ! method_exists($controller, $request_method) && $request_method != "rpc") {
			throw new YZE_Resource_Not_Found_Exception($controller_cls."::".$request_method);
		}
		
		if  ( ! $this->is_get() and $this->the_post_datas()) {
			YZE_Session_Context::get_instance()->save_post_datas(get_class($this->controller()), $this->the_post_datas());
		}
		
		return $this;
	}

	/**
	 * 请求的方法：get,post,put,delete
	 */
	public function the_method()
	{
		return $this->method;
	}
	
	public function get_var($key, $default=null)
	{
		$vars = $this->vars;
		return array_key_exists($key, $vars) ? $vars[$key] : $default;
	}

	public function is_post()
	{
		return strcasecmp($this->the_method(), "post")===0;
	}
	public function is_get()
	{
		return strcasecmp($this->the_method(), "get")===0;
	}

	/**
	 *
	 * @param $just_path 如果为true只显示uri的path部分
	 */
	public function the_referer_uri($just_path=false)
	{
		$referer =  @$_SERVER['HTTP_REFERER'];
		if (!$just_path) {
			return $referer;
		}
		return parse_url($referer, PHP_URL_PATH);
	}

	public function auth(){
		$req_method = $this->the_method();

		if($this->need_auth($req_method)) {//需要验证
			
			$user_filter = do_filter(YZE_ACTION_CHECK_USER_HAS_LOGIN,  array('user'=>null));
			if( ! $user_filter['user'])throw new YZE_Need_Signin_Exception();
			
			$aro = do_filter(YZE_FILTER_GET_USER_ARO_NAME, array("aro"=>"/"));

			//验证请求的方法是否有权限调用
			$acl = YZE_ACL::get_instance();
			$aco_name = "/".$this->module()."/".$this->controller_name(true)."/".$req_method;
			if(!$acl->check_byname($aro['aro'], $aco_name)){
				throw new YZE_Permission_Deny_Exception(vsprintf(__("没有访问资源 <strong>%s</strong> 的权限"),
						array(\app\yze_get_aco_desc($aco_name))));
			}
		}
		return $this;
	}


	/**
	 *
	 * 如果uri指定有验证，则对请求数据进行验证，验证失败抛出YZE_Request_Validate_Failed异常
	 */
	public function validate(){
		$request_method = $this->the_method();
		
		$validate_cls = self::format_class_name($this->controller_name(),"Validate");

		if(!class_exists("$validate_cls"))return $this;

		$validate = new $validate_cls();
		$validate_method = "init_{$request_method}_validates";
		$validate->$validate_method();
		$validate->do_validate($request_method);
		
		return $this;
	}

	public function check_request(YZE_HttpCache $cache)
	{
		if (!$cache)return;
		if ($cache->last_modified() && @$this->get_from_server('HTTP_IF_MODIFIED_SINCE')) {
			if (strtotime($cache->last_modified()) == strtotime($this->get_from_server('HTTP_IF_MODIFIED_SINCE'))) {
				throw new YZE_Not_Modified_Exception();
			}
		}
		if ($cache->etag() && @$this->get_from_server('HTTP_IF_NONE_MATCH')) {
			if (strcasecmp($cache->etag(),$this->get_from_server('HTTP_IF_NONE_MATCH'))==0) {
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
		$format = $this->get_var("__yze_resp_format__");//api 指定的输出格式,如http://domain/action.json
		if($format){
			return $format;
		}elseif($this->is_mobile_client()){//客户端是移动设备
			return "mob";
		}
		return "tpl";//default
	}

	public function is_mobile_client(){
		return preg_match("/android|iphone|ipad/i", $_SERVER['HTTP_USER_AGENT']);
	}


	public static function build_query($data) {
		$ret = array();

		foreach ( (array) $data as $k => $v ) {
			$k = urlencode($k);
			if ( $v === NULL )
				continue;
			elseif ( $v === FALSE )
			$v = '0';

			if ( is_array($v) || is_object($v) )
				array_push($ret,YZE_Request::build_query($v));
			else
				array_push($ret, $k.'='.urlencode($v));
		}

		$sep = ini_get('arg_separator.output');

		return implode($sep, $ret);
	}

	
	/**
	 * 在当前的地址中增加一个参数并返回地址
	 *
	 * @param array $args
	 */
	public function add_args_into_current_uri(array $args)
	{
		$uri = YZE_Request::get_instance()->the_uri();
		$query_string = $this->add_args_to_query_string($args);
		return $uri."?".$query_string;
	}

	/**
	 * 在当前的查询字符串中增加参数, 并返回查询字符串
	 *
	 * @param array $args
	 */
	public function add_args_to_query_string(array $args)
	{
		$gets = $this->get_datas();
		foreach ($args as $name => $value) {
			$gets[$name] = $value;
		}
		return self::build_query($gets);
	}

	public static function format_gmdate($date_str){
		return gmdate('D, d M Y H:i:s',strtotime($date_str))." GMT";
	}

	private function set_method($method)
	{
		return $this->method = $method;
	}
	private function set_vars($vars)
	{
		return $this->vars = $vars;
	}
	
	private function need_auth($req_method){
		
		$need_auth_methods = $this->get_auth_methods($this->controller_name(), "need");
		$no_auth_methods = $this->get_auth_methods($this->controller_name(), "noneed");

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
	 * 根据路由配置解析当前url，如果路由中没有配置，则根据默认的地址格式解析：/module/controller/vars.format
	 *
	 * @param unknown_type $routers
	 * @param unknown_type $uri
	 *
	 * @return Array('controller_name'=>, 'module'=>, 'args'=>)
	 */
	public static function parse_url($routers, $uri)
	{
		$_ = array();
		foreach ($routers as $module=>$router_info) {
			foreach ($router_info as $router => $acontroller) {
				$_['controller_name'] = strtolower($acontroller['controller']);
				$_['module'] = $module;
				if (preg_match("#^/{$router}\.(?P<__yze_resp_format__>[^/]+)$#i", $uri, $matches) ||
				preg_match("#^/{$router}/?$#i", $uri, $matches)) {
					$config_args = $matches;
					foreach ((array)$acontroller['args'] as $name => $value){
						$config_args[$name] = $value;
					}
					$_['args'] 	= @$config_args;
						
					return $_;
				}
			}
		}

		//默认按照 /module/controller/var/ 解析
		$uri_split 				= explode("/", trim($uri, "/"));

		//把controller-name 转换成controller_name
		if(@$uri_split[1]){
			$path = self::the_val(str_replace("-", "_", $uri_split[1]), "index");
			$_['controller_name']	= pathinfo($path, PATHINFO_FILENAME );
			$_['module'] 			= strtolower($uri_split[0]);
		}else{
			$_['module'] 			= pathinfo($uri_split[0], PATHINFO_FILENAME );
			$_['controller_name']	= "index";
		}

		if (preg_match("#\.(?P<__yze_resp_format__>[^/]+)$#i", $uri, $matches)) {
			$_['args'] 	= $matches;
		}
		return $_;
	}



	public function dispatch()
	{
		$controller = $this->controller;
		//如果控制器配置了缓存，则判断是否有缓存，有则直接输出缓存
		if(($cache_html = $controller->has_response_cache())){
			return new YZE_Notpl_View($cache_html, $controller);
		}else{
			$method = "do_".$this->the_method();
			return $controller->$method();
		}
	}
	
	
	private function set_controller_name($controller){
		$this->controller_class = self::format_class_name($controller, "Controller");
		$this->controller_name = $controller;
		if(class_exists($this->controller_class)){
			$this->controller = new $this->controller_class;
			return $this;
		}
		
		$class = "\\app\\".$this->module()."\\".$this->controller_class;

		if(class_exists($class)){
			$this->controller = new $class;
		}

		return $this;
	}
	/**
	 * 控制器名字,如\app\module_name\controller_name
	 *
	 * @return string
	 */
	public function controller_name($is_sort = false){
		if(!$this->module()) return "";
		return $is_sort ? $this->controller_name  : "\\app\\".$this->module()."\\".$this->controller_name;
	}
	
	/**
	 * 控制器类名,如\app\module_name\controller_name
	 * @return string
	 */
	public function controller_class($is_sort = false)
	{
		if(!$this->module()) return "";
		return $is_sort ? $this->controller_class : "\\app\\".$this->module()."\\".$this->controller_class;
	}
	/**
	 * 控制器对象
	 *
	 * @author leeboo
	 *
	 * @return YZE_Resource_Controller
	 */
	public function controller()
	{
		return $this->controller;
	}
	
	public function set_module($module)
	{
		$this->module = $module;
		$this->module_class = YZE_Object::format_class_name($module, "Module");
	
		if(class_exists($this->module_class)){
			$this->module_obj = new $this->module_class;
			return $this;
		}
		
		$class = "\\app\\".$module."\\".$this->module_class;
		
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
		$info = \yangzie\YZE_Object::loaded_module($this->module());
		if($info['is_phar']){
			return "phar://".YZE_APP_PATH."modules/".$this->module().".phar/views";
		}else{
			return YZE_APP_PATH."modules/".$this->module()."/views";
		}
	}
}
?>