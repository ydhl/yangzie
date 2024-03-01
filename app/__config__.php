<?php
namespace  app;

use yangzie\YZE_Exception;
use function yangzie\yze_js_bundle;

/**
 * 指定上传目录
 */
define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html".DS."upload".DS);
/**
 * MYSQL加解密的秘钥
 */
define("YZE_DB_CRYPT_KEY",  "");
/**
 * 网站地址
 */
define("SITE_URI", "http://YOUR-DOMAIN/");
/**
 * 上传内容的访问地址，如果有cdn，填写cdn地址
 */
define("UPLOAD_SITE_URI", "http://YOR-DOMIAN/upload/");

/**
 * 开发环境true还是生产环境（false）
 */
define("YZE_DEVELOP_MODE",  true );
/**
 * 错误报告级别
 */
ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_DEPRECATED);
/**
 * 时区
 */
date_default_timezone_set('Asia/Chongqing');
/**
 * 应用名
 */
define("YZE_APP_NAME", "Yangzie");
/**
 * 是否是session less应用，session less将不开启session功能
 */
define("SESSIONLESS", false);


/**
 * 返回应用级的配置
 * @author leeboo
 *
 */
class App_Module extends \yangzie\YZE_Base_Module{

	/**
	 * App 访问时做一些检查，比如php的版本；如果有不满足的条件则抛出异常
	 * @return void
	 * @throws YZE_FatalException
	 */
	public function check(){
		if( version_compare(PHP_VERSION,'7.0.0','lt')){
			throw new YZE_FatalException("要求7.0及以上8.0以下PHP版本");
		}
		if( version_compare(PHP_VERSION,'8.0.0','>=')){
			throw new YZE_FatalException("要求7.0及以上8.0以下PHP版本");
		}
	}

	/**
	 * 动态返回配置
	 * @return array
	 */
	protected function config(): array{
		return [];
	}

	/**
	 * 应用启动时需要加载的文件，如果指定目录，则自动包含里面的所有文件,
	 * 但要注意是按文件名排序顺序包含的，如果被包含的文件之间有依赖关系，这会导致代码错误，这种情况请手动添加包含的文件
	 */
	public function module_include_files() {
        return [
			"app/vendor/pomo/translation_entry.class.php",
			"app/vendor/pomo/pomo_stringreader.class.php",
			"app/vendor/pomo/pomo_cachedfilereader.class.php",
			"app/vendor/pomo/pomo_cachedIntfilereader.class.php",
			"app/vendor/pomo/translations.class.php",
			"app/vendor/pomo/gettext_translations.class.php",
			"app/vendor/pomo/mo.class.php",
			"vendor/autoload.php",
		];
	}

	/**
	 * js资源分组及其包含的文件，在加载时方便直接通过分组名加载;
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_js_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function js_bundle($bundle){
		$config = [
//			"foo" => ['/js/foo.js']
		];
		return $config[$bundle];
	}
	/**
	 * css资源分组及其包含的文件，在加载时方便直接通过分组名加载;
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_css_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function css_bundle($bundle){
		$config = [
//			"foo" => ['/css/foo.css']
		];
		return $config[$bundle];
	}
}
?>
