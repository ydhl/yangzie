<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 免登录入口视图。
 *
 * 演示要点：本页能渲染出来，说明 YZE_Request::need_auth() 在到达 action 前
 * 就因为 Test_Module::$no_auths['dashboard'] = '^public_' 命中而直接放行，
 * 没有触发 YZE_HOOK_GET_LOGIN_USER -> NEED_SIGNIN 链路。
 *
 * 对比 /test/dashboard：同一个控制器下的 index() 仍在 $auths 保护下，
 * 未登录访问会被拦下重定向。
 *
 * ai@2026-09-22 使用框架默认的 tpl 布局（app/vendor/layouts/tpl.layout.php）：
 * html/head/body/title 由布局输出，本视图只负责内容部分。
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据（来自 Dashboard_Controller::public_demo）：
 *   - user                       ?array 登录用户信息（来自 YZE_HOOK_GET_LOGIN_USER），未登录时为 null
 *   - protected_action_url        URL   对照用的"受保护入口"，未登录访问会被拦截
 *   - login_url / logout_url      URL   登录/退出入口
 */
?>
<?php
/** @var ?array $user */
$user            = $this->get_Data('user');
$protected_url   = (string)($this->get_Data('protected_action_url') ?: '/test/dashboard');
$login_url       = (string)($this->get_Data('login_url')            ?: '/test/signin/login');
$logout_url      = (string)($this->get_Data('logout_url')           ?: '/test/signin/logout');
$is_logged_in    = is_array($user) && !empty($user);
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:center; justify-content:center; }
    .public-card { background:#fff; width:520px; padding:32px; border-radius:8px;
                   box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .public-card h1 { margin:0 0 8px; font-size:22px; }
    .public-card .sub { color:#888; font-size:13px; margin-bottom:18px; }
    .public-card .state { padding:12px 14px; border-radius:6px; font-size:14px;
                          margin-bottom:18px; line-height:1.6; }
    .public-card .state.ok    { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8; }
    .public-card .state.warn  { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8; }
    .public-card .state .who  { color:#222; font-weight:600; }
    .public-card .explain { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                            padding:14px 16px; font-size:12px; color:#606266;
                            font-family:Menlo, Consolas, monospace; line-height:1.7;
                            margin-bottom:18px; }
    .public-card .explain .k { color:#c45656; }
    .public-card .explain .v { color:#222; }
    .public-card .explain .c { color:#888; }
    .public-card .actions { display:flex; flex-wrap:wrap; gap:8px; }
    .public-card a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
                         text-decoration:none; font-size:13px; }
    .public-card a.primary  { background:#409eff; color:#fff; }
    .public-card a.warning  { background:#e6a23c; color:#fff; }
    .public-card a.success  { background:#67c23a; color:#fff; }
    .public-card a.muted    { background:#909399; color:#fff; }
    .public-card a.btn:hover { filter:brightness(1.08); }
</style>

<div class="public-card">
    <h1>免登录入口</h1>
    <div class="sub">/test/dashboard/public_demo · 同控制器下被 no_auths 豁免的 action</div>

    <?php if ($is_logged_in): ?>
        <div class="state ok">
            ✓ 你当前是<b>已登录</b>访问本页（用户名：<span class="who">
                <?= htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </span>）。本视图无论登录与否都能渲染，这是关键。
        </div>
    <?php else: ?>
        <div class="state warn">
            ⚠ 你当前是<b>未登录</b>访问本页 —— 这正是本演示要证明的：
            即便 SESSION 中没有登录用户，框架也没有抛 NEED_SIGNIN 重定向，
            因为 public_demo() 在 $no_auths 里被显式放行。
        </div>
    <?php endif; ?>

    <div class="explain">
<span class="c">// Test_Module::__config__.php</span>
<span class="k">public</span> <span class="k">array</span> <span class="v">$auths</span> = [
    <span class="v">'dashboard'</span> => <span class="v">'*'</span>,         <span class="c">// 默认全部要登录</span>
];
<span class="k">public</span> <span class="k">array</span> <span class="v">$no_auths</span> = [
    <span class="v">'dashboard'</span> => <span class="v">'^public_'</span>,  <span class="c">// 但 public_ 前缀的方法豁免</span>
];
<span class="c">// yangzie/request.php:630-643 的 need_auth()：</span>
<span class="c">// no_auths 先判断，命中即放行，所以 dashboard 控制器下</span>
<span class="c">// 其它 action（如 index()）仍受保护，仅 public_demo() 等免登录。</span>
    </div>

    <div class="actions">
        <a class="btn primary" href="<?= htmlspecialchars($protected_url, ENT_QUOTES, 'UTF-8') ?>">
            对照：受保护的 /test/dashboard
        </a>
        <?php if ($is_logged_in): ?>
            <a class="btn muted" href="<?= htmlspecialchars($logout_url, ENT_QUOTES, 'UTF-8') ?>">
                退出登录后重试
            </a>
        <?php else: ?>
            <a class="btn success" href="<?= htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8') ?>">
                去登录
            </a>
        <?php endif; ?>
        <a class="btn warning" href="/test">回到演示首页</a>
    </div>
</div>