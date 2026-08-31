--TEST--
YZE_ACL 动态权限：get_user_permissions/get_permissions 优先级、分组 ARO(/a/b/c)、ACO 通配与静态失效
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_ACL;

// 动态权限模拟存储
$GLOBALS['test_user_perm'] = null;
$GLOBALS['test_role_perms'] = array();

// 模拟应用定义的动态权限函数：acl.php 通过 function_exists 检测到后即接管权限判定
function get_user_permissions(){ return $GLOBALS['test_user_perm']; }
function get_permissions($aro){ return isset($GLOBALS['test_role_perms'][$aro]) ? $GLOBALS['test_role_perms'][$aro] : null; }

// 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// 注入 ACO 定义表：need_controll 依赖它定位受控 ACO（deny/allow 由动态函数决定）
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

$acl = inject_acos_aros(array(
    "/" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/b" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/c" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/sale" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/sale/order" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/ops/*" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/a/b/c/d" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/x" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
    "/y" => array("deny"=>array(), "allow"=>array("*"), "desc"=>""),
));

// 1. get_user_permissions 用户动态权限（deny/allow 精确）
$GLOBALS['test_user_perm'] = array("deny"=>array("/a"), "allow"=>array("/b"));
echo s($acl->check_byname("/admin", "/a/x")),"\n";  // 用户 deny 命中
echo s($acl->check_byname("/admin", "/b/y")),"\n";  // 用户 allow 命中
echo s($acl->check_byname("/admin", "/c/z")),"\n";  // 用户未命中，角色无配置

// 2. 用户全允许/全拒绝（字符串 * 形式）
$GLOBALS['test_user_perm'] = array("allow"=>"*");
echo s($acl->check_byname("/guest", "/a/x")),"\n";  // 全允许
$GLOBALS['test_user_perm'] = array("deny"=>"*");
echo s($acl->check_byname("/guest", "/a/x")),"\n";  // 全拒绝
$GLOBALS['test_user_perm'] = array("deny"=>array(), "allow"=>array());
echo s($acl->check_byname("/admin", "/a/x")),"\n";  // 空配置 → 继续角色判定

// 3. 优先级：get_user_permissions > get_permissions > 静态配置
$GLOBALS['test_user_perm'] = array("deny"=>array("/a"));
$GLOBALS['test_role_perms'] = array("/admin" => array("allow"=>array("/a")));
echo s($acl->check_byname("/admin", "/a/x")),"\n";  // 用户 deny 优先于角色 allow

$GLOBALS['test_user_perm'] = null;
echo s($acl->check_byname("/admin", "/a/x")),"\n";  // 用户未配置，角色 allow 生效
$GLOBALS['test_role_perms'] = array();
echo s($acl->check_byname("/admin", "/a/x")),"\n";  // 角色无配置，静态被接管失效

// 4. get_permissions 角色动态权限 + ACO 逐级向上
$GLOBALS['test_role_perms'] = array(
    "/employee" => array("deny"=>array("/sale/order"), "allow"=>array("/sale")),
);
echo s($acl->check_byname("/employee", "/sale/order/detail")),"\n";  // 角色 deny 命中 ACO
echo s($acl->check_byname("/employee", "/sale/list")),"\n";          // 角色 allow 命中 ACO
echo s($acl->check_byname("/employee/x", "/sale/list")),"\n";        // 分组 ARO 未精确配置，ACO 逐级无匹配

// 5. ACO 定义支持 * 通配：定位 /ops/* 与角色权限精确匹配
$GLOBALS['test_role_perms'] = array(
    "/operator" => array("allow"=>array("/ops/*")),
);
echo s($acl->check_byname("/operator", "/ops/task/run")),"\n";  // 通配 ACO 命中

// 6. YZE_HOOK_GET_USER_ARO_NAME 分组 ARO /a/b/c 场景（钩子返回分组角色名）
$GLOBALS['test_user_perm'] = array("allow"=>array("/a/b/c/d"));
echo s($acl->check_byname("/a/b/c", "/a/b/c/d/action")),"\n";  // 分组 ARO 有权限
$GLOBALS['test_user_perm'] = array("allow"=>array("/x"));
echo s($acl->check_byname("/a/b/c", "/x/action")),"\n";        // 分组 ARO 对 /x 有权限
echo s($acl->check_byname("/a/b/c", "/y/action")),"\n";        // 分组 ARO 对 /y 无权限
?>
--EXPECT--
false
true
false
true
false
false
false
true
false
false
true
false
true
true
true
false
