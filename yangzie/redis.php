<?php
/**
 * redis 封装，对于redis的连接是静态属性
 * 
 * @author leeboo
 * @see https://github.com/phpredis/phpredis
 */
class YZE_Redis{
	protected static $redis;
	
	private static function init(){
		if(defined("YZE_CACHE_HOST_M") && YZE_CACHE_HOST_M && !static::$redis){
			static::$redis = new Redis();
			$redis->pconnect(YZE_CACHE_HOST_M, YZE_CACHE_PORT);
			if( ! $redis->auth(YZE_CACHE_PASS)){
				return;
			}
			$select = $redis->select(YZE_CACHE_NAME);
		}
	}
	
	public static function set($name, $value){
		static::init();
		static::$redis->set($name, $value);
	}
	
	public static function get($name){
		static::init();
		static::$redis->get($name);
	}
	
	public function __construct(){
		self::init();
	}
}