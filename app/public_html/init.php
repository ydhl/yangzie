<?php
namespace  app;

use yangzie\YZE_Exception_Controller;
use yangzie\YZE_RuntimeException;

/**
 * 1.定义系统目录常量
 * 2.设置文件包含查找路径
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     http://yangzie.yidianhulian.com
 *
 */

define("YANGZIE", "../../yangzie");
define("DS", DIRECTORY_SEPARATOR);
define("PS", PATH_SEPARATOR);

define("YZE_INSTALL_PATH", dirname(dirname(dirname(__FILE__))).DS);//安装的目录路径
define("YZE_APP_PATH", YZE_INSTALL_PATH."app".DS);//应用代码路径
define("YZE_PUBLIC_HTML", YZE_INSTALL_PATH."app".DS."public_html".DS);//应用代码路径
define("YZE_APP_CACHES_PATH", YZE_INSTALL_PATH."app".DS."public_html".DS."caches".DS);//缓存存放路径

define("YZE_APP_INC",           YZE_APP_PATH);//应用代码目录名称
define("YZE_APP_MODULES_INC",   YZE_APP_PATH."modules/");//应用代码目录名称
define("YZE_APP_VENDOR",        YZE_APP_PATH."vendor/");
define("YZE_APP_LAYOUTS_INC",   YZE_APP_PATH."vendor/layouts/");
define("YZE_APP_VIEWS_INC",     YZE_APP_PATH."vendor/views/");


ini_set('include_path', get_include_path().PS.dirname(dirname(dirname(__FILE__))));
require_once YANGZIE.'/init.php';

spl_autoload_register("\yangzie\yze_autoload");


try{
	/**
	 * 加载应用：
	 * 1. 加载应用配置文件app/__config__.php，根据其中的配置进行系统初始化，比如数据库配置
	 * 2. 加载应用中所有的模块配置文件，__module__.php，根据其中的配置加载模块的包含路径，自动包含的文件，url映射等等
	 */
	\yangzie\yze_load_app();

	if(!SESSIONLESS) \session_start();

	//加载l10n本地语言翻译处理，根据用户的请求中的指示，决定合适的显示语言
	\yangzie\load_default_textdomain();
}catch (\Exception $notCatch){
	$controller = new YZE_Exception_Controller();
	$controller->do_exception(new YZE_RuntimeException($notCatch->getMessage()))->output();
    die();
}
?>
