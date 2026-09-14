--TEST--
yangzie/i18n.php __() 与 _e()：MO 翻译加载、未加载回退原文、domain 隔离、_e 输出
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_I18N;

// ai@2026-09-12 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_null($v)) return 'null';
    return var_export($v, true);
}

$mo      = YZE_INSTALL_PATH."i18n/zh-cn.mo";
$signin  = 'Please signin';
$dateMsg = "Field '%s' value %s is not the date value";

// ai@2026-09-12 1. 清空后未加载任何翻译，__ 返回原文
YZE_I18N::get_instance()->clear();
echo "before:", s(\yangzie\__($signin)),"\n";

// ai@2026-09-12 2. load_textdomain 加载 zh-cn.mo 成功
echo "load:", s(\yangzie\load_textdomain('default', $mo)),"\n";

// ai@2026-09-12 3. 已加载的字符串返回译文
echo "signin:", s(\yangzie\__($signin)),"\n";
echo "date:", s(\yangzie\__($dateMsg)),"\n";

// ai@2026-09-12 4. 未收录的字符串原样返回
echo "unknown:", s(\yangzie\__('no such i18n key')),"\n";

// ai@2026-09-12 5. _e 直接输出译文（输出缓冲捕获，先取出内容再输出避免缓冲嵌套）
ob_start();
\yangzie\_e($signin);
$printed = ob_get_clean();
echo "e:", s($printed),"\n";

// ai@2026-09-12 6. domain 隔离：新 domain 未加载时返回原文，加载后返回译文
echo "domain_before:", s(\yangzie\__($signin, 'other')),"\n";
echo "load_other:", s(\yangzie\load_textdomain('other', $mo)),"\n";
echo "domain_after:", s(\yangzie\__($signin, 'other')),"\n";
ob_start();
\yangzie\_e($signin, 'other');
$printed_other = ob_get_clean();
echo "e_other:", s($printed_other),"\n";

// ai@2026-09-12 7. translate 与 __ 等价（别名）
echo "translate:", s(\yangzie\translate($signin)),"\n";

// ai@2026-09-12 8. 不可读的 MO 文件加载失败
echo "load_missing:", s(\yangzie\load_textdomain('bad', '/no/such/file.mo')),"\n";

// ai@2026-09-12 9. 已加载 domain 列表包含 default / other
$loaded = array_keys(YZE_I18N::get_instance()->getLoadedI18N());
sort($loaded);
echo "domains:", s($loaded),"\n";

// ai@2026-09-12 10. clear 后回退原文
YZE_I18N::get_instance()->clear();
echo "after_clear:", s(\yangzie\__($signin)),"\n";
?>
--EXPECT--
before:'Please signin'
load:true
signin:'请登录'
date:'字段 ‘%s’ 的值 %s 不是有效的日期类型'
unknown:'no such i18n key'
e:'请登录'
domain_before:'Please signin'
load_other:true
domain_after:'请登录'
e_other:'请登录'
translate:'请登录'
load_missing:false
domains:["default","other"]
after_clear:'Please signin'
