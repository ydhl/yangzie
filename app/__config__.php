<?php
namespace  app;

use yangzie\YZE_FatalException;
use function yangzie\yze_js_bundle;

/**
 * 指定上传目录
 */
define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html".DS."upload".DS);
/**
 * MYSQL数据库用户名
 */
define("YZE_MYSQL_USER",  "root");
/**
 * MYSQL数据库主地址
 */
define("YZE_MYSQL_HOST_M",  "127.0.0.1");
/**
 * MYSQL数据库从地址，没有则不填写
 */
define("YZE_MYSQL_HOST_S",  "");
/**
 * MYSQL数据库名
 */
define("YZE_MYSQL_DB",  "");
/**
 * MYSQL端口
 */
define("YZE_MYSQL_PORT",  "3306");
/**
 * MYSQL密码
 */
define("YZE_MYSQL_PASS",  "");
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
define("APPLICATION_NAME", "Yangzie");
/**
 * 是否是session less应用，session less将不开启session功能
 */
define("SESSIONLESS", true);


/**
 * app模块配置
 *
 * @author leeboo
 *
 */
class App_Module extends \yangzie\YZE_Base_Module{

	//数据库配置
	public $db_user = YZE_MYSQL_USER;
	public $db_host= YZE_MYSQL_HOST_M;
	public $db_name= YZE_MYSQL_DB;
	public $db_port = YZE_MYSQL_PORT;
	public $db_psw= YZE_MYSQL_PASS;
	public $db_charset= 'UTF8';

	/**
	 * App 访问时做一些检查，比如php的版本
	 * @return bool|void
	 * @throws YZE_FatalException
	 */
	public function check(){
//		if( version_compare(PHP_VERSION,'5.3.0','lt')){
//			throw new YZE_FatalException("要求5.3及以上PHP版本");
//		}
	}

	protected function _config()
	{
		//动态返回配置
		return array();
	}

	/**
	 * 应用启动时需要加载的文件
	 */
	public function module_include_files() {
        $files = array(
           "app/vendor/pomo/translation_entry.class.php",
           "app/vendor/pomo/pomo_stringreader.class.php",
           "app/vendor/pomo/pomo_cachedfilereader.class.php",
           "app/vendor/pomo/pomo_cachedIntfilereader.class.php",
           "app/vendor/pomo/translations.class.php",
           "app/vendor/pomo/gettext_translations.class.php",
           "app/vendor/pomo/mo.class.php",
		   "vendor/autoload.php",
		);

        return $files;
	}

	/**
	 * js资源分组，在加载时方便直接通过分组名加载; 这里是静态指定，如果模块中需要动态指定，可通过Request->addJSBundle制定
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_js_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function js_bundle($bundle){
		$config = array (
				"yangzie" => array (
				)
		);
		return $config[$bundle];
	}
	/**
	 * css资源分组，在加载时方便直接通过分组名加载; 这里是静态指定，如果模块中需要动态指定，可通过Request->addCSSBundle制定
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * 在layouts中通过接口yze_css_bundle("yangzie,foo,bar")一次打包加载这里指定的资源
	 * @return array(资源路径1，资源路径2)
	 */
	public function css_bundle($bundle){
		$config = array (
		);
		return $config[$bundle];
	}
}
?>
