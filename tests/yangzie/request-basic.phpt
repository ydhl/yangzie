--TEST--
YZE_Request 基础数据存取与工具方法：post/get/cookie/server/request 取值、上下文、scheme、referer、移动端判断、语言、GMT 格式化
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

$req = YZE_Request::get_instance();

// 1. CLI 环境下默认的 post/get 数据为空数组
echo "post_datas:",s($req->the_post_datas()),"\n";
echo "get_datas:",s($req->the_get_datas()),"\n";

// 2. uuid 每次请求唯一，非空字符串
echo "uuid:",s(is_string($req->uuid()) && $req->uuid() !== ''),"\n";

// 3. 未 init 时请求方法为 null
echo "req_method:",s($req->get_request_method()),"\n";

// 4. get_var 不存在时返回 default
echo "get_var_miss:",s($req->get_var('no_key','dft')),"\n";

// 5. set/get 上下文存取
$req->set('k1','v1');
echo "ctx_get:",s($req->get('k1')),"\n";
echo "ctx_miss:",s($req->get('nope')),"\n";

// 6. set_post/get_from_post/the_post_datas
$req->set_post('name','tom');
echo "post_name:",s($req->get_from_post('name')),"\n";
echo "post_default:",s($req->get_from_post('nope','dft')),"\n";
echo "post_after_set:",s($req->the_post_datas()),"\n";

// 7. 反射注入各来源数据，验证 get_from_request 优先级：post > cookie > get > server
$setProp = function ($obj, $prop, $value) {
    $ref = new ReflectionProperty(\yangzie\YZE_Request::class, $prop);
    $ref->setAccessible(true);
    $ref->setValue($obj, $value);
};
$setProp($req,'post',array('key'=>'post_val','only_post'=>1));
$setProp($req,'cookie',array('key'=>'cookie_val','only_cookie'=>2));
$setProp($req,'get',array('key'=>'get_val','only_get'=>3));
$setProp($req,'server',array('key'=>'server_val','only_server'=>4));
echo "req_key:",s($req->get_from_request('key')),"\n";
echo "req_only_post:",s($req->get_from_request('only_post')),"\n";
echo "req_only_cookie:",s($req->get_from_request('only_cookie')),"\n";
echo "req_only_get:",s($req->get_from_request('only_get')),"\n";
echo "req_only_server:",s($req->get_from_request('only_server')),"\n";
echo "req_default:",s($req->get_from_request('nope','dft')),"\n";
echo "from_post:",s($req->get_from_post('key')),"\n";
echo "from_cookie:",s($req->get_from_cookie('key')),"\n";
echo "from_get:",s($req->get_from_get('key')),"\n";
echo "from_server:",s($req->get_from_server('key')),"\n";

// 8. set_var/get_var
$req->set_var('a','b');
echo "var_set:",s($req->get_var('a')),"\n";
echo "var_miss:",s($req->get_var('no','dft')),"\n";

// 9. get_Scheme：默认 http，$_SERVER['HTTPS']=='on' 时 https
echo "scheme_default:",s($req->get_Scheme()),"\n";
$_SERVER['HTTPS']='on';
echo "scheme_https:",s($req->get_Scheme()),"\n";

// 10. the_referer_uri：完整地址 / 仅 path
$_SERVER['HTTP_REFERER']='http://example.com/foo/bar?x=1';
echo "referer_full:",s($req->the_referer_uri()),"\n";
echo "referer_path:",s($req->the_referer_uri(true)),"\n";

// 11. 移动端 / iOS / Android 判断
$_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15';
echo "ua_iphone:",s(array($req->is_mobile_client(),$req->is_In_IOS(),$req->is_In_Android())),"\n";
$_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36';
echo "ua_android:",s(array($req->is_mobile_client(),$req->is_In_IOS(),$req->is_In_Android())),"\n";
$_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
echo "ua_pc:",s(array($req->is_mobile_client(),$req->is_In_IOS(),$req->is_In_Android())),"\n";

// 12. get_Accept_Language
$_SERVER['HTTP_ACCEPT_LANGUAGE']='zh-CN,zh;q=0.9,en;q=0.8';
echo "accept_lang:",s($req->get_Accept_Language()),"\n";

// 13. format_gmdate 静态方法
echo "gmdate:",s(YZE_Request::format_gmdate('2026-08-28 10:00:00')),"\n";

// 14. 异常存取
$req->set_Exception(new Exception('boom'));
echo "exception:",s($req->get_exception()->getMessage()),"\n";
?>
--EXPECT--
post_datas:[]
get_datas:[]
uuid:true
req_method:null
get_var_miss:'dft'
ctx_get:'v1'
ctx_miss:null
post_name:'tom'
post_default:'dft'
post_after_set:{"name":"tom"}
req_key:'post_val'
req_only_post:1
req_only_cookie:2
req_only_get:3
req_only_server:4
req_default:'dft'
from_post:'post_val'
from_cookie:'cookie_val'
from_get:'get_val'
from_server:'server_val'
var_set:'b'
var_miss:'dft'
scheme_default:'http'
scheme_https:'https'
referer_full:'http://example.com/foo/bar?x=1'
referer_path:'/foo/bar'
ua_iphone:[1,1,0]
ua_android:[1,0,1]
ua_pc:[0,0,0]
accept_lang:'zh-cn'
gmdate:'Fri, 28 Aug 2026 02:00:00 GMT'
exception:'boom'
