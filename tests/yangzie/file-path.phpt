--TEST--
yangzie/file.php 纯函数：yze_isimage/yze_get_abs_path/yze_remove_path
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

// ===== yze_isimage：通过后缀名判断是否为图片 =====
// 常见图片后缀
echo s(\yangzie\yze_isimage("a.png")),"\n";
echo s(\yangzie\yze_isimage("a.gif")),"\n";
echo s(\yangzie\yze_isimage("a.jpeg")),"\n";
echo s(\yangzie\yze_isimage("a.jpg")),"\n";
echo s(\yangzie\yze_isimage("a.bmp")),"\n";
echo s(\yangzie\yze_isimage("a.ico")),"\n";
echo s(\yangzie\yze_isimage("a.svg")),"\n";
echo s(\yangzie\yze_isimage("a.webp")),"\n";
// 大小写不敏感
echo s(\yangzie\yze_isimage("a.PNG")),"\n";
echo s(\yangzie\yze_isimage("A.JPG")),"\n";
// 带路径的文件名
echo s(\yangzie\yze_isimage("/upload/img/avatar.png")),"\n";
// 非图片后缀
echo s(\yangzie\yze_isimage("a.php")),"\n";
echo s(\yangzie\yze_isimage("a.txt")),"\n";
echo s(\yangzie\yze_isimage("a.html")),"\n";
// 无扩展名：整个文件名参与匹配
echo s(\yangzie\yze_isimage("png")),"\n";
echo s(\yangzie\yze_isimage("/path/to/png")),"\n";
echo s(\yangzie\yze_isimage("")),"\n";

// ===== yze_get_abs_path：路径格式化（去除 . 和 .. 部分） =====
// 文档示例：this/is/../a/./test/.///is（$in 为空时结果带前导分隔符）
echo s(\yangzie\yze_get_abs_path("this/is/../a/./test/.///is")),"\n";
// 绝对路径保留前导分隔符
echo s(\yangzie\yze_get_abs_path("/this/is/../a")),"\n";
// 相对路径（$in 为空）结果为绝对形式
echo s(\yangzie\yze_get_abs_path("a/b/../../c")),"\n";
// 上溯超出根目录时多余的 .. 被丢弃
echo s(\yangzie\yze_get_abs_path("a/../../../c")),"\n";
// $in 前置拼接
echo s(\yangzie\yze_get_abs_path("b/c", "a")),"\n";
// $path 以 / 开头时与 $in 拼接
echo s(\yangzie\yze_get_abs_path("/b", "a")),"\n";
// 连续分隔符合并
echo s(\yangzie\yze_get_abs_path("a//b")),"\n";
// 前导 . 被忽略
echo s(\yangzie\yze_get_abs_path("./a")),"\n";
// 反斜杠统一为系统分隔符
echo s(\yangzie\yze_get_abs_path("a\\b\\..\\c")),"\n";
// 空路径与根路径
echo s(\yangzie\yze_get_abs_path("")),"\n";
echo s(\yangzie\yze_get_abs_path("/")),"\n";

// ===== yze_remove_path：删除路径中的指定子串 =====
// 前缀删除
echo s(\yangzie\yze_remove_path("/a/b/c.php", "/a/b/")),"\n";
echo s(\yangzie\yze_remove_path("/a/b/c.php", "/a/")),"\n";
// 子串不匹配时原样返回
echo s(\yangzie\yze_remove_path("/a/b/c.php", "/x/")),"\n";
// 替换所有出现位置（非仅前缀）
echo s(\yangzie\yze_remove_path("/a/xx/b", "/xx/")),"\n"; // /a + b 直接拼接
// 删除无尾部斜杠的子串
echo s(\yangzie\yze_remove_path("/a/b/c.php", "/a")),"\n";
?>
--EXPECT--
true
true
true
true
true
true
true
true
true
true
true
false
false
false
true
false
false
'/this/a/test/is'
'/this/a'
'/c'
'/c'
'a/b/c'
'a/b'
'/a/b'
'/a'
'/a/c'
'/'
'/'
'c.php'
'b/c.php'
'/a/b/c.php'
'/ab'
'/b/c.php'
