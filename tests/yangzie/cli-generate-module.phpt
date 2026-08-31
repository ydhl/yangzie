--TEST--
scripts/generate-module.php：generate() 生成模块脚手架目录与 __config__.php（随机模块名，用例结束清理）
--FILE--
<?php
namespace yangzie;
ini_set("display_errors",0);
$ROOT = dirname(dirname(dirname(__FILE__)));
// ai@2026-08-31 从 yze.php 提取 AbstractScript/rrmdir/get_colored_text 等真实定义，避免复制副本与源码不同步
$yze = file_get_contents($ROOT."/scripts/yze.php");
$pos = strpos($yze, "function get_colored_text");
$tail = substr($yze, $pos);
$tail = substr($tail, 0, strrpos($tail, "?>")); // 去掉文件尾部的 PHP 结束标签
// ai@2026-08-31 eval 字符串内必须显式声明命名空间：eval 在运行时编译，函数/类定义默认进入全局命名空间
eval("namespace yangzie;" . $tail);
include $ROOT."/scripts/generate-module.php";
function __($s){ return $s; }

// ai@2026-08-31 随机模块名避免与现有模块冲突；无论测试成败都清理生成的目录
$m = "zztest_".substr(md5(uniqid(mt_rand(), true)), 0, 8);
register_shutdown_function(function() use ($ROOT, $m){
    rrmdir($ROOT."/app/modules/".$m);
    rrmdir($ROOT."/tests/".$m);
});

$fail = 0;
function check($cond, $label){ global $fail; echo ($cond ? "PASS: " : "FAIL: ").$label."\n"; if(!$cond) $fail++; }

// 生成模块（丢弃脚手架输出）
ob_start();
(new Generate_Module_Script(array("module_name"=>$m)))->generate();
ob_end_clean();

$mod = $ROOT."/app/modules/".$m;
check(is_dir($mod), "module dir created");
foreach (array("controllers","models","views","hooks","public_html") as $d){
    check(is_dir($mod."/".$d), "sub dir $d created");
}
check(is_dir($ROOT."/tests/".$m), "tests dir created");
$cfg = @file_get_contents($mod."/__config__.php");
check($cfg !== false && strpos($cfg, "class ".ucfirst($m)."_Module extends YZE_Base_Module") !== false, "__config__.php generated with Module class");
check($cfg !== false && strpos($cfg, "'name'=>'".ucfirst($m)."'") !== false, "__config__.php name set");

// 已存在的模块重复生成不报错（目录已存在 check_dir 跳过）
ob_start();
(new Generate_Module_Script(array("module_name"=>$m)))->generate();
ob_end_clean();
check(is_dir($mod), "regenerate existing module ok");

// 清理
rrmdir($mod);
rrmdir($ROOT."/tests/".$m);
check(!is_dir($mod) && !is_dir($ROOT."/tests/".$m), "cleanup removed dirs");

echo $fail === 0 ? "ALL PASS\n" : "SOME FAIL\n";
--EXPECT--
PASS: module dir created
PASS: sub dir controllers created
PASS: sub dir models created
PASS: sub dir views created
PASS: sub dir hooks created
PASS: sub dir public_html created
PASS: tests dir created
PASS: __config__.php generated with Module class
PASS: __config__.php name set
PASS: regenerate existing module ok
PASS: cleanup removed dirs
ALL PASS
