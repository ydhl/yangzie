<?php
namespace  app;

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

define("YZE_APP_INC", YZE_APP_PATH);//应用代码目录名称
define("YZE_APP_MODULES_INC", YZE_APP_PATH."modules/");//应用代码目录名称
define("YZE_APP_LAYOUTS_INC", YZE_APP_PATH."vendor/layouts/");
define("YZE_APP_VIEWS_INC", YZE_APP_PATH."vendor/views/");

//path_info, rewrite, none
define('YZE_REWRITE_MODE_PATH_INFO', 'yze_rewrite_mode_path_info');
define('YZE_REWRITE_MODE_REWRITE', 'yze_rewrite_mode_rewrite');
define('YZE_REWRITE_MODE_NONE', 'yze_rewrite_mode_none');
define('YZE_REWRITE_MODE', YZE_REWRITE_MODE_REWRITE);
define("YZE_DEVELOP_MODE",  true );

ini_set('include_path', get_include_path().PS."../..");

require_once YANGZIE.'/yangzie.php';
require_once YANGZIE.'/hooks.php';//framework hook处理,hook处理程序
require_once YANGZIE.'/file.php';

require_once YANGZIE.'/session.php';
require_once YANGZIE.'/request.php';
require_once YANGZIE.'/cache.php';
require_once YANGZIE.'/view.php';//framework resource处理,处理资源与控制器的影射程序
require_once YANGZIE.'/controller.php';
require_once YANGZIE.'/dba.php';//Database advisor
require_once YANGZIE.'/model.php';
require_once YANGZIE.'/sql.php';
require_once YANGZIE.'/acl.php';
require_once YANGZIE.'/module.php';

require_once YANGZIE.'/router.php';
require_once YANGZIE.'/startup.php';
require_once YANGZIE.'/error.php';
require_once YANGZIE.'/daemon/daemon-functions.php';
require_once YANGZIE.'/html.php';
require_once YANGZIE.'/i18n.php';


//自动加载处理
function yze_autoload($class) {
	$_ = preg_split("{\\\\}", strtolower($class));

	if($_[0]=="app"){
		
		$module_name = $_[1];
		$class_name = $_[2];
		$loaded_module_info = \yangzie\YZE_Object::loaded_module($module_name);
		
		$file = "";
		if($loaded_module_info['is_phar']){
			$module_name .= ".phar";
			$file = "phar://";
		}
		$file .= YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $module_name . DS ;
		if(preg_match("{_controller$}i", $class)){
			$file .= "controllers" . DS . $class_name . ".class.php";
		}else if(preg_match("{_model$}i", $class)){
			$file .= "models" . DS . $class_name . ".class.php";
		}else{
			$file = YZE_INSTALL_PATH . strtr(strtolower($class), array("\\"=>"/")) . ".class.php";
		}

		if(@$file && file_exists($file)){
			include $file;
		}
	}
}

spl_autoload_register("\app\yze_autoload");

/**
 * 加载应用：
 * 1. 加载应用配置文件app/__config__.php，根据其中的配置进行系统初始化，比如数据库配置
 * 2. 加载应用中所有的模块配置文件，__module__.php，根据其中的配置加载模块的包含路径，自动包含的文件，url映射等等
 */
\yangzie\yze_load_app();

//启动会话,yze_load_app中把保存在会话中的对象类都include进来了，这样不会出现 incomplete object
\session_start();

//加载及初始化所有模块的url映射，它们指定了uri到controller的映射
\yangzie\YZE_Router::load_routers();

//加载l10n本地语言翻译处理，根据用户的请求中的指示，决定合适的显示语言
\yangzie\load_default_textdomain();
?>