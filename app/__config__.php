<?php
namespace  app;


if (defined("YZE_DEVELOP_MODE") && YZE_DEVELOP_MODE){
	define("YZE_UPLOAD_PATH", YZE_APP_PATH. "public_html/upload/");//end by /
	define("SAE_MYSQL_USER",  "root");
	define("SAE_MYSQL_HOST_M",  "localhost");
	define("SAE_MYSQL_DB",  "");
	define("SAE_MYSQL_PORT",  "3306");
	define("SAE_MYSQL_PASS",  "ydhl");
	define("SITE_URI", "http://ydweixin.local.com/");
	define("UPLOAD_SITE_URI", "http://ydweixin.local.com/");
}else{
	define("YZE_UPLOAD_PATH",  "saestor://upload/");//end by /
	define("SITE_URI", "http://ydweixin.sinaapp.com/");
	define("UPLOAD_SITE_URI", "http://ydweixin.local.com/");
}


define("APLICATION_NAME", "易点服务助手");


class App_Module extends \yangzie\YZE_Base_Module{

	//数据库配置
	public $db_user = SAE_MYSQL_USER;
	public $db_host= SAE_MYSQL_HOST_M;
	public $db_name= SAE_MYSQL_DB;
	public $db_port = SAE_MYSQL_PORT;
	public $db_psw= SAE_MYSQL_PASS;
	public $db_charset= 'UTF8';
	public $include_files = array(
			"app/vendor/pomo/translation_entry.class.php",
			"app/vendor/pomo/translations.class.php",
			"app/vendor/i18n.php",
			
			'app/vendor/uploader.class.php',
			'app/vendor/httpclient.php',
			'app/vendor/sae.php',
			'app/vendor/log4web.php',
	);
	
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