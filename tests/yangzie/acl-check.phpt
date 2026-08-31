--TEST--
YZE_ACL 静态权限配置：deny/allow 精确匹配、deny 优先、通配符、ACO 层级、分组 ARO(/a/b/c)、多角色、输出缓冲
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_ACL;

// 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// 注入 ACO/ARO 静态权限表：与构造逻辑一致做 krsort 降序，保证更具体的 ACO 优先匹配
function inject_acos_aros($array){
    krsort($array);
    $newarr = array();
    foreach ($array as $aco=>$aros){
        if(is_array($aros['deny'] ?? null)) krsort($aros['deny']);
        if(is_array($aros['allow'] ?? null)) krsort($aros['allow']);
        $newarr[$aco] = $aros;
    }
    $rp = new ReflectionProperty('yangzie\YZE_ACL','acos_aros');
    $rp->setAccessible(true);
    $rp->setValue(YZE_ACL::get_instance(), $newarr);
    return YZE_ACL::get_instance();
}

// 反射调用私有方法
function call_private($obj, $method, $args=array()){
    $rm = new ReflectionMethod('yangzie\YZE_ACL',$method);
    $rm->setAccessible(true);
    return $rm->invokeArgs($obj, $args);
}

// 1. deny/allow 精确匹配与 deny 优先
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array("/blocked"), "allow"=>array("/admin"), "desc"=>""),
));
echo s($acl->check_byname("/admin", "/a/do")),"\n";    // allow 精确命中
echo s($acl->check_byname("/blocked", "/a/do")),"\n";  // deny 精确命中
echo s($acl->check_byname("/guest", "/a/do")),"\n";    // 均未命中，ARO 逐级到 / 仍无匹配
echo s($acl->check_byname("/guest", "/other/do")),"\n";// 未细分 ACO 落根配置 allow * 放行

// 同一 ARO 同时在 deny 与 allow 中：deny 优先
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array("/admin"), "allow"=>array("/admin"), "desc"=>""),
));
echo s($acl->check_byname("/admin", "/a/do")),"\n";    // deny 优先于 allow

// 2. 分组 ARO /a/b/c：子组继承父组 allow，父组 deny 约束子组
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/sale" => array("deny"=>array("/manager"), "allow"=>array("/employee"), "desc"=>""),
    "/sale/order" => array("deny"=>array(), "allow"=>array("/manager"), "desc"=>""),
));
echo s($acl->check_byname("/employee/x", "/sale/list")),"\n";          // 子组 /employee/x 继承 /employee 的 allow
echo s($acl->check_byname("/employee/x/y/z", "/sale/list")),"\n";      // 多级子组继承
echo s($acl->check_byname("/employee/x", "/sale/order/detail")),"\n"; // ACO 定位 /sale/order，employee 无权限
echo s($acl->check_byname("/manager/root", "/sale/order/detail")),"\n";// manager 子组有权限
echo s($acl->check_byname("/manager/root", "/sale/list")),"\n";       // 父组 deny 约束子组

// 3. 多角色数组：任一角色有权限即通过
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array(), "allow"=>array("/admin"), "desc"=>""),
));
echo s($acl->check_byname(array("/guest","/admin"), "/a/do")),"\n";  // 任一角色命中
echo s($acl->check_byname(array("/guest","/blocked"), "/a/do")),"\n";// 全部未命中
echo s($acl->check_byname(array(), "/a/do")),"\n";                  // 空角色数组

// 4. ACO 层级与前缀匹配：need_controll 定位最具体的 ACO
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array(), "allow"=>array("/admin"), "desc"=>""),
    "/a/b" => array("deny"=>array("*"), "allow"=>array(), "desc"=>""),
));
echo s($acl->check_byname("/admin", "/a/b/action")),"\n";  // 定位 /a/b，deny * 拒绝
echo s($acl->check_byname("/admin", "/a/do")),"\n";        // 定位 /a，allow /admin 通过
echo s($acl->check_byname("/admin", "/a/bc/do")),"\n";     // 前缀匹配命中 /a/b（无词边界）
echo s($acl->check_byname("/admin", "/zzz/do")),"\n";      // 未细分 ACO 落根 allow * 放行

// 5. ACO 正则支持（文档示例 /order/(post_?)add）
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/order/(post_?)add" => array("deny"=>array(), "allow"=>array("/seller"), "desc"=>""),
));
echo s($acl->check_byname("/seller", "/order/postadd")),"\n";    // 正则命中（post 变体）
echo s($acl->check_byname("/seller", "/order/post_add")),"\n";   // 正则命中（post_ 变体）
echo s($acl->check_byname("/buyer", "/order/postadd")),"\n";     // 无权限
echo s($acl->check_byname("/buyer", "/order/delete")),"\n";     // 未命中正则，落根 allow * 放行

// 6. need_controll / in_array 私有方法直测
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array(), "allow"=>array("/admin"), "desc"=>""),
    "/a/b" => array("deny"=>array("*"), "allow"=>array(), "desc"=>""),
));
echo s(call_private($acl,"need_controll",array("/a/b/c"))),"\n";   // 定位 /a/b
echo s(call_private($acl,"need_controll",array("/a/c"))),"\n";     // 定位 /a
echo s(call_private($acl,"need_controll",array("/zzz"))),"\n";     // 落根 /
echo s(call_private($acl,"need_controll",array("noslash"))),"\n";  // 无前导斜杠 NULL

echo s(call_private($acl,"in_array",array("/admin/sub",array("/admin")))),"\n";  // /admin/* 通配命中
echo s(call_private($acl,"in_array",array("/admin",array("/admin")))),"\n";      // 精确命中
echo s(call_private($acl,"in_array",array("/adminx",array("/admin")))),"\n";     // 需斜杠分隔，不命中
echo s(call_private($acl,"in_array",array("/a/b",array("*")))),"\n";             // * 全匹配
echo s(call_private($acl,"in_array",array("/a/b",array("/a/*")))),"\n";          // 显式 * 通配
echo s(call_private($acl,"in_array",array("",array("/a")))),"\n";               // 空 check 不匹配

// 7. begin/end_check_permission 输出缓冲：有权限输出，无权限丢弃
$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array(), "allow"=>array("/admin"), "desc"=>""),
));
$acl->begin_check_permission("/admin", "/a/do");
echo "visible\n";
$acl->end_check_permission("/admin", "/a/do");
$acl->begin_check_permission("/guest", "/a/do");
echo "hidden\n";
$acl->end_check_permission("/guest", "/a/do");
echo "after\n";

// 8. 空参数边界
echo s($acl->check_byname("", "/a/do")),"\n";   // 空 ARO 拒绝
echo s($acl->check_byname("/admin", "")),"\n";  // 空 ACO 不需控制，放行
?>
--EXPECT--
true
false
false
true
false
true
true
false
true
false
true
false
false
false
true
false
true
true
true
false
true
'/a/b'
'/a'
'/'
NULL
true
true
false
true
true
false
visible
after
false
true
