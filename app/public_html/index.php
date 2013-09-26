<?php
/**
 * 该文件是系统的入口
 * 
 * @category Framework
 * @package  Yangzie
 * @author   liizii <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version  SVN: $Id$
 * @link     http://yangzie.yidianhulian.com
 *
 */
//sae_xhprof_start();
//初始化系统的一些配置信息，比如系统变量，yangzie的包含路径及一些php的配置问题
require 'init.php';

//加载系统需要的代码及初始化yangzie的名个功能处理
require 'load.php';

function __autoload($class_name) {
	$_ = preg_split("{\\\\}", strtolower($class_name));

	if($_[0]=="app"){
		
		if(preg_match("{_controller$}i", $class_name)){
			$file = YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $_[1] . DS . "controllers" . DS . $_[2] . ".class.php";
		}else if(preg_match("{_validate$}i", $class_name)){
			$file = YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $_[1] . DS . "validates" . DS . $_[2] . ".class.php";
		}else if(preg_match("{_model$}i", $class_name)){
			$file = YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $_[1] . DS . "models" . DS . $_[2] . ".class.php";
		}else{
			$file = YZE_INSTALL_PATH . strtr(strtolower($class_name), array("\\"=>"/")) . ".class.php";
		}

		if(@$file && file_exists($file)){
			
			include $file;
		}
	}
}

//开始处理请求
\yangzie\yze_go();
//sae_xhprof_end();
?>
