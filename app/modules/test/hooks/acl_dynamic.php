<?php
/**
 * 动态权限演示（test 模块 hooks 目录）
 *
 * 本文件在模块加载阶段被框架自动 require（router.php:104 的
 * YZE_Hook::include_hooks），按 Cookie demo_perm_mode 定义动态权限函数：
 *
 *   none（默认） 不定义任何函数              → 走静态配置 __aros_acos__.php
 *   user         定义 get_user_permissions() → 用户级 deny/allow 优先
 *   role         定义 get_permissions($aro)  → 角色级 deny/allow 接管静态表
 *
 * 两个"为什么"（各一句）：
 *   1. 函数定义放这里：ACL 检查发生在 startup.php:131 的 auth()，早于任何
 *      hook 触发点，函数必须在请求进来时就存在；
 *   2. 模式只能存 Cookie：同一原因，此时 session_start() 还没执行。
 *
 * 注意：本文件不能声明 namespace —— get_user_permissions / get_permissions
 * 必须是全局函数，acl.php 的 function_exists() 才检测得到。
 */

$demo_perm_mode = $_COOKIE['demo_perm_mode'] ?? '';

if ($demo_perm_mode === 'user' && !function_exists('get_user_permissions')) {
    /**
     * 当前登录用户的动态权限（deny/allow 都是 ACO 列表）
     *
     * 演示两个方向：
     *   - allow /test/acl/user_grant：静态配置只 allow /admin，但用户级 allow
     *     不看 ARO —— 切到 /consumer 也能进（权限挂在"用户"身上，和角色无关）。
     *   - deny /test/acl/user_block：静态配置 allow "*" 人人放行，但用户级
     *     deny 优先于一切（acl.php:64-69 先判 deny），当前登录用户被整个拒掉。
     */
    function get_user_permissions() {
        return [
            'deny'  => ['/test/acl/user_block'],
            'allow' => ['/test/acl/user_grant'],
        ];
    }
}

if ($demo_perm_mode === 'role' && !function_exists('get_permissions')) {
    /**
     * 指定角色（ARO）的动态权限（deny/allow 都是 ACO 列表）
     *
     * 演示主角是 '/consumer'：静态配置里 role_grant 只 allow /admin，/consumer 被拒；
     * 这条动态 allow 让 /consumer 也能进。
     *
     * 注意两个框架行为（acl.php:95-123 动态分支）：
     *   1. 与静态分支不同，这里**不做 ARO 向上递归**（静态分支才 array_pop ARO），
     *      而是对 ACO 向上递归 —— 所以每个演示角色都必须有精确 key，漏配即拒绝；
     *   2. 静态表在本分支完全不参与，allow '/test/acl' 是为了让其他演示页
     *      在 role 模式下仍可正常导航（in_array 会把它补成 '/test/acl/*'）。
     */
    function get_permissions($aro_name) {
        $maps = [
            '/admin/exact'  => ['allow' => ['/test/acl']],
            '/admin/normal' => ['allow' => ['/test/acl']],
            '/admin/banned' => [
                'deny'  => ['/test/acl/admin_tools', '/test/acl/settings'],
                'allow' => ['/test/acl'],
            ],
            '/consumer'     => ['allow' => ['/test/acl/role_grant', '/test/acl']],
            '/'             => ['allow' => ['/test/acl']],
        ];
        return $maps[$aro_name] ?? null;
    }
}
