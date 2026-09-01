--TEST--
yangzie/view.php YZE_Layout/YZE_Redirect/YZE_Response_304_NotModified
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_Request;
use yangzie\YZE_Resource_Controller;
use yangzie\YZE_Notpl_View;
use yangzie\YZE_Layout;
use yangzie\YZE_Redirect;
use yangzie\YZE_Response_304_NotModified;
use yangzie\YZE_Resource_Not_Found_Exception;

// ai@2026-08-29 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// ai@2026-08-29 初始化请求与测试控制器
$req = YZE_Request::get_instance();
$req->init('/test/index', null, null, 'GET');
class YZE_View_Test_Controller extends YZE_Resource_Controller {}
$c = new YZE_View_Test_Controller();

// ===== YZE_Redirect：重定向响应 =====
$r = new YZE_Redirect("/foo/bar", $c);
echo s($r->destinationURI()),"\n";   // 目标 url
echo s($r->output(true)),"\n";       // return 时返回目标 url
echo s($r->get_data("any")),"\n";    // 无视图数据始终返回空串
ob_start();
$r->output();                        // 直接输出 Location header（CLI 下无输出）
echo s(ob_get_clean()),"\n";

// ===== YZE_Response_304_NotModified：仅输出 http 头 =====
$n = new YZE_Response_304_NotModified(array("ETag"=>"abc", "X-Cache"=>"hit"), $c);
echo s($n->get_data("ETag")),"\n";   // 构造时设置的响应头
echo s($n->get_data("X-Cache")),"\n";
$n->add_header("X-Extra", "1");
echo s($n->get_data("X-Extra")),"\n"; // add_header 追加
ob_start();
$n->output();                        // CLI 下 header() 无输出
echo s(ob_get_clean()),"\n";

// ===== YZE_Layout：布局包裹视图 =====
$view = new YZE_Notpl_View("inner", $c);
$layout = new YZE_Layout("json", $view, $c);
echo s($layout->get_output()),"\n";  // json.layout.php 输出 content_of_view = inner
// 视图指定 layout 时覆盖构造传入的布局
$view2 = new YZE_Notpl_View("inner2", $c);
$view2->layout = "json";
$layout2 = new YZE_Layout("tpl", $view2, $c);
echo s($layout2->get_output()),"\n"; // 覆盖为 json 布局 → inner2
// pjax 请求：只输出标题 + 内容，不使用布局（标题来自被包裹视图的 data）
$_SERVER['HTTP_X_PJAX'] = "true";
$pv = new YZE_Notpl_View("pjax-body", $c);
$pv->set_data("yze_page_title", "标题");
$layout3 = new YZE_Layout("json", $pv, $c);
echo s($layout3->get_output()),"\n"; // <title>标题</title>pjax-body
unset($_SERVER['HTTP_X_PJAX']);
// 移动端 UA 且无 moblayout 时回退普通布局
$_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (iPhone)";
$layout4 = new YZE_Layout("json", new YZE_Notpl_View("mob-body", $c), $c);
echo s($layout4->get_output()),"\n"; // 无 json.moblayout.php → json.layout.php → mob-body
unset($_SERVER['HTTP_USER_AGENT']);
// 布局文件不存在抛异常
try {
    $layout5 = new YZE_Layout("no_such", new YZE_Notpl_View("x", $c), $c);
    $layout5->get_output();
    echo "NO_EXCEPTION\n";
} catch (YZE_Resource_Not_Found_Exception $e) {
    echo "LAYOUT_EX:",s(strpos($e->getMessage(), "app/vendor/layouts/no_such.layout.php")!==false?"no_such.layout.php":""),"\n";
}
?>
--EXPECT--
'/foo/bar'
'/foo/bar'
''
''
'abc'
'hit'
'1'
''
'inner'
'inner2'
'<title>标题</title>pjax-body'
'mob-body'
LAYOUT_EX:'no_such.layout.php'
