<?php
namespace yangzie;

use app\vendor\pomo\MO;
use app\vendor\pomo\Translations;

/**
 * 国际化（i18n）管理类
 *
 * 负责加载和缓存各 domain 的翻译（MO 格式翻译文件），
 * 并提供 translate()/__()/_e() 等全局函数用于翻译文本。
 *
 * @package yangzie
 */
class YZE_I18N extends YZE_Object{
	/**
	 * 已加载的翻译对象集合，key 为 domain 名
	 *
	 * @var array
	 */
	private $i18n = [];

	/**
	 * 单例实例
	 *
	 * @var YZE_I18N|null
	 */
	private static $me;

	/**
	 * 私有构造函数，禁止外部直接实例化，请使用 get_instance() 获取单例
	 */
	private function __construct() {
	}

	/**
	 * 获取 i18n 管理单例
	 *
	 * @return YZE_I18N 国际化管理实例
	 */
	public static function get_instance() {
		if(!isset(self::$me)){
			$c = __CLASS__;
			self::$me = new $c;
		}
		return self::$me;
	}

	/**
	 * 清空所有已加载的翻译
	 *
	 * @return void
	 */
	public function clear(){
		$this->i18n = [];
	}

	/**
	 * 获取所有已加载的翻译对象集合
	 *
	 * @return array 翻译对象集合，key 为 domain 名，value 为 Translations 对象
	 */
	public function getLoadedI18N(){
		return $this->i18n;
	}

	/**
	 * 设置指定 domain 的翻译对象
	 *
	 * @param string $domain 翻译 domain 名
	 * @param Translations $mo 翻译对象（引用保存）
	 * @return void
	 */
	public function setLoadedI18N($domain, &$mo){
		$this->i18n[$domain] = &$mo;
	}
}

/**
 * 翻译指定的文本
 *
 * @param string $text   需要翻译的文本
 * @param string $domain 翻译 domain 名，默认 "default"
 * @return string 翻译后的文本，未找到翻译时返回原文本
 */
function translate( $text, $domain = 'default' ) {
	if(!class_exists("app\\vendor\\pomo\\Translations"))return $text;

	$l10n = YZE_I18N::get_instance()->getLoadedI18N();
	$empty = new Translations();
	if ( isset($l10n[$domain]) )
		$translations = $l10n[$domain];
	else
		$translations = $empty;
	return $translations->translate($text);
}

/**
 * 翻译文本（translate 的别名，短名形式）
 *
 * @param string $text   需要翻译的文本
 * @param string $domain 翻译 domain 名，默认 "default"
 * @return string 翻译后的文本
 */
function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

/**
 * 翻译并输出文本
 *
 * @param string $text   需要翻译的文本
 * @param string $domain 翻译 domain 名，默认 "default"
 * @return void
 */
function _e( $text, $domain = 'default' ) {
	echo translate( $text, $domain );
}

/**
 * 从 MO 文件中加载指定 domain 的翻译
 *
 * @param string $domain 翻译 domain 名
 * @param string $mofile MO 翻译文件路径
 * @return bool 加载成功返回 true，文件不可读或解析失败返回 false
 */
function load_textdomain($domain, $mofile) {
	if ( !is_readable( $mofile ) ) return false;
	$mo = new MO();
	if ( !$mo->import_from_file( $mofile ) ) return false;
	YZE_I18N::get_instance()->setLoadedI18N($domain, $mo);
	return true;
}

/**
 * 获取当前语言（locale），如 zh-cn、en
 *
 * 优先通过 YZE_HOOK_GET_LOCALE hook 获取，未设置时取请求的 Accept-Language
 *
 * @return string 语言标识
 */
function get_accept_language() {
	$locale = YZE_Hook::do_hook(YZE_HOOK_GET_LOCALE);
	return $locale ?: YZE_Request::get_instance()->get_Accept_Language();
}

/**
 * 加载默认 domain 的翻译文件（i18n/{locale}.mo）
 *
 * @return bool 加载成功返回 true
 */
function load_default_textdomain() {
	$locale = get_accept_language();
	$mofile =  YZE_INSTALL_PATH."i18n/$locale.mo";
	return load_textdomain('default', $mofile);
}
?>
