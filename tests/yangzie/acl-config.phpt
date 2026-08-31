--TEST--
app/__aros_acos__.php 权限配置函数：yze_get_acos_aros/yze_get_ignore_acos/yze_get_aco_desc 及默认 ACL 放行
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

// 1. 默认 ACO/ARO 权限表：仅配置根 "/"，allow 白名单为 "*"（默认全放行）
echo s(\app\yze_get_acos_aros()),"\n";

// 2. 默认忽略列表为空
echo s(\app\yze_get_ignore_acos()),"\n";

// 3. ACO 描述：默认 "/" 的 desc 为空字符串，任意 ACO 前缀命中根配置返回 ''
echo s(\app\yze_get_aco_desc("/")),"\n";
echo s(\app\yze_get_aco_desc("/module/controller/action")),"\n";

// 4. 默认配置下 ACL 行为：根 allow *，任意 ARO 对任意 ACO 放行
$acl = YZE_ACL::get_instance();
echo s($acl->check_byname("/admin", "/module/controller/action")),"\n";
echo s($acl->check_byname("/admin/normal", "/x/y/z")),"\n";
echo s($acl->check_byname("/", "/x/y/z")),"\n";

// 5. 单例：多次获取返回同一实例
echo s(YZE_ACL::get_instance() === $acl),"\n";
?>
--EXPECT--
{"/":{"deny":[],"allow":["*"],"desc":""}}
[]
''
''
true
true
true
true
