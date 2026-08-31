--TEST--
scripts/yze.php CLI：帮助输出/参数校验错误分支/交互退出
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__))));
// ai@2026-08-31 子进程运行 yze.php（顶层有 chdir/getopt/交互副作用，只能进程级测试），剥离 ANSI 与 CR 后按子串断言
function run_script($args, $stdin=null){
    $pipe = $stdin === null ? "" : "printf '%s' '".addslashes($stdin)."' | ";
    $cmd = $pipe . PHP_BINARY . " -d display_errors=0 scripts/yze.php $args 2>&1";
    exec($cmd, $lines, $ret);
    $out = implode("\n", $lines);
    $out = str_replace("\r", "", $out);
    $out = preg_replace('/\033\[[0-9;]*[A-Za-z]/', '', $out);
    return array($out, $ret);
}
$fail = 0;
function check($cond, $label){ global $fail; echo ($cond ? "PASS: " : "FAIL: ").$label."\n"; if(!$cond) $fail++; }

// -h 帮助
list($out, $ret) = run_script("-h");
check($ret === 0 && strpos($out, "Generate model (database to code):") !== false, "-h shows model help");
check($ret === 0 && strpos($out, "php scripts/yze.php --model --table=acl --module=admin") !== false, "-h shows usage example");
check($ret === 0 && strpos($out, "Usage Wizard:") !== false, "-h shows wizard usage");

// --model 缺 table
list($out, $ret) = run_script("--model");
check($ret === 1 && strpos($out, "Error: Table name is required") !== false, "--model without table fails");

// --model 缺 module
list($out, $ret) = run_script("--model --table=acl");
check($ret === 1 && strpos($out, "Error: Module name is required") !== false, "--model without module fails");

// --model 非法 module
list($out, $ret) = run_script("--model --table=acl --module='!!!'");
check($ret === 1 && strpos($out, "module name is invalid, please try again") !== false, "--model invalid module fails");

// --model 非法 db（__nodb__ 不在 db_connections 配置中）
list($out, $ret) = run_script("--model --table=acl --module=admin --db='__nodb__'");
check($ret === 1 && strpos($out, "Error: Invalid database") !== false, "--model invalid db fails");

// --mvc 缺 controller
list($out, $ret) = run_script("--mvc");
check($ret === 1 && strpos($out, "Error: controller name is required") !== false, "--mvc without controller fails");

// --mvc 非法 controller
list($out, $ret) = run_script("--mvc --controller='@@'");
check($ret === 1 && strpos($out, "controller name is invalid, please try again") !== false, "--mvc invalid controller fails");

// --mvc 缺 module
list($out, $ret) = run_script("--mvc --controller=index");
check($ret === 1 && strpos($out, "Error: Module name is required") !== false, "--mvc without module fails");

// --mvc 非法 action
list($out, $ret) = run_script("--mvc --controller=index --module=admin --action='@@'");
check($ret === 1 && strpos($out, "action name is invalid, please try again") !== false, "--mvc invalid action fails");

// --mvc 非法 module
list($out, $ret) = run_script("--mvc --controller=index --module='!!!'");
check($ret === 1 && strpos($out, "module name is invalid, please try again") !== false, "--mvc invalid module fails");

// --phar 非法 module（不触发实际打包）
list($out, $ret) = run_script("--phar --module='!!!'");
check($ret === 1 && strpos($out, "module name is invalid, please try again") !== false, "--phar invalid module fails");

// 交互模式：stdin 输入 0 显示菜单后退出
list($out, $ret) = run_script("", "0");
check($ret === 0 && strpos($out, "Generate module, controller, view Scaffolding file") !== false, "wizard menu shown");
check($ret === 0 && strpos($out, "Quit.") !== false, "wizard quit on 0");

echo $fail === 0 ? "ALL PASS\n" : "SOME FAIL\n";
--EXPECT--
PASS: -h shows model help
PASS: -h shows usage example
PASS: -h shows wizard usage
PASS: --model without table fails
PASS: --model without module fails
PASS: --model invalid module fails
PASS: --model invalid db fails
PASS: --mvc without controller fails
PASS: --mvc invalid controller fails
PASS: --mvc without module fails
PASS: --mvc invalid action fails
PASS: --mvc invalid module fails
PASS: --phar invalid module fails
PASS: wizard menu shown
PASS: wizard quit on 0
ALL PASS
