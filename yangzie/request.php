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
class YZE_Request extends YZE_Object
{
	private $method;
	private $vars;

	private $post = array();
	private $get = array();
	private $cookie = array();
	private $server = array();
	private $env = array();

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
		//预处理请求数据，把get，post，cookie等数据进行urldecode后编码
		
		$this->post 		= $_POST ? self::filter_special_chars($_POST, INPUT_POST) : array();
		$this->get 		= $_GET ? self::filter_special_chars($_GET, INPUT_GET) : array();
		$this->cookie 	= $_COOKIE ? self::filter_special_chars($_COOKIE, INPUT_COOKIE) : array();
		$this->env 		= $_ENV ? self::filter_special_chars($_ENV, INPUT_ENV) : array();
		$this->server 	= $_SERVER ? self::filter_special_chars($_SERVER, INPUT_SERVER) : array();

		$this->init_request();
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
	public function save_entity_to_cache(YZE_Model $entity)
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
	 * @return YZE_Request
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
		}catch (Exception $e){
		}
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
	 * 系统处理入口，该方法在入口文件index.php中调用，
	 * 该方法的作用是根据URI把资源的请求操作转发到正确的资源上，并把处理结果返回。
	 *
	 * @return YZE_IResponse
	 * @author liizii, <libol007@gmail.com>
	 *
	 */
	public function dispatch()
	{
		$dispatch = YZE_Dispatch::get_instance();
		return $dispatch->dispatch();
	}
	private function init_request()
	{
		$request_method = self::the_val($this->get_from_post("yze_method"), strtolower($_SERVER['REQUEST_METHOD']));
		$this->set_method($request_method);

		if  (!$this->is_get() && $this->the_post_data()) {
			YZE_Session::get_instance()->save_post_datas($this->the_uri(), $this->the_post_data());
		}

		$uri = $this->the_uri();

		$routers = YZE_Router::get_instance()->get_routers();
		
		$config_args 		= self::parse_url($routers, $uri);
		
		$this->set_vars(@(array)$config_args['args']);
		return $this;
	}

	/**
	 * 请求的方法：get,post,put,delete
	 */
	public function method()
	{
		return $this->method;
	}
	public function set_method($method)
	{
		return $this->method = $method;
	}
	public function set_vars($vars)
	{
		return $this->vars = $vars;
	}
	public function get_var($key, $default=null)
	{
		$vars = $this->vars;
		return array_key_exists($key, $vars) ? $vars[$key] : $default;
	}
	
	public function vars(){
		return $this->vars;
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
		$referer =  @$_SERVER['HTTP_REFERER'];
		if (!$just_path) {
			return $referer;
		}
		return parse_url($referer, PHP_URL_PATH);
	}

	public function auth()
	{
		$dispatch = YZE_Dispatch::get_instance();
		$req_method = $this->method();

		if($this->need_auth($req_method)) {//需要验证
			if ( !class_exists("App_Auth")) {
				throw new YZE_Not_Found_Class_Exception(
						vsprintf(__("未找到认证实现类App_Auth")));
			}

			$authobject = new App_Auth();

			//先验证是否登录
			$authobject->do_auth();

			//验证请求的方法是否有权限调用
			$acl = YZE_ACL::get_instance();
			$aco_name = "/".$dispatch->module()."/".$dispatch->get_controller()."/".$req_method;
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
		$dispatch = YZE_Dispatch::get_instance();
		$request_method = $this->method();
		YZE_Object::silent_include_file($dispatch->module().'/validates/'.$dispatch->controller()."_validate.class.php");
		$validate_cls = self::format_class_name($dispatch->controller(),"Validate");

		if(!class_exists("$validate_cls"))return;
		$validate = new $validate_cls();
		$validate_method = "init_{$request_method}_validates";
		$validate->$validate_method();
		return $validate->do_validate($request_method);
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
	 * 在当前的地址中增加一个参数并返回地址字符串
	 *
	 * @param array $args
	 */
	public static function add_args_into_current_uri(array $args)
	{
		$uri = YZE_Request::get_instance()->the_uri();
		$query_string = self::add_args_to_query_string($args);
		return $uri."?".$query_string;
	}
	
	/**
	 * 在当前的查询字符串中增加参数
	 *
	 * @param array $args
	 */
	public static function add_args_to_query_string(array $args)
	{
		$gets = $_GET;
		foreach ($args as $name => $value) {
			$gets[$name] = $value;
		}
		return self::build_query($gets);
	}
	
	public static function format_gmdate($date_str){
		return gmdate('D, d M Y H:i:s',strtotime($date_str))." GMT";
	}


	private function need_auth($req_method)
	{
		$dispatch = YZE_Dispatch::get_instance();

		$need_auth_methods = $this->get_auth_methods($dispatch->controller(), "need");
		$no_auth_methods = $this->get_auth_methods($dispatch->controller(), "noneed");

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
		$dispatch = YZE_Dispatch::get_instance();
		if($type=="need"){
			$auth_methods = @$dispatch->module_obj()->auths[$controller_name];
			if($auth_methods)return $auth_methods;

			$auth_methods = @$dispatch->module_obj()->auths["*"];
			if($auth_methods)return $auth_methods;
		}elseif($type=="noneed"){
			$auth_methods = @$dispatch->module_obj()->no_auths[$controller_name];
			if($auth_methods)return $auth_methods;

			$auth_methods = @$dispatch->module_obj()->no_auths["*"];
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
	public static function parse_url($routers, $uri)
	{
		$_ = array();
		foreach ($routers as $module=>$router_info) {
			foreach ($router_info as $router => $acontroller) {
				if (preg_match("#^/{$router}(\.(?P<__yze_resp_format__>[^/]+)$|/?$)#i", $uri, $matches)) {
					$_['controller_name'] = strtolower($acontroller['controller']);
					$config_args = $matches;
					foreach ((array)$acontroller['args'] as $name => $value){
						$config_args[$name] = $value;
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