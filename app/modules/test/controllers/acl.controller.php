<?php
namespace app\test;

use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Hook;

/**
 * ACL（ACO/ARO）控制演示控制器。
 *
 * 配合 app/__aros_acos__.php 中的静态配置和 app/hooks/auth.php 中可切换的 ARO，
 * 演示 yangzie 框架 ACL 的关键行为：
 *   1. ACO 精确匹配（/test/acl/admin_tools）
 *   2. ACO 前缀匹配（配置 /test/acl，访问 /test/acl/reports 时命中 in_array 自动补齐的 /*）
 *   3. ARO 向上匹配（/test/acl/audit：allow 只配 /admin，ARO /admin/exact 也能命中）
 *   4. deny 优先（/test/acl/settings 的 deny 名单先于 allow 生效）
 *   5. ignore 列表（/test/acl/public_info 完全跳过 ACL 检查）
 *   6. 动态权限（/test/acl/dynamic：定义 get_user_permissions / get_permissions 后
 *      优先于静态配置生效，见 test 模块 hooks/acl_dynamic.php）
 *
 * ARO 维度的演示（精确匹配 + 向上递归）由 4 种"演示角色"覆盖：
 *   - /admin/exact   → in_array 完全匹配；audit 里演示"只配父级 /admin 也能命中"
 *   - /admin/normal  → 精确匹配失败，向上递归 /admin 命中
 *   - /admin/banned  → 被 deny 名单拦截
 *   - /consumer      → 不在任何 allow 名单里，最终逐级回到 / 也无配置 → 拒绝
 *
 * 入口流程：
 *   GET  /test/acl              → 演示首页（角色切换器 + 5 个 action 入口）
 *   GET  /test/acl/switch_role?aro=... → 写入 session['demo_aro']
 *   GET  /test/acl/admin_tools  → 精确 ACO 演示
 *   GET  /test/acl/reports      → 前缀 ACO 演示
 *   GET  /test/acl/audit        → ARO 向上匹配演示（allow 只配 /admin）
 *   GET  /test/acl/settings     → deny 优先演示
 *   GET  /test/acl/public_info  → ignore 列表演示
 *   GET  /test/acl/dynamic      → 动态权限演示首页（模式切换器 + 3 个对照组入口）
 *   GET  /test/acl/set_perm_mode?mode=user|role|none → 切换动态权限演示模式（Cookie）
 *
 * 所有 action 都要登录（Test_Module::$auths['acl'] = '*'），
 * 但 switch_role 与 public_info 又被 yze_get_ignore_acos() 跳过 ACL。
 */
class Acl_Controller extends YZE_Resource_Controller {

    /**
     * 演示可用的 4 种 ARO 白名单。和 auth.php 的 GET_USER_ARO_NAME hook 共享这一组值。
     *
     * @var array<int, string>
     */
    public const DEMO_AROS = [
        '/admin/exact',
        '/admin/normal',
        '/admin/banned',
        '/consumer',
    ];

    /**
     * 从 session 里读"当前演示 ARO"，统一从 YZE_HOOK_GET_USER_ARO_NAME 拿，
     * 避免 controller 自己再 parse 一遍导致与框架判定逻辑分叉。
     */
    private function current_aro(): string {
        $aro = YZE_Hook::do_hook(YZE_HOOK_GET_USER_ARO_NAME);
        return is_string($aro) && $aro !== '' ? $aro : '/';
    }

    /**
     * 构造"框架在 request.php:509 生成的 ACO 字符串"。用 request 的方法避免自己拼路径时出错。
     */
    private function current_aco(string $action): string {
        $request = $this->request;
        return '/' . $request->module() . '/' . $request->controller_name(true) . '/' . $action;
    }

    /**
     * GET /test/acl —— 演示首页：4 个 action 链接 + 角色切换器 + 一段"匹配规则说明"。
     *
     * 本方法被 Test_Module::$auths['acl'] = '*' 保护，必须登录后访问。
     * 但它不在任何 ACO 白名单里 —— 这演示了"ACO 完全未配置时默认放行"
     * （yangzie/acl.php:266-270 的 need_controll 返回 null → return true）。
     *
     * @return void
     */
    public function index() {
        $loginUser = YZE_Hook::do_hook(YZE_HOOK_GET_LOGIN_USER);
        if (!$loginUser) {
            throw new \yangzie\YZE_RuntimeException(
                '登录态丢失：模块已声明 acl 需要登录，但 GET_LOGIN_USER 返回空'
            );
        }

        $this->set_view_data('yze_page_title', 'ACL 演示');
        $this->set_view_data('user',         $loginUser);
        $this->set_view_data('demo_aros',    self::DEMO_AROS);
        $this->set_view_data('current_aro',  $this->current_aro());
    }

    /**
     * GET /test/acl/switch_role?aro=<one of DEMO_AROS>
     *
     * 把选中的演示 ARO 写到 session，框架的 GET_USER_ARO_NAME hook 在下次请求时读它。
     * 写完跳转回演示首页便于继续点击。
     *
     * 注意：本 action 在 yze_get_ignore_acos() 里，**任何 ARO 都能访问**（包括 /consumer），
     * 所以一定要从白名单里选值，避免请求 ?aro=/anything 把 session 写脏。
     *
     * @return \yangzie\YZE_Redirect
     */
    public function switch_role() {
        $aro = (string)$this->request->get_from_get('aro', '');
        if (!in_array($aro, self::DEMO_AROS, true)) {
            $aro = '';
        }
        $_SESSION['demo_aro'] = $aro;

        $this->layout = '';
        return new \yangzie\YZE_Redirect('/test/acl', $this);
    }

    /**
     * GET /test/acl/admin_tools —— 精确 ACO 匹配演示。
     *
     * ACO = /test/acl/admin_tools：和 __aros_acos__.php 里配置的 key 完全相等，
     * 故 yangzie/acl.php:172-177 的 need_controll 用 ^... 正则前缀匹配后命中本条，
     * 然后开始 deny/allow 判定。
     *
     * 期望结果：
     *   - ARO /admin/exact   → 完全匹配 allow → 允许
     *   - ARO /admin/normal  → 精确不命中、向上递归到 /admin 命中 → 允许
     *   - ARO /admin/banned  → deny 直接命中 → 抛 YZE_Permission_Deny_Exception
     *   - ARO /consumer      → 任何层都不在 allow，逐级回到 / 也没配置 → 抛 deny
     *
     * @return void
     */
    public function admin_tools() {
        $this->set_view_data('yze_page_title', 'ACL - 管理工具（精确 ACO 匹配）');
        $this->set_view_data('rule_kind',     '精确 ACO');
        $this->set_view_data('aco',           $this->current_aco('admin_tools'));
        $this->set_view_data('matched_rule',
            '在 yze_get_acos_aros() 里配置的 /test/acl/admin_tools 完全命中'
        );
        $this->set_view_data('aro',           $this->current_aro());
    }

    /**
     * GET /test/acl/reports —— ACO 前缀匹配演示。
     *
     * ACO = /test/acl/reports：__aros_acos__.php 里**没有**这条 key。
     * 但因为有 /test/acl 这条更短前缀，need_controll() 用 ^/test/acl 正则匹配后命中它，
     * 然后 in_array 用 /* 自动补齐匹配 ARO。
     *
     * 与 admin_tools 的对照（同样 ARO 下行为不同）：
     *   - /admin/exact   → 放行（两者都命中 allow）
     *   - /admin/normal  → 放行（向上递归 /admin 命中）
     *   - /admin/banned  → **reports 放行**（/test/acl 的 allow 含 /admin/banned），
     *                      admin_tools 拒（admin_tools 的 deny 含 /admin/banned）
     *   - /consumer      → 放行（reports 用 /test/acl 的 allow，admin_tools 拒）
     *
     * 结论：ACO 越具体（key 越长），deny 优先级越高——deny 必须写在最具体的那个 ACO key 上
     * 才能拦下所有相关 action。
     *
     * @return void
     */
    public function reports() {
        $this->set_view_data('yze_page_title', 'ACL - 报表（ACO 前缀匹配）');
        $this->set_view_data('rule_kind',     'ACO 前缀匹配');
        $this->set_view_data('aco',           $this->current_aco('reports'));
        $this->set_view_data('matched_rule',
            '__aros_acos__.php 里只配了 /test/acl，但 in_array() 自动补 "/*" 后命中这条'
        );
        $this->set_view_data('aro',           $this->current_aro());
    }

    /**
     * GET /test/acl/audit —— ARO 向上匹配演示。
     *
     * __aros_acos__.php 里 /test/acl/audit 的 allow **只有 ["/admin"]**，
     * 没有任何子级角色。当前 ARO 是 /admin/exact / /admin/normal 时：
     *   - in_array()（acl.php:190-193）把 "/admin" 补成 "/admin/*"，
     *     正则 ^/admin/.*$ 直接命中 → 放行；
     *   - 若正则没命中（如 allow 配 "/" 这种补不出能匹配子级的正则），
     *     check_role_permission()（acl.php:148-153）还会 array_pop 去掉 ARO 尾段
     *     逐级向上重试：/admin/exact → /admin → /。
     *
     * 期望结果：
     *   - ARO /admin/exact   → allow 只配了父级 /admin 仍命中 → 允许（本演示主角）
     *   - ARO /admin/normal  → 同上 → 允许
     *   - ARO /admin/banned  → 虽是 /admin 子级，但 deny 里有 /admin/banned → 拒绝
     *                          （子级 deny 拦得住父级 allow：deny 在 allow 之前判定）
     *   - ARO /consumer      → 不在 /admin 子树下，逐级回到 / 也无配置 → 拒绝
     *
     * @return void
     */
    public function audit() {
        $this->set_view_data('yze_page_title', 'ACL - 审计日志（ARO 向上匹配）');
        $this->set_view_data('rule_kind',     'ARO 向上匹配');
        $this->set_view_data('aco',           $this->current_aco('audit'));
        $this->set_view_data('matched_rule',
            'allow 只配了父级 /admin；当前 ARO 是其子级，in_array 补 "/\*" 或 array_pop 递归后命中'
        );
        $this->set_view_data('aro',           $this->current_aro());
    }

    /**
     * 当前"动态权限演示模式"（Cookie demo_perm_mode，由 set_perm_mode 写入）。
     */
    private function current_perm_mode(): string {
        $mode = $_COOKIE['demo_perm_mode'] ?? '';
        return in_array($mode, ['user', 'role'], true) ? $mode : '';
    }

    /**
     * GET /test/acl/dynamic —— 动态权限演示首页。
     *
     * 展示 get_user_permissions() / get_permissions($aro_name) 两个动态权限函数
     * 与静态配置的三级优先级，提供模式切换器（写 Cookie demo_perm_mode）。
     * 三个对照组 action：user_grant / user_block / role_grant。
     */
    public function dynamic() {
        $this->set_view_data('yze_page_title', 'ACL - 动态权限（get_user_permissions / get_permissions）');
        $this->set_view_data('perm_mode',    $this->current_perm_mode());
        $this->set_view_data('aro',          $this->current_aro());
    }

    /**
     * GET /test/acl/set_perm_mode?mode=user|role|none
     *
     * 把演示模式写进 Cookie（而非 session，原因见模块 hooks/acl_dynamic.php 文件头：
     * 模块 hooks 文件在 session_start() 之前被加载），下一个请求的模块加载阶段
     * hooks/acl_dynamic.php 就会按它条件定义对应的全局函数。
     * 本 action 在 yze_get_ignore_acos() 里，任何模式都能访问。
     */
    public function set_perm_mode() {
        $mode = (string)$this->request->get_from_get('mode', '');
        if (!in_array($mode, ['user', 'role'], true)) {
            $mode = '';
        }
        if (!headers_sent()) {
            setcookie('demo_perm_mode', $mode, [
                'expires'  => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        $this->layout = '';
        return new \yangzie\YZE_Redirect('/test/acl/dynamic', $this);
    }

    /**
     * GET /test/acl/user_grant —— get_user_permissions 的 allow 演示。
     *
     * 静态基线：allow ["/admin"] → 默认模式下只有 /admin 子树角色能进，/consumer 被拒。
     * user 模式：get_user_permissions() 返回 allow ["/test/acl/user_grant"]，
     * 用户级 allow 不看 ARO —— 即便切到 /consumer（静态被拒的角色）也能进。
     * role 模式：用户函数未定义，判定交给 get_permissions()；它给演示角色配的
     * allow ["/test/acl"] 补 /* 后覆盖本页 → 同样放行（不是落回静态表被拒）。
     *
     * 能看到本页说明：当前模式下你的 ARO（或用户级授权）命中了 allow。
     */
    public function user_grant() {
        $this->set_view_data('yze_page_title', 'ACL - 用户级动态授权（get_user_permissions allow）');
        $this->set_view_data('rule_kind',     '用户级 allow（优先级最高）');
        $this->set_view_data('aco',           $this->current_aco('user_grant'));
        $this->set_view_data('matched_rule',
            'user 模式：get_user_permissions() 返回 allow ["/test/acl/user_grant"]，不看 ARO 直接放行'
        );
        $this->set_view_data('aro',           $this->current_aro());
        $this->set_view_data('perm_mode',     $this->current_perm_mode());
    }

    /**
     * GET /test/acl/user_block —— get_user_permissions 的 deny 演示。
     *
     * 静态基线：allow ["*"] → 默认模式下任何 ARO 都能进。
     * user 模式：get_user_permissions() 返回 deny ["/test/acl/user_block"]，
     * 用户级 deny 优先于一切（acl.php:64-69 先判 deny）→ 当前登录用户被拒，
     * 无论 ARO 是谁、静态 allow 多宽都救不回来。
     *
     * role 模式：get_permissions() 给演示角色配的 allow ["/test/acl"] 覆盖本页 →
     * 也能进（此时静态表完全不参与判定，放行依据不是静态 allow "*"）。
     *
     * 所以在 user 模式下本页**永远进不来**（会抛 Permission_Deny）——
     * 能看到本页说明当前不在 user 模式。
     */
    public function user_block() {
        $this->set_view_data('yze_page_title', 'ACL - 用户级动态拒绝（get_user_permissions deny）');
        $this->set_view_data('rule_kind',     '用户级 deny（先于一切 allow）');
        $this->set_view_data('aco',           $this->current_aco('user_block'));
        $this->set_view_data('matched_rule',
            '静态 allow "*" 本应人人放行；能看到本页说明当前不在 user 模式（user 模式下被用户级 deny 拒掉）'
        );
        $this->set_view_data('aro',           $this->current_aro());
        $this->set_view_data('perm_mode',     $this->current_perm_mode());
    }

    /**
     * GET /test/acl/role_grant —— get_permissions($aro_name) 的演示。
     *
     * 静态基线：allow ["/admin"] → 默认模式下 /consumer 被拒。
     * role 模式：get_permissions("/consumer") 返回 allow ["/test/acl/role_grant", ...]，
     * 角色级动态授权让 /consumer 也能进 —— 权限存数据库随时增删，无需改代码。
     *
     * 注意 role 模式下静态表的角色判定分支整体不执行（acl.php:95 function_exists
     * 后直接走动态分支），本演示中其他页能继续访问是因为 get_permissions 对每个
     * 演示角色都给了 allow ["/test/acl"]（in_array 补 /* 后等同于 /test/acl/*）。
     */
    public function role_grant() {
        $this->set_view_data('yze_page_title', 'ACL - 角色级动态授权（get_permissions allow）');
        $this->set_view_data('rule_kind',     '角色级 allow（接管静态表）');
        $this->set_view_data('aco',           $this->current_aco('role_grant'));
        $this->set_view_data('matched_rule',
            'role 模式：get_permissions("/consumer") 返回 allow ["/test/acl/role_grant"] → /consumer 放行'
        );
        $this->set_view_data('aro',           $this->current_aro());
        $this->set_view_data('perm_mode',     $this->current_perm_mode());
    }

    /**
     * GET /test/acl/settings —— deny 优先演示。
     *
     * 与 admin_tools / reports 共享 allow 配置（/admin/exact, /admin），
     * 但单独 deny /admin/banned。展示"deny 比 allow 优先"。
     *
     * 期望结果：
     *   - ARO /admin/exact   → 允许（allow 完全匹配）
     *   - ARO /admin/normal  → 允许（向上递归到 /admin 命中）
     *   - ARO /admin/banned  → **拒绝**（deny 优先于 allow）
     *   - ARO /consumer      → 拒绝（allow 不命中，逐级回退也无配置）
     *
     * @return void
     */
    public function settings() {
        $this->set_view_data('yze_page_title', 'ACL - 系统设置（精确 ACO + deny 优先）');
        $this->set_view_data('rule_kind',     '精确 ACO + deny 优先');
        $this->set_view_data('aco',           $this->current_aco('settings'));
        $this->set_view_data('matched_rule',
            '精确命中 /test/acl/settings；deny 在 allow 前判，/admin/banned 会被拒'
        );
        $this->set_view_data('aro',           $this->current_aro());
    }

    /**
     * GET /test/acl/public_info —— ignore 列表演示。
     *
     * 本 ACO 在 yze_get_ignore_acos() 里 → need_controll() 直接返回 null →
     * check_byname() 第 268-269 行 return true（放行）。
     *
     * 任何 ARO 都能进，包括 /consumer 和 /admin/banned。
     *
     * @return void
     */
    public function public_info() {
        $this->set_view_data('yze_page_title', 'ACL - 公开信息（ignore 列表）');
        $this->set_view_data('rule_kind',     'ignore 列表（跳过 ACL）');
        $this->set_view_data('aco',           $this->current_aco('public_info'));
        $this->set_view_data('matched_rule',
            '命中 yze_get_ignore_acos()，need_controll() 返回 null，框架直接放行'
        );
        $this->set_view_data('aro',           $this->current_aro());
    }
}