<?php


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
			"app/vendor/app_auth.class.php",
			"app/vendor/i18n.php"
	);

	public function check(){
		//do app check
	}

	protected function _config()
	{
		//动态返回配置
		return array();
	}
}
?>