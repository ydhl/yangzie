<?php
class YZE_Object{
	//subclass impl this.
	public function output(){

	}

	/**
	 * 判断两个字符串或者两个数值间是否相等
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @param bool case insensitive $ci
	 * @return boolean
	 */
	public static function is_equal($a,$b,$ci=true){
		return $ci ? strcasecmp($a,$b)==0 : strcmp($a,$b)==0;
	}

	/**
	 * 取得一个变量的值，该方法主要是增加了默认值处理，如果变量为假值，返回默认值
	 *
	 */
	public static function the_val($val,$default){
		return $val ? $val : $default;
	}

	public static function date_is_not_empty($date_string)
	{
		return $date_string!="0000-00-00 00:00:00";
	}
	
	/**
	 * 包含文件，所包含的文件没有任何输出
	 * 
	 * @author leeboo
	 * 
	 * @param unknown_type $file
	 * 
	 * @return
	 */
	public static function silent_include_file($file){
		ob_start();
		include_once $file;
		$err = ob_get_clean();
		if($err){
			YZE_Object::log($err, "include file error");
		}
	}
	
	public static function log($msg,$tag=null){
		$messages = "[".($tag ? $tag : "yangzie")." ".date('y-m-d H:i:s')."] {$msg}\r\n";
		$dir = APP_PATH."/logs/log-".date("Y-m-d");

		$log = @fopen($dir,"a+");
		if(empty($log)){
			return false;
		}
		@fwrite($log,$messages);
		return true;
	}

	public static function call_object_method(&$object,$method,$args){
		$class = new ReflectionClass(get_class($object));
		if(!$class->hasMethod($method)){
			throw new Exception(__(sprintf("“%s”实体的方法“%s”不存在",get_class($object),$method)));
		}
		$method = $class->getMethod($method);
		return $method->invoke($object,$value);
	}
	/**
	 *
	 * aa_bb_cc格式化成Aa_Bb_Cc_suffix
	 * @param unknown_type $class_name
	 * @param unknown_type $suffix
	 */
	public static function format_class_name($class_name,$suffix){
		foreach(explode("_", trim($class_name)) as $word){
			$class[] = ucfirst(strtolower($word));
		}
		return join("_", $class).($suffix ? "_{$suffix}" : "");
	}


	/**
	 * 过滤掉html符号
	 *
	 * @param array $array
	 * @param $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV, INPUT_SESSION, or INPUT_REQUEST.
	 * @return unknown
	 */
	public static function filter_special_chars(array $array,$type){
		$definition = array();
		foreach($array as $name=>$value){
			$definition[$name]['filter'] 	= FILTER_CALLBACK;
			$definition[$name]['options']	= 'htmlspecialchars';
		}
		return filter_input_array($type, $definition);
	}

	public static function filter_vars(array $array){
		$definition = array();
		foreach($array as $name=>$value){
			$definition[$name]['filter'] 	= FILTER_CALLBACK;
			$definition[$name]['options']	= 'htmlspecialchars';
		}
		return filter_var_array($array, $definition);
	}
	public static function filter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'htmlspecialchars'));
	}
	public static function defilter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'htmlspecialchars_decode'));
	}
	public static function unescape($str,$charcode="UTF-8"){
		$text = preg_replace_callback("/[%|\\\\]u(?P<c>[0-9A-Za-z]{4})/",function($matches){
			$c = '';
			$val = intval($matches['c'],16);
			if($val < 0x7F){        // 0000-007F
				$c .= chr($val);
			}elseif($val < 0x800) { // 0080-0800
				$c .= chr(0xC0 | ($val / 64));
				$c .= chr(0x80 | ($val % 64));
			}else{                // 0800-FFFF
				$c .= chr(0xE0 | (($val / 64) / 64));
				$c .= chr(0x80 | (($val / 64) % 64));
				$c .= chr(0x80 | ($val % 64));
			}
			return $c;
		},$str);
		return mb_convert_encoding($text, $charcode, 'utf-8');
	}
	
	protected function get_set_property()
	{
		$property = func_get_arg(0);
		$args = func_get_arg(1);
		$ref_property = new ReflectionProperty(get_class($this), $property);
		$ref_property->setAccessible(true);
		if ($args) {
			$ref_property->setValue($this, $args[0]);
			return $this;
		}
		return $ref_property->getValue($this);
	}
}
?>