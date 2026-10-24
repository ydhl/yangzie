<?php
namespace app\test;
/**
 * 模块视图组件 —— 用户信息卡片。
 *
 * 放在模块 views（app/modules/test/views）中，供本模块的视图/控制器通过
 * YZE_Simple_View 包含；第一个参数指向"模块 views 目录 + 组件名"：
 *
 *     $view = new YZE_Simple_View($this->request->view_path() . "/user-card", $data, $controller);
 *     $html = $view->get_output();   // 只要结果字符串
 *     // 或 $view->output();         // 直接输出到响应
 *
 * 要跨模块 / 系统级重用时，把本文件拷贝到 app/vendor/views（YZE_APP_VIEWS_INC），
 * 并把第一个参数换成 YZE_APP_VIEWS_INC . "user-card" 即可（yangzie.md:743）。
 *
 * 组件本身是最"传统"的 php 模板，不需要为被重用做任何额外改造；
 * 数据通过 $this->get_data() 获取（YZE_View_Adapter 注入的 $this），
 * 视图格式按请求上下文自动推断（找不到对应格式文件时回落 tpl）。
 *
 * 数据约定（方式一的弊端：调用方没有代码提示，只能看这里的注释或双方约定）：
 *   - user : ['username' => 登录名, 'name' => 姓名, 'email' => 邮箱]
 *   - tag  : string 角色标签文案（空字符串则不显示标签）
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
$user     = $this->get_data('user') ?: [];
$username = (string)($user['username'] ?? '');
$name     = (string)($user['name'] ?? '');
$email    = (string)($user['email'] ?? '');
$tag      = (string)($this->get_data('tag') ?? '');

$src = $username !== '' ? $username : '?';
$initial = function_exists('mb_substr')
    ? strtoupper(mb_substr($src, 0, 1))
    : strtoupper(substr($src, 0, 1));
?>
<style>
    .ucard { display:flex; align-items:center; gap:14px; background:#fafbfc;
             border:1px solid #ebeef5; border-radius:8px; padding:14px 18px; margin:0 0 10px; }
    .ucard .avatar { width:44px; height:44px; border-radius:50%; background:#409eff; color:#fff;
                     display:flex; align-items:center; justify-content:center; font-size:18px;
                     font-weight:600; flex:none; }
    .ucard .info { flex:1; min-width:0; }
    .ucard .info .uname { font-size:14px; font-weight:600; color:#222; }
    .ucard .info .uname small { color:#888; font-weight:400; margin-left:6px; }
    .ucard .info .mail { font-size:12px; color:#888; font-family:Menlo, Consolas, monospace;
                         overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ucard .tag { flex:none; font-size:11px; padding:3px 10px; border-radius:10px;
                  background:#ecf5ff; color:#409eff; border:1px solid #d9ecff; }
</style>
<div class="ucard">
    <div class="avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="info">
        <div class="uname"><?= htmlspecialchars($name !== '' ? $name : $username, ENT_QUOTES, 'UTF-8') ?>
            <small>@<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <div class="mail"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if ($tag !== ''): ?>
        <span class="tag"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>
