<?php
namespace yangzie;

class YZE_Object{
	const VERSION = '4.0.0';
	private static $loaded_modules = array();
	/**
	 * 根目录 .env 文件内容的缓存，避免一个请求中多次读取文件
	 * @var array|null
	 */
	private static $env_config = null; // ai@2026-08-25 .env 解析结果的缓存


	public static function set_loaded_modules($module_name, $module_info){
		self::$loaded_modules[strtolower($module_name)] = $module_info;
	}

	public static function loaded_module($module_name){
		// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
		return self::$loaded_modules[strtolower($module_name)] ?? null;
	}

	public function output(){

	}



	/**
	 * 取得一个变量的值，该方法主要是增加了默认值处理，如果变量为假值，返回默认值
	 * @return mixed
	 */
	public static function the_val($val,$default){
		return $val ?: $default;
	}

	/**
	 *
	 * aa_bb_cc格式化成Aa_Bb_Cc_suffix
	 * @param string $class_name
	 * @param string $suffix
	 */
	public static function format_class_name($class_name,$suffix){
		foreach(explode("_", trim($class_name)) as $word){
			$class[] = ucfirst(strtolower($word));
		}
		return join("_", $class).($suffix ? "_{$suffix}" : "");
	}


	/**
	 * 转义html符号
	 *
	 * @param array $array
	 * @param $type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV, INPUT_SESSION, or INPUT_REQUEST.
	 * @return unknown
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
	 * 转义$array数组中的html符号
	 * @param array $array
	 * @return array|false|null
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
	 * 转义数据中的html符号
	 * @param $var
	 * @return mixed
	 */
	public static function filter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'htmlentities')) ?: $var;
	}

	/**
	 * 解码数据中的html符号
	 * @param $var
	 * @return mixed
	 */
	public static function defilter_var($var){
		return filter_var($var, FILTER_CALLBACK,array('options' => 'html_entity_decode')) ?: $var;
	}

	/**
	 * 获取配置值，优先从操作系统环境变量获取，其次从根目录 .env 文件获取，都没有则返回默认值
	 * @param string $name 配置名
	 * @param mixed $default 默认值
	 * @return mixed
	 */
	public function env($name, $default = null){
		// ai@2026-08-25 首次调用时读取根目录 .env 文件并缓存解析结果
		if (self::$env_config === null) {
			self::$env_config = array();
			$env_file = YZE_INSTALL_PATH . '.env';
			if (is_file($env_file)) {
				foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
					$line = trim($line);
					// ai@2026-08-25 跳过空行和 # 开头的注释行
					if ($line === '' || strpos($line, '#') === 0) {
						continue;
					}
					// ai@2026-08-25 支持 export 前缀
					if (strpos($line, 'export ') === 0) {
						$line = trim(substr($line, 7));
					}
					$pos = strpos($line, '=');
					if ($pos === false) {
						continue;
					}
					$key = trim(substr($line, 0, $pos));
					$value = trim(substr($line, $pos + 1));
					// ai@2026-08-25 去掉行内注释
					$comment_pos = strpos($value, ' #');
					if ($comment_pos !== false) {
						$value = trim(substr($value, 0, $comment_pos));
					}
					// ai@2026-08-25 去掉包裹值的单双引号
					if (strlen($value) >= 2
						&& (($value[0] === '"' && substr($value, -1) === '"') ||
							($value[0] === "'" && substr($value, -1) === "'"))) {
						$value = substr($value, 1, -1);
					}
					self::$env_config[$key] = $value;
				}
			}
		}
		// ai@2026-08-25 优先取操作系统环境变量
		$value = getenv($name);
		if ($value !== false) {
			return $value;
		}
		// ai@2026-08-25 其次取 .env 文件中的配置
		if (isset(self::$env_config[$name])) {
			return self::$env_config[$name];
		}
		return $default;
	}
}

?>
