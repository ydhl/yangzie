--TEST--
YZE_Request init 流程与静态 parse_url：路由解析、模块/控制器映射、请求方法、输出格式
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_Request;

// 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_null($v)) return 'null';
    return var_export($v, true);
}

// 1. 静态 parse_url：默认 /module/controller/action/args 格式
echo "pu_full:",s(YZE_Request::parse_url(array(), '/foo/bar/baz/va1/va2')),"\n";
echo "pu_mc:",s(YZE_Request::parse_url(array(), '/m/c')),"\n";
echo "pu_m:",s(YZE_Request::parse_url(array(), '/m')),"\n";
// 数字 action 进入 args
echo "pu_num:",s(YZE_Request::parse_url(array(), '/m/c/1')),"\n";
// format 后缀进入 args.__yze_resp_format__
echo "pu_fmt:",s(YZE_Request::parse_url(array(), '/m/c/a.json')),"\n";
// 2. 静态 parse_url：路由表匹配
$routers = array('test' => array('/hello' => array('controller' => 'Foo', 'action' => 'hi', 'args' => array('k' => 'v'))));
echo "pu_route:",s(YZE_Request::parse_url($routers, '/hello')),"\n";
echo "pu_route_fmt:",s(YZE_Request::parse_url($routers, '/hello.json')),"\n";
echo "pu_route_slash:",s(YZE_Request::parse_url($routers, '/hello/')),"\n";
// 不匹配路由时回退默认解析
echo "pu_route_miss:",s(YZE_Request::parse_url($routers, '/hello/x')),"\n";

$req = YZE_Request::get_instance();

// 3. init GET：test 模块 + index 控制器
$req->init('/test/index', null, null, 'GET');
echo "uri:",s($req->the_uri()),"\n";
echo "full_uri:",s($req->the_full_uri()),"\n";
echo "query:",s($req->the_query()),"\n";
echo "module:",s($req->module()),"\n";
echo "module_class:",s($req->module_class()),"\n";
echo "module_instance:",s($req->module_instance() !== null),"\n";
echo "controller_name:",s($req->controller_name()),"\n";
echo "controller_name_sort:",s($req->controller_name(true)),"\n";
echo "controller_class:",s($req->controller_class()),"\n";
echo "controller_class_sort:",s($req->controller_class(true)),"\n";
echo "controller_instance:",s(get_class($req->controller_instance())),"\n";
echo "method:",s($req->the_method()),"\n";
echo "req_method:",s($req->get_request_method()),"\n";
echo "is_get:",s($req->is_get()),"\n";
echo "is_post:",s($req->is_post()),"\n";
echo "view_path:",s(substr($req->view_path(), -strlen("app/modules/test/views"))),"\n";

// 4. init POST：映射方法前加 POST_ 前缀
$req->init('/test/index', null, null, 'POST');
echo "post_method:",s($req->the_method()),"\n";
echo "post_is_get:",s($req->is_get()),"\n";
echo "post_is_post:",s($req->is_post()),"\n";

// 5. init('/')：无 module/controller 时使用默认控制器
$req->init('/', null, null, 'GET');
echo "def_controller_name:",s($req->controller_name()),"\n";
echo "def_controller_name_sort:",s($req->controller_name(true)),"\n";
echo "def_controller_class:",s($req->controller_class()),"\n";
echo "def_controller_class_sort:",s($req->controller_class(true)),"\n";
echo "def_instance:",s(get_class($req->controller_instance())),"\n";
echo "def_method:",s($req->the_method()),"\n";

// 6. init 带 action/format/query：/test/index/detail.json?page=2
$req->init('/test/index/detail.json?page=2', null, null, 'GET');
echo "action_uri:",s($req->the_uri()),"\n";
echo "action_full_uri:",s($req->the_full_uri()),"\n";
echo "action_query:",s($req->the_query()),"\n";
echo "action_method:",s($req->the_method()),"\n";
echo "action_format:",s($req->get_var('__yze_resp_format__')),"\n";
echo "action_output:",s($req->get_output_format()),"\n";
echo "action_page:",s($req->get_from_get('page')),"\n";
echo "action_get_datas:",s($req->the_get_datas()),"\n";

// 7. format 参数优先于 UA 判断
$req->init('/test/index', null, 'xml', 'GET');
echo "fmt_output:",s($req->get_output_format()),"\n";

// 8. OPTIONS 请求 auth 直接放行
$req->init('/test/index', null, null, 'OPTIONS');
echo "auth:",s($req->auth() === $req),"\n";
?>
--EXPECT--
pu_full:{"controller_name":"bar","module":"foo","args":["va1","va2"],"action":"baz"}
pu_mc:{"controller_name":"c","module":"m"}
pu_m:{"module":"m","controller_name":"index"}
pu_num:{"controller_name":"c","module":"m","args":["1"]}
pu_fmt:{"controller_name":"c","module":"m","action":"a","args":{"__yze_resp_format__":"json"}}
pu_route:{"controller_name":"foo","module":"test","action":"hi","args":{"0":"/hello","k":"v"}}
pu_route_fmt:{"controller_name":"foo","module":"test","action":"hi","args":{"0":"/hello.json","__yze_resp_format__":"json","1":"json","k":"v"}}
pu_route_slash:{"controller_name":"foo","module":"test","action":"hi","args":{"0":"/hello/","k":"v"}}
pu_route_miss:{"controller_name":"x","module":"hello"}
uri:'/test/index'
full_uri:'/test/index'
query:null
module:'test'
module_class:'Test_Module'
module_instance:true
controller_name:'\\app\\test\\index'
controller_name_sort:'index'
controller_class:'\\app\\test\\Index_Controller'
controller_class_sort:'Index_Controller'
controller_instance:'app\\test\\Index_Controller'
method:'index'
req_method:'GET'
is_get:true
is_post:false
view_path:'app/modules/test/views'
post_method:'POST_index'
post_is_get:false
post_is_post:true
def_controller_name:'\\app\\test\\yze_default'
def_controller_name_sort:'yze_default'
def_controller_class:'\\app\\test\\Yze_Default_Controller'
def_controller_class_sort:'Yze_Default_Controller'
def_instance:'yangzie\\Yze_Default_Controller'
def_method:'index'
action_uri:'/test/index/detail.json'
action_full_uri:'/test/index/detail.json?page=2'
action_query:'page=2'
action_method:'detail'
action_format:'json'
action_output:'json'
action_page:'2'
action_get_datas:{"page":"2"}
fmt_output:'xml'
auth:true