<?php

namespace yangzie;

/**
 * 会话上下文，处理请求过程中的数据传递及相关控制
 *
 * @liizii
 */
class YZE_Session_Context {
    private static $instance;
    private function __construct() {
    }
    
    /**
     *
     * @return YZE_Session_Context
     */
    public static function get_instance() {
        if (! isset ( self::$instance )) {
            $c = __CLASS__;
            self::$instance = new $c ();
        }
        return self::$instance;
    }
    
    /**
     * 在会话中保存数据，为了避免与一些系统使用会话冲突，key都hash过
     *
     * @param
     *            $key
     * @return unknown_type
     * @author liizii
     * @since 2009-12-10
     */
    public function get($key) {
        return @$_SESSION ['yze'] ['values'] [sha1 ( $key )];
    }
    public function set($key, $value) {
        $_SESSION ['yze'] ['values'] [sha1 ( $key )] = $value;
    }
    public function has($key) {
        return array_key_exists ( sha1 ( $key ), @$_SESSION ['yze'] ['values'] );
    }
    /**
     * 删除指定的key，如果不指定key则全部session都将被删除
     *
     * @author leeboo
     *        
     * @param string $key            
     *
     * @return
     *
     */
    public function destory($key = null) {
        if ($key) {
            unset ( $_SESSION ['yze'] ['values'] [sha1 ( $key )] );
        } else {
            unset ( $_SESSION ['yze'] ['values'] );
        }
    }
    
    /**
     * 临时保存post提交的数据
     *
     * @param string $uri            
     * @param $post filter_special_chars过滤后的数据            
     *
     * @return void
     * @author liizii
     * @since 2009-12-10
     */
    public function save_post_datas($uri, array $post) {
        $_SESSION ['yze'] ['post_cache'] [$uri] = $post;
        return $this;
    }
    /**
     * 取得缓存的post提交的数据
     *
     * @param $uri
     * 
     * @return array filter_special_chars过滤后的数据
     *        
     * @author liizii
     * @since 2009-12-10
     */
    public function get_post_datas($uri) {
        return @$_SESSION ['yze'] ['post_cache'] [$uri];
    }
    /**
     * 清空post提交的数据
     *
     * @param $uri
     * @return void
     * @author liizii
     * @since 2009-12-10
     */
    public function clear_post_datas($uri) {
        if(array_key_exists($uri, @$_SESSION ['yze'] ['post_cache']));
            unset($_SESSION ['yze'] ['post_cache'] [$uri]);
            
        return $this;
    }
    

	/**
	 * 保存controller的数据
	 * 
	 * @param $uri
	 * @param array $datas
	 */
	public function save_controller_datas($uri, array $datas){
		$_SESSION['yze']['controller_data'][$uri] = $datas;
	}

	/**
	 * 取得controller的数据
	 * 
	 * @param $uri
	 */
	public function get_controller_datas($uri){
		return @$_SESSION['yze']['controller_data'][$uri];
	}

	/**
	 * 清空controller上绑定的所有数据
	 * 
	 * @param $uri
	 */
	public function clear_controller_datas($uri){
		unset($_SESSION['yze']['controller_data'][$uri]);
	}
	public function set_request_token($uri, $token){
		$_SESSION['yze']["token"][$uri] = $token;
		return $this;
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
	
	public function copy($olduri, $newuri){
		foreach (array("token") as $key){
			$_SESSION['yze'][$key][$newuri] = @$_SESSION['yze'][$key][$olduri];
		}
	}
	
	public static function get_cached_post($name){
		$uri = YZE_Request::get_instance()->the_uri();
		$dates = YZE_Session_Context::get_instance()->get_post_datas($uri);
		return @$dates[$name];
	}

	public static function post_cache_has(){
		$uri = YZE_Request::get_instance()->the_uri();
		return YZE_Session_Context::get_instance()->get_post_datas($uri);
	}
	
}

?>