<?php

namespace app;

/**
 * 返回指定ACO的功能描述
 * @param $aconame
 * @return mixed|string
 */
function yze_get_aco_desc($aconame) {
    foreach ( ( array ) yze_get_acos_aros () as $aco => $desc ) {
        if (preg_match ( "{^" . $aco . "}", $aconame )) {
            return @$desc ['desc'];
        }
    }
    return '';
}

/**
 * 返回在yze_get_acos_aros中定义的，但完全不需要权限控制的acl；支持正则
 * @return array
 */
function yze_get_ignore_acos() {
    return array();
}

/**
 * 返回系统中所有需要鉴权的ACO，格式为 <u>/模块名/控制器名/方法名</u>
 *
 * <strong>注意ACO不是URL</strong>，ACO表示具体的请求处理入口，也就是Controller中的action。<br/>
 * ARO指用户的角色分类，系统自行定义（通过Hook YZE_HOOK_GET_USER_ARO_NAME 返回），比如/admin，/admin/normal，/consumer等<br/><br/>
 *
 * 这里定义的ACO在访问时会先鉴权（必须登录且在allow中），只有有权限的用户才会正常处理请求，
 * 其他情况抛出异常YZE_Permission_Deny_Exception异常。<br/><br/>
 *
 * <ul>
 * <li>deny是黑名单，表示明确拒绝的ARO；</li>
 * <li>allow是白名单，表示允许的ARO；</li>
 * <li>deny的优先级高于allow；</li>
 * </ul>
 *
 * ACO支持正则，比如/order/(post_?)add,
 * 忽略的部分默认为*，比如<u>/模块名/控制器名</u> 等同于 <u>/模块名/控制器名/\*</u><br/><br/>
 *
 * 这里是静态的权限配置，实际系统可能会针对用户或者角色进行动态的权限配置，针对用户的动态权限配置可以把配置存储在数据库中，然后通过
 * get_user_permissions()方法返回当前登录者的动态权限；通过get_permissions($aro_name)返回指定角色的动态权限。
 * 当定义后get_user_permissions或get_permissions。
 *
 * 优先级先后顺序为get_user_permissions → get_permissions → yze_get_acos_aros配置
 *
 * 谁先定义了deny或allow就以他的结果为准。
 *
 * @see get_user_permissions
 * @see 通过get_permissions
 * @return array[]
 */
function yze_get_acos_aros() {
    $array = [
        "/" => [
            "deny" => [],//黑名单
            "allow" => ["*"], //白名单
            "desc" => ""//功能说明
        ]
    ];

	return $array;
}
?>
