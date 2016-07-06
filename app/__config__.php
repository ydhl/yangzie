<?php
namespace  app;

define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html".DS."upload".DS);//end by /
define("YZE_MYSQL_USER",  "root");
define("YZE_MYSQL_HOST_M",  "127.0.0.1");
define("YZE_MYSQL_DB",  "ydoa");
define("YZE_MYSQL_PORT",  "3306");
define("YZE_MYSQL_PASS",  "ydhl");
define("SITE_URI", "http://yangzie.local.com/");//网站地址
define("UPLOAD_SITE_URI", "http://yangzie.local.com/upload/");//上传文件内容访问地址，比如cdn


define("YZE_DEVELOP_MODE",  true );
define('YZE_REWRITE_MODE', YZE_REWRITE_MODE_REWRITE);//开发时一但设置便别在修改
ini_set('error_reporting', E_ERROR);//错误级别
date_default_timezone_set('Asia/Chongqing');//时区
define("APPLICATION_NAME", "Yangzie");//应用名称


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
	
	//加载应用时需要检查什么
	public function check(){
		//return empty array() if everything is ok
		//return array  of  error message while has some error
		$error = array();
		return $error;
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
			"app/vendor/pomo/translations.class.php"
		);
        
        return $files;
	}
	
	/**
	 * js资源分组，在加载时方便直接通过分组名加载; 这里是静态指定，如果模块中需要动态指定，可通过Request->addJSBundle制定
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * @return array(资源路径1，资源路径2)
	 */
	public function js_bundle($bundle){
		$config = array (
				"bootstrap" => array (
						"/bootstrap3/js/bootstrap.min.js"
				),
				"jquery" => array (
						"/js/jquery-1.11.2.min.js" 
				),
				"pjax" => array (
						"/js/jquery.pjax.js" 
				),
				"yangzie" => array (
						"/js/json.js",
						"/js/yze_ajax_front_controller.js",
						"/js/outerHTML-2.1.0-min.js"
				) 
		);
		return $config[$bundle];
	}
	/**
	 * css资源分组，在加载时方便直接通过分组名加载; 这里是静态指定，如果模块中需要动态指定，可通过Request->addCSSBundle制定
	 * 资源路径以web 绝对路径/开始，/指的上public_html目录
	 * @return array(资源路径1，资源路径2)
	 */
	public function css_bundle($bundle){
		$config = array (
				"bootstrap" => array (
						"/css/bsfix.css",
						"/bootstrap3/css/bootstrap.min.css",
						"/bootstrap3/css/bootstrap-theme.min.css",
						
				)
		);
		return $config[$bundle];
	}
}
?>