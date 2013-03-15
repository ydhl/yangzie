<?php
define("YZE_DEVELOP_MODE",  true);

class App_Module extends YZE_Base_Module{

	//数据库配置
	public $db_user = 'root';
	public $db_host= '127.0.0.1';
	public $db_name= '';
	public $db_psw= '';
	public $db_charset= 'UTF8';
	public $include_files = array(
			"app/vendor/pomo/translation_entry.class.php",
			"app/vendor/pomo/translations.class.php",
			"app/components/app_auth.class.php",
			"app/components/i18n.php"
	);

	public function check(){
		//return empty array() if everything is ok
		//return array  of  error message while has some error
		$error = array();
		if ( !is_writable(APP_CACHES_PATH)){
			$error[] = vsprintf(__("%s 目录不可写"), APP_CACHES_PATH);
		}
		return $error;
	}

	protected function _config()
	{
		//动态返回配置
		return array();
	}
}
?>