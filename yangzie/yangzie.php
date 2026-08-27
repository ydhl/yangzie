<?php
namespace yangzie;

/**
 * YZE_Object 框架最基础的类，提供框架级静态工具方法
 *
 * 主要包括：
 * - 已加载模块信息的注册与查询（set_loaded_modules / loaded_module）
 * - 变量的默认值处理（the_val）
 * - 命名格式转换（format_class_name）
 * - 输入数据的 HTML 转义与反转义（filter_* / defilter_var）
 *
 * @author liizii
 * @package yangzie
 */
class YZE_Object{
	/**
	 * 框架版本号
	 *
	 * @var string
	 */
	const VERSION = '3.1.0';

	/**
	 * 已加载模块的注册表，key 为小写模块名，value 为模块配置信息
	 *
	 * @var array
	 */
	private static $loaded_modules = array();

	/**
	 * 注册（记录）一个已加载的模块信息
	 *
	 * @param string $module_name  模块名
	 * @param mixed  $module_info  模块的配置信息（通常是模块配置数组）
	 * @return void
	 */
	public static function set_loaded_modules($module_name, $module_info){
		self::$loaded_modules[strtolower($module_name)] = $module_info;
	}

	/**
	 * 根据模块名获取已加载的模块信息，未加载时返回 null
	 *
	 * @param string $module_name 模块名
	 * @return mixed 模块配置信息，模块未加载时返回 null
	 */
	public static function loaded_module($module_name){
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		return self::$loaded_modules[strtolower($module_name)] ?? null;
	}

	/**
	 * 输出方法，子类可重写用于定义自身输出行为
	 *
	 * @return void
	 */
	public function output(){

	}



	/**
	 * 取得一个变量的值，该方法主要是增加了默认值处理，如果变量为假值，返回默认值
	 *
	 * @param mixed $val     需要判断的变量
	 * @param mixed $default 变量为假值时返回的默认值
	 * @return mixed 变量为真时返回 $val，否则返回 $default
	 */
	public static function the_val($val,$default){
		return $val ?: $default;
	}

	/**
	 * 将 aa_bb_cc 格式的字符串格式化成 Aa_Bb_Cc_suffix 格式
	 *
	 * 例如：format_class_name("user_profile", "Model") 返回 "User_Profile_Model"
	 *
	 * @param string $class_name 原始类名（下划线分隔）
	 * @param string $suffix     追加到末尾的后缀，可为空字符串
	 * @return string 格式化后的类名
	 */
	public static function format_class_name($class_name,$suffix){
		foreach(explode("_", trim($class_name)) as $word){
			$class[] = ucfirst(strtolower($word));
		}
		return join("_", $class).($suffix ? "_{$suffix}" : "");
	}


	/**
	 * 转义输入变量中的 html 符号
	 *
	 * 按 $array 中指定的变量名列表，从 $type 指定的输入来源读取并转义
	 *
	 * @param array $array 需要转义的变量名列表
	 * @param int   $type  输入来源常量：INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV, INPUT_SESSION 或 INPUT_REQUEST
	 * @return mixed 转义后的值数组；读取失败时为 false，无法转义的变量为 null
	 */
	public static function filter_special_chars($array=array(),$type=INPUT_GET){
		$definition = array();
		foreach($array as $name=>$value){
			$definition[$name]['filter'] 	= FILTER_CALLBACK;
			$definition[$name]['options']	= 'htmlspecialchars';
		}
		return filter_input_array($type, $definition);
	}

	/**
	 * 转义 $array 数组中的 html 符号
	 *
	 * @param array $array 需要转义的键值对数组
	 * @return array|false|null 转义后的数组；失败时返回 false，无法转义的键为 null
	 */
	public static function filter_vars(array $array){
		$definition = array();
		foreach($array as $name=>$value){
			$definition[$name]['filter'] 	= FILTER_CALLBACK;
			$definition[$name]['options']	= 'htmlentities';
		}
		return filter_var_array($array, $definition);
	}

	/**
	 * 转义单个数据中的 html 符号
	 *
	 * @param mixed $var 需要转义的数据
	 * @return mixed 转义后的数据；转义失败时返回原数据
	 */
	public static function filter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'htmlentities')) ?: $var;
	}

	/**
	 * 解码单个数据中的 html 符号（filter_var 的逆操作）
	 *
	 * @param mixed $var 需要反转义的数据
	 * @return mixed 反转义后的数据；解码失败时返回原数据
	 */
	public static function defilter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'html_entity_decode')) ?: $var;
	}
}

?>
