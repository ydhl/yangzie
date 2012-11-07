<?php
#http协议的处理
class Http extends YangzieObject{
	
	public static function build_query($data) {
		$ret = array();
	
		foreach ( (array) $data as $k => $v ) {
			$k = urlencode($k);
			if ( $v === NULL )
				continue;
			elseif ( $v === FALSE )
				$v = '0';
	
			if ( is_array($v) || is_object($v) )
				array_push($ret,Http::build_query($v));
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
		$uri = Request::get_instance()->the_uri();
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
	
	/**
	 * 把 /name-value/形式的uri构建成name=value的数组
	 * 
	 * @param string $uri
	 * @return array
	 */
	public static function parse_uri_to_args($uri){
		$uri_args = explode("/", trim($uri,"/"));
		foreach ($uri_args as $k => $v){
			if(strpos($v,'-')!==false){
				$args = explode('-',urldecode($v));
				$return[@$args[0]] = @$args[1];
			}else{
				$return[$k] = urldecode($v);
			}
		}
		return (array)$return;
	}
	
	public static function format_gmdate($date_str){
		return gmdate('D, d M Y H:i:s',strtotime($date_str))." GMT";
	}
}
?>