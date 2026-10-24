<?php
namespace app\test;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_RuntimeException;
use yangzie\YZE_View_Adapter;
use yangzie\YZE_Hook;

/**
 * test 模块演示功能入口列表。
 *
 * ai@2026-09-22 演示首页：把模块内所有演示功能集中列在这里，
 * 每个演示附上说明和对应链接。新增演示时在 $demos 数组加一项即可。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>

<?php
/**
 * 演示功能清单
 *
 * 结构：name 功能名 / desc 说明 / url 入口地址 / need_login 是否需要登录才能看到入口
 *
 * @var array<int, array{name:string, desc:string, url:string, need_login:bool}>
 */
$demos = [
    [
        'name'       => '登录演示',
        'desc'       => '完整的登录认证链路：未登录访问受保护页 → 自动重定向到登录页 → '
                      . '提交账号密码（YZE_HOOK_SET_LOGIN_USER 写 session）→ 跳回受保护页。'
                      . '演示账号 alice / bob，密码均为 secret。',
        'url'        => '/test/signin/login',
        'need_login' => false,
    ],
    [
        'name'       => '受保护页（Dashboard）',
        'desc'       => 'Test_Module::$auths 中声明为需要登录的资源（dashboard => *）。'
                      . '未登录访问会被框架拦截并重定向到登录页；登录后展示当前登录用户信息，'
                      . '并提供退出登录入口。',
        'url'        => '/test/dashboard',
        'need_login' => true,
    ],
    [
        'name'       => '免登录入口（同控制器局部豁免）',
        'desc'       => '虽然 dashboard 控制器在 $auths 里被设为需要登录（dashboard => *），'
                      . '但 $no_auths[\'dashboard\'] = \'^public_\' 让 public_ 前缀的方法'
                      . '被精确豁免。访问 /test/dashboard/public_demo 无论登录与否都能直接渲染，'
                      . '这就是"auths 锁住整个控制器、no_auths 局部豁免个别 action"的标准用法。',
        'url'        => '/test/dashboard/public_demo',
        'need_login' => false,
    ],
    [
        'name'       => '退出登录',
        'desc'       => '清除 session 中的登录信息（$_SESSION[\'admin\']），'
                      . '让 YZE_HOOK_GET_LOGIN_USER 重新返回空，受保护页恢复拦截状态。',
        'url'        => '/test/signin/logout',
        'need_login' => false,
    ],
    [
        'name'       => '自定义路由：动态 path 参数 + 静态 args',
        'desc'       => '模块 __config__.php 的 routers 配置演示。访问 '
                      . '/test/demo/order/1234 会匹配到 \'test/demo/order/(?P<id>\\d+)\'，'
                      . '其中 id 是 uri 命名正则捕获的动态 path 参数，source/channel 是配置里写死的 '
                      . 'args 静态参数，两者都用 $request->get_var() 获取。'
                      . '地址里的 1234 可以随意换成其它数字。',
        'url'        => '/test/demo/order/1234',
        'need_login' => false,
    ],
    [
        'name'       => '自定义路由：纯静态 args',
        'desc'       => '对应 \'test/demo/about\'，路由没有动态参数，'
                      . 'name/version 完全来自配置的 args，通过 $request->get_var() 获取。',
        'url'        => '/test/demo/about',
        'need_login' => false,
    ],
    [
        'name'       => 'ACL 控制演示（ACO/ARO 匹配规则）',
        'desc'       => 'app/__aros_acos__.php 里配置静态 ACO 权限表，结合可切换的 ARO 演示：'
                      . '① ACO 完全匹配；② ACO 前缀匹配（配 /test/acl，访问 /test/acl/reports）；'
                      . '③ ARO 完全匹配与向上递归匹配（/admin/normal → /admin）；'
                      . '④ deny 优先于 allow；⑤ yze_get_ignore_acos() 跳过 ACL。',
        'url'        => '/test/acl',
        'need_login' => true,
    ],
    [
        'name'       => '视图选择演示',
        'desc'       => '控制器里怎么指定 / 构造响应视图的 6 种方式：'
                      . '① 默认命名约定（view-{action}.tpl.php）；② $this-&gt;view 属性覆盖默认命名；'
                      . '③ 运行时按 GET 参数挑模板（同一 action 多模板）；④ 直接 return YZE_Simple_View 路径完全自定；'
                      . '⑤ return YZE_Notpl_View 跳过模板输出裸字符串；⑥ return YZE_JSON_View 输出 JSON。'
                      . '核心代码见 yangzie/controller.php:71-98 的 get_Response()。',
        'url'        => '/test/view',
        'need_login' => false,
    ],
];

// 读取当前登录态，用于给需要登录的演示项打标
// 注意：YZE_HOOK_* 常量是 hooks.php 里 define() 定义的全局常量（不带 yangzie\ 命名空间），
// 所以这里用 \YZE_HOOK_GET_LOGIN_USER 全局限定的写法
$loginUser = YZE_Hook::do_hook(\YZE_HOOK_GET_LOGIN_USER);
?>
<style>
    .demo-list { max-width:760px; margin:24px auto; padding:0 16px;
                 font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .demo-list h1 { font-size:22px; margin-bottom:4px; }
    .demo-list .sub { color:#888; font-size:13px; margin-bottom:24px; }
    .demo-list .item { background:#fff; border:1px solid #ebeef5; border-radius:8px;
                       padding:20px 24px; margin-bottom:16px;
                       box-shadow:0 2px 8px rgba(0,0,0,.04); }
    .demo-list .item h2 { font-size:16px; margin:0 0 8px; display:flex; align-items:center; gap:8px; }
    .demo-list .item p  { margin:0 0 12px; color:#666; font-size:13px; line-height:1.7; }
    .demo-list a.btn   { display:inline-block; padding:7px 16px; border-radius:4px;
                         background:#409eff; color:#fff; text-decoration:none; font-size:13px; }
    .demo-list a.btn:hover { background:#66b1ff; }
    .demo-list .tag { display:inline-block; font-size:11px; padding:2px 8px;
                      border-radius:10px; vertical-align:middle; }
    .demo-list .tag.login  { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8; }
    .demo-list .tag.nologin{ background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8; }
    .demo-list .status { color:#999; font-size:12px; margin-left:auto; }
</style>

<div class="demo-list">
    <h1>test 模块演示</h1>
    <div class="sub">
        当前登录状态：
        <?php if ($loginUser): ?>
            已登录（<?= htmlspecialchars((string)($loginUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>）
        <?php else: ?>
            未登录
        <?php endif; ?>
    </div>

    <?php foreach ($demos as $demo): ?>
        <div class="item">
            <h2>
                <?= htmlspecialchars($demo['name'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($demo['need_login']): ?>
                    <span class="tag login">需登录</span>
                <?php else: ?>
                    <span class="tag nologin">免登录</span>
                <?php endif; ?>
                <?php if ($demo['need_login'] && !$loginUser): ?>
                    <span class="status">未登录时访问会被重定向到登录页</span>
                <?php endif; ?>
            </h2>
            <p><?= htmlspecialchars($demo['desc'], ENT_QUOTES, 'UTF-8') ?></p>
            <a class="btn" href="<?= htmlspecialchars($demo['url'], ENT_QUOTES, 'UTF-8') ?>">进入演示</a>
        </div>
    <?php endforeach; ?>
</div>