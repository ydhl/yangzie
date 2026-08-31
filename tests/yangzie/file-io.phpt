--TEST--
yangzie/file.php 文件操作：yze_make_dirs/yze_copy_file/yze_move_file/yze_copy_dir
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

// ai@2026-08-29 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// ai@2026-08-29 测试清理用：递归删除目录
function rrmdir($dir){
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f){
        if ($f=='.'||$f=='..') continue;
        $p = $dir.DIRECTORY_SEPARATOR.$f;
        is_dir($p) ? rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

$base = sys_get_temp_dir()."/yze_file_test_".uniqid();

// ===== yze_make_dirs：递归创建目录 =====
echo s(\yangzie\yze_make_dirs($base."/a/b/c")),"\n"; // 无返回值 null
echo s(file_exists($base."/a/b/c")),"\n";            // 已创建
echo s(\yangzie\yze_make_dirs($base."/a/b/c")),"\n"; // 已存在时直接返回
echo s(file_exists($base."/a/b")),"\n";
// 带尾部分隔符
echo s(\yangzie\yze_make_dirs($base."/d/e/")),"\n";
echo s(file_exists($base."/d/e")),"\n";

// ===== yze_copy_file：拷贝文件（目标目录不存在则自动创建） =====
$src = $base."/src.txt";
file_put_contents($src, "hello yze");
$ret = \yangzie\yze_copy_file($src, $base."/copy");
echo s(str_replace($base,"{BASE}",$ret)),"\n";         // {BASE}/copy/src.txt
echo s(file_get_contents($base."/copy/src.txt")),"\n"; // 内容一致
// dist_dir 为空返回 false
echo s(\yangzie\yze_copy_file($src, "")),"\n";
echo s(\yangzie\yze_copy_file($src, null)),"\n";
// 源文件不存在返回 false
echo s(\yangzie\yze_copy_file($base."/no_such.txt", $base."/copy2")),"\n";

// ===== yze_move_file：移动文件 =====
$msrc = $base."/move_src.txt";
file_put_contents($msrc, "move me");
$mret = \yangzie\yze_move_file($msrc, $base."/moved");
echo s(str_replace($base,"{BASE}",$mret)),"\n";                    // {BASE}/moved/move_src.txt
echo s(file_exists($msrc)),"\n";                                    // 源文件已被删除
echo s(file_get_contents($base."/moved/move_src.txt")),"\n";        // 内容一致
// 源文件不存在返回 false
echo s(\yangzie\yze_move_file($base."/no_such.txt", $base."/moved")),"\n";

// ===== yze_copy_dir：拷贝目录及其子目录 =====
$sd = $base."/srcdir";
mkdir($sd,0777,true);
file_put_contents($sd."/f1.txt", "f1");
mkdir($sd."/sub",0777,true);
file_put_contents($sd."/sub/f2.txt", "f2");
// 目标目录不存在时自动创建
echo s(\yangzie\yze_copy_dir($sd, $base."/destdir")),"\n";          // true
echo s(file_exists($base."/destdir/f1.txt")),"\n";                  // true
echo s(file_get_contents($base."/destdir/f1.txt")),"\n";            // f1
echo s(file_exists($base."/destdir/sub/f2.txt")),"\n";              // true
echo s(file_get_contents($base."/destdir/sub/f2.txt")),"\n";        // f2
// 目标目录已存在
mkdir($base."/destdir2");
echo s(\yangzie\yze_copy_dir($sd, $base."/destdir2")),"\n";         // true
echo s(file_exists($base."/destdir2/sub/f2.txt")),"\n";             // true
// 重复拷贝不报错
echo s(\yangzie\yze_copy_dir($sd, $base."/destdir")),"\n";          // true
echo s(file_exists($base."/destdir/sub/f2.txt")),"\n";              // true

// ===== 清理 =====
rrmdir($base);
echo s(file_exists($base)),"\n"; // false
?>
--EXPECT--
NULL
true
NULL
true
NULL
true
'{BASE}/copy/src.txt'
'hello yze'
false
false
false
'{BASE}/moved/move_src.txt'
false
'move me'
false
true
true
'f1'
true
'f2'
true
true
true
true
false
