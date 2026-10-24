<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 受保护页视图：展示当前登录用户的信息。
 *
 * ai@2026-09-22 改为使用框架默认的 tpl 布局（app/vendor/layouts/tpl.layout.php）：
 * html/head/body/title 由布局输出，本视图只负责内容部分。
 *
 * 这里能渲染出来，说明：Test_Module::$auths 已生效；GET_LOGIN_USER hook
 * 从 session 读到了 SET_LOGIN_USER 写入的内容。
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据（来自 Dashboard_Controller）：
 *   - user             array 登录用户信息（来自 YZE_HOOK_GET_LOGIN_USER）
 *       - username    string
 *       - name        string
 *       - login_at    string
 */
?>
<?php $user = $this->get_Data('user') ?: []; ?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:center; justify-content:center; }
    .dashboard-card { background:#fff; width:480px; padding:32px; border-radius:8px;
                      box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .dashboard-card h1 { margin:0 0 16px; font-size:22px; }
    .dashboard-card .ok { color:#67c23a; font-size:13px; margin-bottom:16px; }
    .dashboard-card .row { display:flex; padding:10px 0; border-bottom:1px solid #f0f0f0; }
    .dashboard-card .row:last-child { border-bottom:none; }
    .dashboard-card .key { width:120px; color:#888; font-size:13px; }
    .dashboard-card .val { flex:1; color:#222; font-size:14px; word-break:break-all; }
    .dashboard-card .actions { margin-top:24px; }
    .dashboard-card a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
                            text-decoration:none; font-size:13px; margin-right:8px; }
    .dashboard-card a.primary { background:#409eff; color:#fff; }
    .dashboard-card a.danger  { background:#f56c6c; color:#fff; }
    .dashboard-card a.primary:hover { background:#66b1ff; }
    .dashboard-card a.danger:hover  { background:#f89898; }
</style>

<div class="dashboard-card">
    <h1>Dashboard</h1>
    <div class="ok">✓ 已登录，正在访问受保护资源</div>

    <div class="row">
        <div class="key">用户名</div>
        <div class="val"><?= htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="row">
        <div class="key">显示名</div>
        <div class="val"><?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="row">
        <div class="key">登录时间</div>
        <div class="val"><?= htmlspecialchars((string)($user['login_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="actions">
        <a class="btn primary" href="/test">回到首页</a>
        <a class="btn danger"  href="/test/signin/logout">退出登录</a>
    </div>
</div>