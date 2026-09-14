--TEST--
app/__config__.php include_files 配置：包含指定文件、目录（含尾斜杠）内的所有文件，且不递归子目录
--FILE--
<?php
ini_set("display_errors",0);

// ai@2026-09-12 配置文件必须在 include init.php 之前替换，因为 App_Module::config() 是类中的字面量，运行时无法注入
$root       = dirname(dirname(dirname(__FILE__)));
$configFile = $root."/app/__config__.php";
$origConfig = file_get_contents($configFile);

// ai@2026-09-12 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_null($v)) return 'null';
    return var_export($v, true);
}

// ai@2026-09-12 递归删除目录，用于清理测试临时目录
function t_rmdir($dir){
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f){
        if ($f=='.'||$f=='..') continue;
        $p = $dir.DIRECTORY_SEPARATOR.$f;
        is_dir($p) ? t_rmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

// ai@2026-09-12 临时目录放在项目根下：include_files 的路径是相对 YZE_INSTALL_PATH 解析的
$tmpName = "tmp_include_test_".uniqid();
$tmp     = $root."/".$tmpName;

// ai@2026-09-12 兜底：进程中断（超时/致命错误）时也恢复配置并清理临时目录
register_shutdown_function(function() use ($configFile, $origConfig, $tmp){
    if (@file_get_contents($configFile) !== $origConfig) @file_put_contents($configFile, $origConfig);
    t_rmdir($tmp);
});

// ai@2026-09-12 1. 准备测试数据：单个文件、目录内多个文件、非 php 文件、子目录、带尾斜杠的目录
mkdir($tmp."/dir_no_slash/sub", 0777, true);
mkdir($tmp."/dir_with_slash", 0777, true);
file_put_contents($tmp."/single.php", '<?php define("YZE_TEST_INC_FILE","single"); $GLOBALS["YZE_TEST_INC_ORDER"][]="single";');
file_put_contents($tmp."/dir_no_slash/01_a.php", '<?php define("YZE_TEST_INC_A",true); $GLOBALS["YZE_TEST_INC_ORDER"][]="a";');
file_put_contents($tmp."/dir_no_slash/02_b.php", '<?php define("YZE_TEST_INC_B",true); $GLOBALS["YZE_TEST_INC_ORDER"][]="b";');
file_put_contents($tmp."/dir_no_slash/sub/nested.php", '<?php define("YZE_TEST_INC_NESTED",true);');
file_put_contents($tmp."/dir_no_slash/note.txt", 'PLAIN_TEXT_INCLUDED');
file_put_contents($tmp."/dir_with_slash/c.php", '<?php define("YZE_TEST_INC_C",true); $GLOBALS["YZE_TEST_INC_ORDER"][]="c";');

// ai@2026-09-12 2. 只替换 include_files 一项，保留原有的 vendor/autoload.php
$block = "'include_files'=>[\n"
       . "\t\t\t\t\"vendor/autoload.php\",\n"
       . "\t\t\t\t\"{$tmpName}/single.php\",\n"
       . "\t\t\t\t\"{$tmpName}/dir_no_slash\",\n"
       . "\t\t\t\t\"{$tmpName}/dir_with_slash/\",\n"
       . "\t\t\t]";
$newConfig = preg_replace_callback('/\'include_files\'\s*=>\s*\[[^\]]*\]/s', function($m) use ($block){ return $block; }, $origConfig, 1, $cnt);
echo "config_replace:", s($cnt),"\n";
file_put_contents($configFile, $newConfig);
clearstatcache();

// ai@2026-09-12 3. 启动应用，触发 yze_load_app() 中的 include_files 处理；同时捕获包含过程产生的输出
ob_start();
chdir($root."/app/public_html");
include "init.php";
$captured = ob_get_clean();

// ai@2026-09-12 4. 文件直接包含
echo "file:", s(defined("YZE_TEST_INC_FILE")),"\n";
// ai@2026-09-12 5. 目录（无尾斜杠）内的文件全部被包含
echo "dir_a:", s(defined("YZE_TEST_INC_A")),"\n";
echo "dir_b:", s(defined("YZE_TEST_INC_B")),"\n";
// ai@2026-09-12 6. 目录（带尾斜杠）内的文件被包含
echo "dir_c:", s(defined("YZE_TEST_INC_C")),"\n";
// ai@2026-09-12 7. 子目录不递归，其内文件未被包含
echo "nested:", s(defined("YZE_TEST_INC_NESTED")),"\n";
// ai@2026-09-12 8. 包含顺序：按配置项顺序，目录内按文件名排序（01_a、02_b、note.txt、sub）
echo "order:", s($GLOBALS["YZE_TEST_INC_ORDER"] ?? array()),"\n";
// ai@2026-09-12 9. 目录内非 php 文件也被 include（内容原样输出）
echo "captured:", s($captured),"\n";
// ai@2026-09-12 10. 配置中保留的 vendor/autoload.php 生效（composer autoload 已注册）
echo "vendor_autoload:", s(class_exists("Composer\\Autoload\\ClassLoader")),"\n";

// ai@2026-09-12 11. 恢复配置并清理临时目录（shutdown 中还有一次幂等兜底）
file_put_contents($configFile, $origConfig);
t_rmdir($tmp);
clearstatcache();
echo "config_restored:", s(file_get_contents($configFile) === $origConfig),"\n";
echo "tmp_removed:", s(!file_exists($tmp)),"\n";
?>
--EXPECT--
config_replace:1
file:true
dir_a:true
dir_b:true
dir_c:true
nested:false
order:["single","a","b","c"]
captured:'PLAIN_TEXT_INCLUDED'
vendor_autoload:true
config_restored:true
tmp_removed:true
