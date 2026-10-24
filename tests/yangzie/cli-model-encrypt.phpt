--TEST--
scripts/yze.php 加密字段：CLI --encrypt/-e 及交互向导生成 encrypt Column 标注，字段不存在时报错退出
--SKIPIF--
<?php
// ai@2026-09-21 只需数据库服务可连接；测试库/表由 test 模块的 Tests_Db 在 FILE 中检查并创建
ini_set("display_errors",0);
$root = dirname(dirname(dirname(__FILE__)));
if (!file_exists($root."/app/public_html/init.php")) die("skip init.php not found");

$loaded = false;
ob_start();
chdir($root."/app/public_html");
try {
    include_once "init.php";
    $loaded = true;
} catch (\Throwable $e) {
    $loaded = false;
}
while (ob_get_level()) ob_end_clean();
if (!$loaded) die("skip init.php load failed");

try {
    $app_module = new \app\App_Module();
    $db_name = $app_module->get_module_config("default_db");
    $conn = $app_module->get_module_config("db_connections")[$db_name] ?? null;
    if (!$conn || !$conn["db_host"]) die("skip db connection not configured");
    // ai@2026-09-21 不指定 dbname 连接，库尚不存在时也可判断服务是否可用
    if (!mysqli_connect($conn["db_host"], $conn["db_user"], $conn["db_psw"], "", $conn["db_port"])) die("skip db not connectable");
} catch (\Throwable $e) {
    die("skip ".$e->getMessage());
}
?>
--FILE--
<?php
ini_set("display_errors",0);
$root = dirname(dirname(dirname(__FILE__)));
$table = "yze_encrypt_test";
// ai@2026-09-13 yzeenc9 只用于"字段不存在"用例，预期不会被创建
$modules = array("yzeenc1", "yzeenc2", "yzeenc3", "yzeenc4", "yzeenc9");

// ai@2026-09-13 递归删除目录，用于清理测试生成的 module 脚手架
// ai@2026-09-21 测试库/表由 test 模块的 Tests_Db 统一管理，测试内不再建表、删表
function t_rmdir($dir){
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f){
        if ($f=='.'||$f=='..') continue;
        $p = $dir.DIRECTORY_SEPARATOR.$f;
        is_dir($p) ? t_rmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}
function t_clean($root, $modules){
    foreach ($modules as $m){
        t_rmdir($root."/app/modules/".$m);
        t_rmdir($root."/tests/".$m);
    }
}

// ai@2026-09-13 子进程运行 yze.php（顶层有 chdir/getopt/交互副作用，只能进程级测试），剥离 ANSI 与 CR 后按子串断言
function run_script($args, $stdin=null){
    $pipe = $stdin === null ? "" : "printf '%s' '".addslashes($stdin)."' | ";
    $cmd = $pipe . PHP_BINARY . " -d display_errors=0 scripts/yze.php $args 2>&1";
    exec($cmd, $lines, $ret);
    $out = str_replace("\r", "", implode("\n", $lines));
    return array(preg_replace('/\033\[[0-9;]*[A-Za-z]/', '', $out), $ret);
}

// ai@2026-09-13 解析生成的 model 文件，返回 字段名 => #[Column(...)] 注解参数
function column_args($model_file){
    $ret = array();
    if (!file_exists($model_file)) return $ret;
    preg_match_all('/#\[Column\((?<args>[^\)]*)\)\]\s*private\s+\??[\w\\\\]*\s*\$(?<field>\w+);/', file_get_contents($model_file), $m, PREG_SET_ORDER);
    foreach ($m as $one) $ret[$one["field"]] = trim($one["args"]);
    return $ret;
}

$fail = 0;
function check($cond, $label){ global $fail; echo ($cond ? "PASS: " : "FAIL: ").$label."\n"; if(!$cond) $fail++; }

// ai@2026-09-21 连接默认库，测试库/表由 test 模块的 Tests_Db 统一检查并创建（表无外键，避免生成时触发关联表的交互输入）
ob_start();
chdir($root."/app/public_html");
include_once "init.php";
while (ob_get_level()) ob_end_clean();
chdir($root);

$app_module = new \app\App_Module();
$db_name = $app_module->get_module_config("default_db");
\app\modules\test\Tests_Db::ensure(array($db_name));
t_clean($root, $modules);

// ai@2026-09-13 兜底清理：测试中断（超时/致命错误）时也清理生成的 module
register_shutdown_function(function() use ($root, $modules){
    t_clean($root, $modules);
});

// 1. CLI 长选项：逗号分隔的多个字段都标注 encrypt，其余字段不受影响
list($out, $ret) = run_script("--model --table=$table --module=yzeenc1 --db=$db_name --encrypt=password,memo");
$model1 = $root."/app/modules/yzeenc1/models/{$table}.model.php";
$cols = column_args($model1);
check($ret === 0, "cli --encrypt generate success");
check(isset($cols["password"]) && strpos($cols["password"], "encrypt: true") !== false, "cli --encrypt marks password");
check(isset($cols["memo"]) && strpos($cols["memo"], "encrypt: true") !== false, "cli --encrypt marks memo");
check(isset($cols["id"]) && strpos($cols["id"], "encrypt") === false
    && isset($cols["other"]) && strpos($cols["other"], "encrypt") === false, "cli --encrypt keeps other fields plain");

// 2. CLI 短选项：-e 只标注指定字段
list($out, $ret) = run_script("--model --table=$table --module=yzeenc2 --db=$db_name -e=password");
$model2 = $root."/app/modules/yzeenc2/models/{$table}.model.php";
$code2 = file_exists($model2) ? file_get_contents($model2) : "";
check($ret === 0 && substr_count($code2, "encrypt: true") === 1, "cli -e marks exactly one field");

// 3. CLI 字段不存在：报错退出（exit 1）且不生成任何文件
list($out, $ret) = run_script("--model --table=$table --module=yzeenc9 --db=$db_name --encrypt=__nofield__");
check($ret === 1 && strpos($out, 'Error: encrypt field "__nofield__" not found in table "'.$table.'"') !== false, "cli unknown encrypt field exits with error");
check(!file_exists($root."/app/modules/yzeenc9"), "cli unknown encrypt field generates nothing");

// 4. 交互向导：model 流程为 4 步，第 4 步填写加密字段
list($out, $ret) = run_script("", "2\n".$db_name."\n".$table."\nyzeenc3\npassword,memo\n");
$cols = column_args($root."/app/modules/yzeenc3/models/{$table}.model.php");
check($ret === 0 && strpos($out, "4. (4/4)encrypt fields") !== false, "wizard shows 4/4 encrypt step");
check(isset($cols["password"]) && strpos($cols["password"], "encrypt: true") !== false
    && isset($cols["memo"]) && strpos($cols["memo"], "encrypt: true") !== false, "wizard encrypt step marks fields");

// 5. 交互向导：第 4 步留空表示不需要加密字段
list($out, $ret) = run_script("", "2\n".$db_name."\n".$table."\nyzeenc4\n\n");
$model4 = $root."/app/modules/yzeenc4/models/{$table}.model.php";
$code4 = file_exists($model4) ? file_get_contents($model4) : "";
check($ret === 0 && $code4 !== "" && strpos($code4, "encrypt: true") === false, "wizard empty encrypt step keeps fields plain");

// ai@2026-09-13 显式清理（shutdown 中还有一次幂等兜底）
t_clean($root, $modules);

echo $fail === 0 ? "ALL PASS\n" : "SOME FAIL\n";
--EXPECT--
PASS: cli --encrypt generate success
PASS: cli --encrypt marks password
PASS: cli --encrypt marks memo
PASS: cli --encrypt keeps other fields plain
PASS: cli -e marks exactly one field
PASS: cli unknown encrypt field exits with error
PASS: cli unknown encrypt field generates nothing
PASS: wizard shows 4/4 encrypt step
PASS: wizard encrypt step marks fields
PASS: wizard empty encrypt step keeps fields plain
ALL PASS
