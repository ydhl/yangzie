<?php
/**
 * 该文件是系统的入口
 * 
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://www.yangzie.net
 *
 */

//初始化系统的一些配置信息，比如系统变量，yangzie的包含路径及一些php的配置问题
require 'init.php';

//加载系统需要的代码及初始化yangzie的名个功能处理
require 'load.php';

//开始处理请求
yze_run();
?>
