--TEST--
yangzie/view.php JSON/XML 视图输出与 build_view 静态构建
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_Request;
use yangzie\YZE_Resource_Controller;
use yangzie\YZE_JSON_View;
use yangzie\YZE_XML_View;

// ai@2026-08-29 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// ai@2026-08-29 初始化请求与测试控制器（YZE_Resource_Controller 无抽象方法可直接继承）
$req = YZE_Request::get_instance();
$req->init('/test/index', null, null, 'GET');
class YZE_View_Test_Controller extends YZE_Resource_Controller {}
$c = new YZE_View_Test_Controller();

// ===== YZE_JSON_View：json 序列化输出（JSON_UNESCAPED_UNICODE） =====
echo s((new YZE_JSON_View($c, array("a"=>1,"msg"=>"hi")))->get_output()),"\n";
// 中文不转义
echo s((new YZE_JSON_View($c, array("name"=>"张三")))->get_output()),"\n";
// error 静态构建：{success:false, data, code, msg}
echo s(YZE_JSON_View::error($c, "出错了", 404, array("x"=>1))->get_output()),"\n";
// success 静态构建：{success:true, data, msg:null}
echo s(YZE_JSON_View::success($c, array("k"=>"v"))->get_output()),"\n";
// success 无数据
echo s(YZE_JSON_View::success($c)->get_output()),"\n";
// error 全默认参数
echo s(YZE_JSON_View::error($c)->get_output()),"\n";
// data 存取
$jv = new YZE_JSON_View($c, array("a"=>1));
echo s($jv->get_data("a")),"\n";
echo s($jv->get_data("no_such")),"\n";
$jv->set_data("b", 2);
echo s($jv->get_datas()),"\n";

// ai@2026-08-29 XML 视图输出 asXML() 使用 CRLF 行尾，统一规范化为 LF 便于与 EXPECT 精确比对
function sx($v){
    return s(str_replace("\r\n", "\n", $v));
}

// ===== YZE_XML_View：数组递归转 xml =====
// 基本键值
echo sx((new YZE_XML_View($c, array("name"=>"yze","ver"=>"1.0")))->get_output()),"\n";
// 标量数值键输出 <0>/<1>
echo sx((new YZE_XML_View($c, array("list"=>array("a","b"),"k"=>"v")))->get_output()),"\n";
// 数组数值键输出 item0/item1
echo sx((new YZE_XML_View($c, array("grid"=>array(0=>array("a"=>1), 1=>array("b"=>2)))))->get_output()),"\n";
// error 静态构建：<success/>（false 节点）
echo sx(YZE_XML_View::error($c, "bad", 500)->get_output()),"\n";
// success 静态构建：<success>1</success>
echo sx(YZE_XML_View::success($c, "ok")->get_output()),"\n";
// 空数据输出 <root/>
echo sx((new YZE_XML_View($c, array()))->get_output()),"\n";

// ===== YZE_View_Adapter::build_view：按格式构建视图 =====
echo s(get_class(YZE_JSON_View::build_view($c, "json", array("a"=>1)))),"\n";
echo s(get_class(YZE_JSON_View::build_view($c, "xml", array("a"=>1)))),"\n";
echo s(get_class(YZE_JSON_View::build_view($c, "tpl", array("a"=>1)))),"\n";
echo s(get_class(YZE_JSON_View::build_view($c, "unknown", array("a"=>1)))),"\n";
?>
--EXPECT--
'{"a":1,"msg":"hi"}'
'{"name":"张三"}'
'{"success":false,"data":{"x":1},"code":404,"msg":"出错了"}'
'{"success":true,"data":{"k":"v"},"msg":null}'
'{"success":true,"data":null,"msg":null}'
'{"success":false,"data":null,"code":null,"msg":null}'
1
NULL
{"a":1,"b":2}
'<?xml version="1.0"?>
<root><name>yze</name><ver>1.0</ver></root>
'
'<?xml version="1.0"?>
<root><list><0>a</0><1>b</1></list><k>v</k></root>
'
'<?xml version="1.0"?>
<root><grid><item0><a>1</a></item0><item1><b>2</b></item1></grid></root>
'
'<?xml version="1.0"?>
<root><success/><data/><code>500</code><msg>bad</msg></root>
'
'<?xml version="1.0"?>
<root><success>1</success><data>ok</data><msg/></root>
'
'<?xml version="1.0"?>
<root/>
'
'yangzie\\YZE_JSON_View'
'yangzie\\YZE_XML_View'
'yangzie\\YZE_Notpl_View'
'yangzie\\YZE_Notpl_View'
