<?php
/**
 * 该文件的职责：
 * 1.定义系统目录常量
 * 2.设置文件包含查找路径
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://www.yangzie.net
 *
 */

define("YANGZIE", "../../yangzie");
define("DS", DIRECTORY_SEPARATOR);
define("PS", PATH_SEPARATOR);

define("YZE_APP_INC", "../");//应用代码目录名称
define("YZE_APP_MODULES_INC", "../modules/");//应用代码目录名称
define("YZE_APP_LAYOUTS_INC", "../components/layouts/");
define("APP_VIEWS_INC", "../components/views/");


define("YZE_INSTALL_PATH", dirname(__FILE__)."/../../");//安装的目录路径
define("YZE_APP_PATH", YZE_INSTALL_PATH."app".DS);//应用代码路径
define("YZE_APP_CACHES_PATH", YZE_INSTALL_PATH."app".DS."public_html".DS."caches".DS);//应用代码路径

//path_info, rewrite, none
define('YZE_REWRITE_MODE_PATH_INFO', 'yze_rewrite_mode_path_info');
define('YZE_REWRITE_MODE_REWRITE', 'yze_rewrite_mode_rewrite');
define('YZE_REWRITE_MODE_NONE', 'yze_rewrite_mode_none');
define('YZE_REWRITE_MODE', YZE_REWRITE_MODE_NONE);
define("YZE_DEVELOP_MODE",  true);

ini_set('include_path', get_include_path().PS."../..");


ini_set('error_reporting', E_ALL);

date_default_timezone_set('Asia/Chongqing');
?>