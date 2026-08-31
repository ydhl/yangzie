--TEST--
scripts/generate-controller.php：private _arr2str 数组转配置字符串（反射调用）
--FILE--
<?php
namespace yangzie;
ini_set("display_errors",0);
$ROOT = dirname(dirname(dirname(__FILE__)));
// ai@2026-08-31 从 yze.php 提取 AbstractScript 真实定义，避免复制副本与源码不同步
$yze = file_get_contents($ROOT."/scripts/yze.php");
$pos = strpos($yze, "function get_colored_text");
$tail = substr($yze, $pos);
$tail = substr($tail, 0, strrpos($tail, "?>")); // 去掉文件尾部的 PHP 结束标签
// ai@2026-08-31 eval 字符串内必须显式声明命名空间：eval 在运行时编译，函数/类定义默认进入全局命名空间
eval("namespace yangzie;" . $tail);
include $ROOT."/scripts/generate-controller.php";
function __($s){ return $s; }

$c = new Generate_Controller_Script(array("module_name"=>"admin"));
$rm = new \ReflectionMethod("yangzie\\Generate_Controller_Script", "_arr2str");
$rm->setAccessible(true);
// ai@2026-08-31 输出经 var_export 包裹：内部 CRLF 转义为字面 \r\n，与 EXPECT 精确比对
function s($v){ return var_export($v, true); }
echo s($rm->invoke($c, array("name"=>"yze","ver"=>"1.0","sub"=>array("a"=>"b","n"=>2)), "\t")),"\n";
echo "===\n";
echo s($rm->invoke($c, array("routers"=>array("/foo"=>array("controller"=>"index","args"=>array())), "auths"=>array()), "\t\t")),"\n";
echo "===\n";
echo s($rm->invoke($c, array("empty"=>array()), "\t")),"\n";
--EXPECT--
'array(
		\'name\'	=> \'yze\',
		\'ver\'	=> \'1.0\',
		\'sub\'	=> array(
			\'a\'	=> \'b\',
			\'n\'	=> \'2\',
		),
	);'
===
'array(
			\'routers\'	=> array(
				\'/foo\'	=> array(
					\'controller\'	=> \'index\',
					\'args\'	=> array(
					),
				),
			),
			\'auths\'	=> array(
			),
		);'
===
'array(
		\'empty\'	=> array(
		),
	);'
