<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 登录表单视图。
 *
 * ai@2026-09-22 改为使用框架默认的 tpl 布局（app/vendor/layouts/tpl.layout.php）：
 * html/head/body/title 由布局输出，本视图只负责内容部分；
 * yze_page_title 在布局中输出页面标题。
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据（来自 Signin_Controller）：
 *   - error        string  错误提示，登录失败时填充
 *   - username     string  已填的用户名（登录失败回显）
 *   - redirect_to  string  登录成功后跳转的地址
 */
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; padding:0; min-height:100vh;
           display:flex; align-items:center; justify-content:center; }
    .login-card { background:#fff; width:360px; padding:32px; border-radius:8px;
                  box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .login-card h1 { margin:0 0 24px; font-size:20px; }
    .login-card .field { margin-bottom:16px; }
    .login-card label { display:block; font-size:13px; color:#666; margin-bottom:6px; }
    .login-card input[type=text], .login-card input[type=password] {
        width:100%; box-sizing:border-box; padding:10px 12px;
        border:1px solid #dcdfe6; border-radius:4px; font-size:14px; }
    .login-card input[type=text]:focus, .login-card input[type=password]:focus {
        outline:none; border-color:#409eff; }
    .login-card button { width:100%; padding:10px 0; background:#409eff; color:#fff;
                         border:none; border-radius:4px; font-size:14px; cursor:pointer; }
    .login-card button:hover { background:#66b1ff; }
    .login-card .error { color:#f56c6c; background:#fef0f0; border:1px solid #fde2e2;
                         padding:8px 12px; border-radius:4px; margin-bottom:16px; font-size:13px; }
    .login-card .hint { color:#999; font-size:12px; margin-top:16px; text-align:center; }
</style>

<form class="login-card" method="post" action="/test/signin/dologin">
    <h1>演示登录</h1>

    <?php $error = (string)$this->get_Data('error'); if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="field">
        <label for="username">用户名</label>
        <input id="username" type="text" name="username" required
               value="<?= htmlspecialchars((string)$this->get_Data('username'), ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="username">
    </div>

    <div class="field">
        <label for="password">密码</label>
        <input id="password" type="password" name="password" required
               autocomplete="current-password">
    </div>

    <input type="hidden" name="redirect_to"
           value="<?= htmlspecialchars((string)$this->get_Data('redirect_to'), ENT_QUOTES, 'UTF-8') ?>">

    <button type="submit">登录</button>

    <div class="hint">演示账号：alice / bob　密码均为 secret</div>
</form>