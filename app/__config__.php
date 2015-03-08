<?php
namespace  app;

define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html/upload/");//end by /
define("YZE_MYSQL_USER",  "root");
define("YZE_MYSQL_HOST_M",  "127.0.0.1");
define("YZE_MYSQL_DB",  "ydoa");
define("YZE_MYSQL_PORT",  "3306");
define("YZE_MYSQL_PASS",  "ydhl");
define("SITE_URI", "http://ydweixin.local.com/");
define("UPLOAD_SITE_URI", "http://ydweixin.local.com/");

ini_set('error_reporting', E_ALL);//错误级别
date_default_timezone_set('Asia/Chongqing');//时区
define("APLICATION_NAME", "Yangzie");//应用名称

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
	 * 应用启动时需要加载的文件
	 * 
	 * @var unknown
	 */
	public $include_files = array(
			"app/vendor/pomo/translation_entry.class.php",
			"app/vendor/pomo/translations.class.php",
	);
	
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
}
?>