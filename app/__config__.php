<?php
class App_Module extends Base_Module{
	/**
	 * 定义应用的身份认证类名
	 * @var string
	 */
	public $auth_class = "App_Auth";
		
	//数据库配置
	public $db_user = 'root';
	public $db_host= '127.0.0.1';
	public $db_name= 'zhiqiang';
	public $db_psw= 'ydhl';
	public $db_charset= 'UTF8';

	public $default_theme = 'themes/default';
	public $theme_path = 'themes';#主题目录，保存主题用到的css,javascript,img
	

	#设置app目录中的自动包含路径
	public $include_path = array(
        "libs/l10n",
        "libs/l10n/pomo",
		"libs",
        "components",
    );



    protected function _config()
    {
    	return array(
            'upload_path' => dirname(__FILE__).DIRECTORY_SEPARATOR.'v'.DIRECTORY_SEPARATOR.'upload',#上传目录
            'include_files' => array(
                "l10n.php",
                'controllers/app_auth.class.php',
                'helper/link.php',
                'helper/form.php',
				'session_const.php',
            	'spyc.php'
            )
    	);
    }
}
?>