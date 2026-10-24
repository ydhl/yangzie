<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/user_block 视图 —— get_user_permissions() 的 deny 演示。
 *
 * 能渲染到这里说明当前不在 user 模式：user 模式下用户级 deny ["/test/acl/user_block"]
 * 优先于静态 allow "*"，对任何 ARO 都拒绝（抛 Permission_Deny），本页进不来。
 * 放行原因分两种：none 模式走静态 allow "*"；role 模式走 get_permissions() 配的
 * allow ["/test/acl"]（此时静态表不参与判定）。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<?php
/** @var string $aco */
/** @var string $rule_kind */
/** @var string $matched_rule */
/** @var string $aro */
/** @var string $perm_mode */
$aco          = (string)$this->get_Data('aco');
$rule_kind    = (string)$this->get_Data('rule_kind');
$matched_rule = (string)$this->get_Data('matched_rule');
$aro          = (string)$this->get_Data('aro');
$perm_mode    = (string)($this->get_Data('perm_mode') ?: '');
$mode_names   = ['' => 'none（纯静态）', 'user' => 'user', 'role' => 'role'];
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .r { background:#fff; width:600px; padding:32px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .r h1 { margin:0 0 6px; font-size:22px; }
    .r .rule { display:inline-block; padding:4px 12px; border-radius:4px;
               background:#ecf5ff; color:#409eff; font-size:12px; margin:0 6px 18px 0; }
    .r .mode { display:inline-block; padding:4px 12px; border-radius:4px;
               background:#f0f9eb; color:#67c23a; font-size:12px; margin:0 0 18px 6px; }
    .r .ok { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8;
             padding:14px 18px; border-radius:6px; font-size:14px; margin-bottom:18px; line-height:1.7; }
    .r dl { display:grid; grid-template-columns:120px 1fr; gap:8px 16px; font-size:13px; }
    .r dt { color:#888; }
    .r dd { margin:0; font-family:Menlo, Consolas, monospace; color:#222; word-break:break-all; }
    .r dd .aro { padding:2px 10px; border-radius:3px; background:#f0f9eb; color:#67c23a; }
    .r .expl { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; color:#606266; line-height:1.7;
               margin:18px 0; font-family:Menlo, Consolas, monospace;
               white-space:pre-wrap; }
    .r .expl .c { color:#888; }
    .r .expl .k { color:#c45656; }
    .r .expl .v { color:#228b22; }
    .r a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
                text-decoration:none; font-size:13px; margin-right:8px; }
    .r a.primary { background:#409eff; color:#fff; }
    .r a.primary:hover { background:#66b1ff; }
    .r a.muted { background:#909399; color:#fff; }
</style>

<div class="r">
    <h1><?= htmlspecialchars((string)$this->get_Data('yze_page_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="rule"><?= htmlspecialchars($rule_kind, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="mode">当前模式：<?= htmlspecialchars($mode_names[$perm_mode] ?? $perm_mode, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="ok">✓ 你能看到本页，说明当前<b>不在 user 模式</b>——
        <?php if ($perm_mode === 'role'): ?>
            角色级动态配置生效：get_permissions() 给演示角色配的 allow ["/test/acl"] 补 /* 后覆盖本页
            （<b>不是</b>走静态 allow "*"，role 模式下静态表不参与判定）
        <?php else: ?>
            静态 allow "*" 对所有 ARO 放行
        <?php endif; ?>。<br>
        切到 user 模式再来，本页会抛 Permission_Deny：用户级 deny 连静态 allow "*" 都拦得住，
        且拒绝的是"当前登录用户"本人，换任何角色都进不来。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/user_block</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>静态基线</dt>      <dd>allow ["*"] → 任何 ARO 都放行</dd>
        <dt>用户级配置</dt>    <dd>get_user_permissions() → deny ["/test/acl/user_block"]</dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:64-69  check_user_permission() —— deny 先于 allow</span>
<span class="k">if</span> (is_array(<span class="v">$perm</span>[<span class="v">"deny"</span>] ?? null)) {
    <span class="v">$denys</span> = <span class="v">$this</span>->in_array(<span class="v">$aconame</span>, <span class="v">$perm</span>[<span class="v">"deny"</span>]);
    <span class="k">if</span> (<span class="v">$denys</span>) <span class="k">return false</span>;      <span class="c">// 命中用户级 deny → 直接拒绝</span>
}
<span class="c">// 后面才判 allow —— 所以"用户级 deny + 静态 allow *"的组合里 deny 赢。</span>
<span class="c">// 这和静态表"deny 优先于 allow"（acl.php:130-142）是同一条规则在两个层面的体现。</span>
    </div>

    <a class="btn primary" href="/test/acl/set_perm_mode?mode=user">切到 user 模式再试（本页将被拒）</a>
    <a class="btn muted"   href="/test/acl/dynamic">回动态权限演示</a>
</div>
