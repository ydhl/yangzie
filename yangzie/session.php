<?php
namespace yangzie;
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
	 * @param string $controller_name
	 * @param $post filter_special_chars过滤后的数据
	 * 
	 * @return void
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function save_post_datas($controller_name, array $post){
		$_SESSION['yze']['post_cache'][$controller_name] = $post;
	}
	/**
	 * 取得缓存的post提交的数据
	 *
	 * @param  $controller_name
	 * @return array filter_special_chars过滤后的数据
	 * 
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function get_post_datas($controller_name){
		return @$_SESSION['yze']['post_cache'][$controller_name];
	}
	/**
	 * 清空post提交的数据
	 *
	 * @param $controller_name
	 * @return void
	 * @author liizii
	 * @since 2009-12-10
	 */
	public function clear_post_datas($controller_name){
		if (@$_SESSION['yze']['post_cache'][$controller_name])
			unset($_SESSION['yze']['post_cache'][$controller_name]);
	}
	
	/**
	 * 保存控制器处理过程中的异常
	 * 
	 * @param $controller_name
	 * @param Exception $exception
	 */
	public function save_controller_exception($controller_name, $exception){
		$_SESSION['yze']['exception'][$controller_name] = $exception;
		return $this;
	}
	
	/**
	 * 取得控制器的异常
	 *
	 * @param $controller_name
	 * @return Exception
	 */
	public function get_controller_exception($controller_name){
		return @$_SESSION['yze']['exception'][$controller_name];
	}
	public function clear_controller_exception($controller_name){
		if (@$_SESSION['yze']['exception'][$controller_name]) 
			unset($_SESSION['yze']['exception'][$controller_name]);
	}
	
	/**
	 * 保存控制器处理过程中的验证错误
	 *
	 * @param YZE_Resource_Controller $controller
	 * @param Exception $exception
	 */
	public function save_controller_validates($controller_name, $datas){
		$_SESSION['yze']['validates'][$controller_name] = $datas;
		return $this;
	}
	
	/**
	 * 取得控制器的验证错误
	 *
	 * @param $controller_name
	 * @return Exception
	 */
	public function get_controller_validates($controller_name){
		return @$_SESSION['yze']['validates'][$controller_name];
	}
	public function clear_controller_validates($controller_name){
		if (@$_SESSION['yze']['validates'][$controller_name])
			unset($_SESSION['yze']['validates'][$controller_name]);
	}


	/**
	 * 保存controller的数据
	 * 
	 * @param $controller_name
	 * @param array $datas
	 */
	public function save_controller_datas($controller_name, array $datas){
		$_SESSION['yze']['controller_data'][$controller_name] = $datas;
	}

	/**
	 * 取得controller的数据
	 * 
	 * @param $controller_name
	 */
	public function get_controller_datas($controller_name){
		return @$_SESSION['yze']['controller_data'][$controller_name];
	}

	/**
	 * 清空controller上绑定的所有数据
	 * 
	 * @param $controller_name
	 */
	public function clear_controller_datas($controller_name){
		unset($_SESSION['yze']['controller_data'][$controller_name]);
	}
	public function set_request_token($controller_name, $token){
		$_SESSION['yze']["token"][$controller_name] = $token;
	}
	public function get_request_token($controller_name){
		return @$_SESSION['yze']["token"][$controller_name];
	}
	public function clear_request_token(){
		unset($_SESSION['yze']["token"]);
	}
	public function clear_request_token_ext($controller_name){
		unset($_SESSION['yze']["token"][$controller_name]);
	}
	
	public function copy($src_controller_name, $dest_controller_name){
		foreach (array("token") as $key){
			$_SESSION['yze'][$key][$dest_controller_name] = @$_SESSION['yze'][$key][$src_controller_name];
		}
	}
	
	public static function get_cached_post($name,$controller_name=null)
	{
		if (empty($controller_name)) {
			$controller_name = get_class(YZE_Request::get_instance()->controller());
		}
		$dates = YZE_Session_Context::get_instance()->get_post_datas($controller_name);
		return @$dates[$name];
	}

	public static function post_cache_has($name, $controller_name=null)
	{
		if (empty($controller_name)) {
			$controller_name = get_class(YZE_Request::get_instance()->controller());
		}
		return YZE_Session_Context::get_instance()->get_cached_post($name);
	}

	public static function post_cache_has_ext($controller_name=null)
	{
		if (empty($controller_name)) {
			$controller_name = get_class(YZE_Request::get_instance()->controller());
		}
		return YZE_Session_Context::get_instance()->get_post_datas($controller_name);
	}
	
}

?>