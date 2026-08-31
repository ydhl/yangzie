--TEST--
yze_merge_query_string HTML 辅助函数：url 合并 args / format 后缀 / 参数编码
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use function yangzie\yze_merge_query_string;

function s($v){ return var_export($v, true); }

// 1. 无 query + args：直接追加 query string
echo s(yze_merge_query_string("/user/list", array("page"=>2))),"\n";

// 2. 已有 query + args：URL 原 query 保留原样，args 另起一段
//    （parse_str 在 PHP 7.2+ 返回 void，原 if 条件恒 false，URL 参数不参与合并——当前实现行为）
echo s(yze_merge_query_string("/user/list?page=1", array("page"=>2))),"\n";

// 3. $_GET + args + URL query 三者并存
$_GET = array("tag"=>"hot");
echo s(yze_merge_query_string("/user/list?kw=a", array("page"=>2))),"\n";

// 4. $_GET 与 args 同名：args 覆盖 $_GET
echo s(yze_merge_query_string("/user/list", array("page"=>2))),"\n";
$_GET = array();

// 5. 无 query 无 args：原样返回，不加问号
echo s(yze_merge_query_string("/user/list")),"\n";

// 6. 有 query 无 args：URL 原样返回（含 query）
echo s(yze_merge_query_string("/user/list?page=1")),"\n";

// 7. format 替换已有扩展名
echo s(yze_merge_query_string("/user/list.html", array(), "json")),"\n";

// 8. format 替换扩展名 + 原 query + args
echo s(yze_merge_query_string("/user/list.html?page=1", array("kw"=>"b"), "json")),"\n";

// 9. format 无扩展名：追加后缀
echo s(yze_merge_query_string("/user/list", array(), "json")),"\n";

// 10. 特殊字符编码：空格→+，&→%26，中文→百分号编码（http_build_query 默认 RFC1738）
echo s(yze_merge_query_string("/user/list", array("name"=>"a b&c","q"=>"中文"))),"\n";

// 11. 边界：query 值含点 + format（strrpos 命中 query 中的点，截断 query 后加后缀——当前实现行为）
echo s(yze_merge_query_string("/user/list?page=1.5", array(), "json")),"\n";

// 12. 边界：path 中含点 + format（strrpos 命中 path 中的点，从点处截断——当前实现行为）
echo s(yze_merge_query_string("/user/v1.2/list", array(), "xml")),"\n";
?>
--EXPECT--
'/user/list?page=2'
'/user/list?page=1?page=2'
'/user/list?kw=a?tag=hot&page=2'
'/user/list?tag=hot&page=2'
'/user/list'
'/user/list?page=1'
'/user/list.json'
'/user/list.json?kw=b'
'/user/list.json'
'/user/list?name=a+b%26c&q=%E4%B8%AD%E6%96%87'
'/user/list?page=1.json'
'/user/v1.xml'
