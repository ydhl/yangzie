<?php
/**
 * 会话上下文，处理请求过程中的数据传递及相关控制
 * 
 * @liizii
 * 
 */
class YZE_Session_Context{
	private static $instance;
	private function __construct(){
	}
	
	/**
	 * @return YZE_Session_Context
	 */
	public static function get_instance(){
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	/**
	 * 在会话中保存数据，为了避免与一些系统使用会话冲突，key都hash过
	 *
	 * @param $key
	 * @return unknown_type
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function get($key){
		return @$_SESSION['yze']['values'][sha1($key)];
	}
	public function set($key,$value){
		$_SESSION['yze']['values'][sha1($key)] = $value;
	}
	public function has($key){
		return array_key_exists(sha1($key), @$_SESSION['yze']['values']);
	}
	/**
	 * 删除指定的key，如果不指定key则全部session都将被删除
	 *
	 * @author leeboo
	 *
	 * @param string $key
	 *
	 * @return
	 */
	public function destory($key=null){
		if($key){
			unset($_SESSION['yze']['values'][sha1($key)]);
		}else{
			unset($_SESSION['yze']['values']);
		}
	}
	
	/**
	 * 临时保存post提交的数据
	 *
	 * @param $uri 处理post的uri
	 * @param $post filter_special_chars过滤后的数据
	 * @return void
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function save_post_datas(/*string*/$uri,array $post){
		$_SESSION['yze']['post_cache'][sha1($uri)] = $post;
	}
	/**
	 * 取得缓存的post提交的数据
	 *
	 * @param  $uri 处理post的uri
	 * @return array filter_special_chars过滤后的数据
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function get_post_datas(/*string*/$uri){
		return @$_SESSION['yze']['post_cache'][sha1($uri)];
	}
	/**
	 * 清空post提交的数据
	 *
	 * @param $uri 处理post的uri
	 * @return void
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function clear_post_datas(/*string*/$uri){
		unset($_SESSION['yze']['post_cache'][sha1($uri)]);
	}
	
	/**
	 * 保存控制器处理过程中的异常
	 * 
	 * @param YZE_Resource_Controller $controller
	 * @param Exception $exception
	 */
	public function save_controller_exception(YZE_Resource_Controller $controller, $exception){
		$_SESSION['yze']['exception'][get_class($controller)] = $exception;
		return $this;
	}
	
	/**
	 * 取得控制器的异常
	 *
	 * @param YZE_Resource_Controller $controller
	 * @return Exception
	 */
	public function get_controller_exception(YZE_Resource_Controller $controller){
		return @$_SESSION['yze']['exception'][get_class($controller)];
	}
	public function clear_controller_exception(YZE_Resource_Controller $controller){
		$key = get_class($controller);
		if (@$_SESSION['yze']['exception'][$key]) 
			unset($_SESSION['yze']['exception'][$key]);
	}


	/**
	 * 保存controller的数据
	 * 
	 * @param YZE_Resource_Controller $controller
	 * @param array $datas
	 */
	public function save_controller_datas(YZE_Resource_Controller $controller, array $datas){
		$_SESSION['yze']['controller_data'][sha1(get_class($controller))] = $datas;
	}

	/**
	 * 取得controller的数据
	 * 
	 * @param YZE_Resource_Controller $controller
	 */
	public function get_controller_datas(YZE_Resource_Controller $controller){
		return @$_SESSION['yze']['controller_data'][sha1(get_class($controller))];
	}

	/**
	 * 清空controller上绑定的所有数据
	 * 
	 * @param YZE_Resource_Controller $controller
	 */
	public function clear_controller_datas(YZE_Resource_Controller $controller){
		unset($_SESSION['yze']['controller_data'][sha1(get_class($controller))]);
	}
	public function set_request_token($uri, $token){
		$_SESSION['yze']["token"][$uri] = $token;
	}
	public function get_request_token($uri){
		return @$_SESSION['yze']["token"][$uri];
	}
	public function clear_request_token(){
		unset($_SESSION['yze']["token"]);
	}
	public function clear_request_token_ext($uri){
		unset($_SESSION['yze']["token"][$uri]);
	}
	public static function get_cached_post($name,$uri=null)
	{
		if (empty($uri)) {
			$uri = YZE_Request::get_instance()->the_uri();
		}
		$dates = YZE_Session_Context::get_instance()->get_post_datas($uri);
		return @$dates[$name];
	}

	public static function post_cache_has($name,$uri=null)
	{
		if (empty($uri)) {
			$uri = YZE_Request::get_instance()->the_uri();
		}
		return array_key_exists($name,(array)@$_SESSION['yze']['post_cache'][sha1($uri)]);
	}

	public static function post_cache_has_ext($uri=null)
	{
		if (empty($uri)) {
			$uri = YZE_Request::get_instance()->the_uri();
		}
		return @$_SESSION['yze']['post_cache'][sha1($uri)];
	}
	
}

?>