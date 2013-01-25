<?php
/**
 * 该文件的职责：
 * 1.包含启动系统所需要的所有文件
 * 2.调用系统启动代码
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://www.yangzie.net
 *
 */

require_once YANGZIE.'/auth.php';
require_once YANGZIE.'/yangzie.php';
require_once YANGZIE.'/validate.php';
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
require_once YANGZIE.'/hooks.php';//framework hook处理,hook处理程序
require_once YANGZIE.'/router.php';
require_once YANGZIE.'/startup.php';
require_once YANGZIE.'/error.php';
require_once YANGZIE.'/daemon/daemon-functions.php';
require_once YANGZIE.'/html.php';
require_once YANGZIE.'/i18n.php';
require_once YANGZIE.'/dispatch.php';



/**
 * 加载应用：
 * 1. 加载应用配置文件app/__config__.php，根据其中的配置进行系统初始化，比如数据库配置
 * 2. 加载应用中所有的模块配置文件，__module__.php，根据其中的配置加载模块的包含路径，自动包含的文件，url映射等等
 */
yze_load_app();

//启动会话,yze_load_app中把保存在会话中的对象类都include进来了，这样不会出现 incomplete object
session_start();

//加载及初始化所有模块的url映射，它们指定了uri到controller的映射
YZE_Router::load_routers();

//加载l10n本地语言翻译处理，根据用户的请求中的指示，决定合适的显示语言
load_default_textdomain();

?>