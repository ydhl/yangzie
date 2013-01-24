<?php
define("DEVELOP_MODE",  true);

class App_Module extends Base_Module{

	//数据库配置
	public $db_user = 'root';
	public $db_host= '127.0.0.1';
	public $db_name= 'zhiqiang';
	public $db_psw= 'ydhl';
	public $db_charset= 'UTF8';
	public $include_files = array(
			"app/components/pomo/translation_entry.class.php",
			"app/components/pomo/translations.class.php",
			"app/components/app_auth.class.php",
			"app/components/i18n.php"
	);


    protected function _config()
    {
    	//动态返回配置
    	return array();
    }
}
?>