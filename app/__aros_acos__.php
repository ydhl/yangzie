<?php

namespace app;

/**
 * 返回指定ACO的功能描述
 * @param string $aconame
 * @return mixed|string
 */
function yze_get_aco_desc(string $aconame) {
    foreach ( ( array ) yze_get_acos_aros () as $aco => $desc ) {
        if (preg_match ( "{^" . $aco . "}", $aconame )) {
            return $desc['desc'] ?: $aconame;
        }
    }
    return $aconame;
}

/**
 * 返回在yze_get_acos_aros中定义的，但完全不需要权限控制的acl；支持正则
 *
 * ai@2026-09-22 ACL 演示：把演示用的入口放进忽略列表
 *   - /test/acl/public_info —— 演示"命中 ignore → 任意 ARO 直接放行"
 *   - /test/acl/switch_role —— 角色切换器本身不应受 ACL 拦截，否则切换演示角色会被
 *     卡死成"必须有 X 权限才能切到 Y 权限"。
 *   - /test/acl/set_perm_mode —— 动态权限模式切换器同理，任何模式下都必须能访问。
 *
 * @return array
 */
function yze_get_ignore_acos() {
    return array(
        "/test/acl/public_info",
        "/test/acl/switch_role",
        "/test/acl/set_perm_mode",
    );
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
        ],

        // ai@2026-09-22 演示 1：精确 ACO 匹配
        //
        // 仅当请求的 ACO 与 "/test/acl/admin_tools" 字面相等时才命中本条（见
        // yangzie/acl.php:172-177 的 need_controll 用 ^... 正则前缀匹配，但
        // 前面没有更长的前缀配置，所以实际上就退化成"精确匹配 admin_tools 这一个 action"）。
        // 下面 /test/acl 这条前缀规则**不会**被 /test/acl/admin_tools 命中
        // （krsort 已经把更长的 /test/acl/admin_tools 排到前面去匹配了，命中后即返回）。
        //
        // 本条 allow 同时演示两种 ARO 命中：
        //   - /admin/exact   → in_array 完全匹配直接命中
        //   - /admin         → 当前 ARO 向上递归匹配（如 /admin/normal 先匹配失败、再回到 /admin 命中）
        "/test/acl/admin_tools" => [
            "deny"  => ["/admin/banned"],
            "allow" => ["/admin/exact", "/admin"],
            "desc"  => "管理工具（精确 ACO 完全匹配）",
        ],

        // ai@2026-09-22 演示 2：ACO 前缀匹配
        //
        // 这里只写了 "/test/acl"，没有写方法名。按 yangzie/acl.php:190-193 的 in_array 逻辑
        // 会自动补 "/*"，相当于 "/test/acl/*"，因此访问 /test/acl/reports 这种没单独配置
        // 的 action 时，会由本条兜底管控。
        //
        // 注意它**不会**拦截 /test/acl/admin_tools / settings —— 因为上面那两条更长的前缀
        // 会先命中，它们走各自的 deny/allow，本条不参与。
        //
        // allow 里多带 /admin/banned 和 /consumer 是为了让 /test/acl/index（演示首页）任何 ARO 都能进，
        // 否则用户切到 /admin/banned / /consumer 后连演示首页都进不去，会陷入"想切角色但被 ACL 卡死"。
        "/test/acl" => [
            "deny"  => [],
            "allow" => ["/admin/exact", "/admin", "/admin/banned", "/consumer", "/"],
            "desc"  => "ACL 演示（ACO 前缀匹配，演示 in_array 的 /* 自动补齐）",
        ],

        // ai@2026-09-22 演示 3：ARO 向上匹配（配置父级 /admin，命中子级 /admin/exact）
        //
        // 注意 allow 里**只有 /admin**，没有 /admin/exact —— 这是和演示 1 最大的区别。
        // 当前 ARO 是 /admin/exact 时按两层机制命中：
        //   a) in_array()（acl.php:190-193）把 allow 里的 "/admin" 自动补成 "/admin/*"，
        //      正则 ^/admin/.*$ 直接匹配 /admin/exact —— 和 ACO 前缀匹配是同一个函数；
        //   b) 即使 a) 没命中（比如 allow 里是 "/" 这种补 "/*" 后也匹配不上子级的写法），
        //      check_role_permission()（acl.php:148-153）还会 array_pop 去掉 ARO 尾段，
        //      /admin/exact → /admin → / 逐级向上重试，命中 /admin 即放行。
        //
        // 反过来不成立：allow 配 /admin/exact 时，ARO /admin **不会**向下继承子级权限。
        //
        // deny 里的 /admin/banned 说明"子级 deny 拦得住父级 allow"：
        // deny 在 allow 之前判定（acl.php:130-142），且 /admin/banned 是 /admin 的子级，
        // 父级被 allow 不代表子级全放行 —— 必须显式 deny 才能排除个别子角色。
        "/test/acl/audit" => [
            "deny"  => ["/admin/banned"],
            "allow" => ["/admin"],
            "desc"  => "审计日志（ARO 向上匹配：只配 /admin，命中 /admin/exact）",
        ],

        // ai@2026-09-22 演示 4：deny 优先
        //
        // 即便 allow 里写了 /admin 和 /admin/exact，deny 中的 /admin/banned 仍优先，
        // 所以 /admin/banned 用户访问 /test/acl/settings 时被拒。
        "/test/acl/settings" => [
            "deny"  => ["/admin/banned"],
            "allow" => ["/admin/exact", "/admin"],
            "desc"  => "系统设置（精确 ACO + deny 优先）",
        ],

        // ai@2026-09-22 演示 5：动态权限函数的对照组（get_user_permissions / get_permissions）
        //
        // 这三条是"动态权限未开启"（默认）时的基线行为；开启动态权限后由
        // test 模块 hooks/acl_dynamic.php 里条件定义的两个全局函数覆盖判定：
        //   - user_grant / role_grant：静态只 allow /admin → 默认模式下 /consumer 被拒；
        //     开启 user 模式（用户级 allow）或 role 模式（角色级 allow）后 /consumer 反而能进。
        //   - user_block：静态 allow "*" → 默认模式下人人能进；
        //     开启 user 模式后，用户级 deny 优先于一切，当前登录用户被整个拒掉。
        //
        // 注意 need_controll() 仍以这里的静态表定位"哪些 ACO 受控"，
        // 动态函数只接管 deny/allow 判定，所以三条必须出现在静态表里。
        "/test/acl/user_grant" => [
            "deny"  => [],
            "allow" => ["/admin"],
            "desc"  => "用户级动态授权（get_user_permissions allow 的对照组）",
        ],
        "/test/acl/user_block" => [
            "deny"  => [],
            "allow" => ["*"],
            "desc"  => "用户级动态拒绝（get_user_permissions deny 的对照组）",
        ],
        "/test/acl/role_grant" => [
            "deny"  => [],
            "allow" => ["/admin"],
            "desc"  => "角色级动态授权（get_permissions allow 的对照组）",
        ],
    ];

	return $array;
}
?>
