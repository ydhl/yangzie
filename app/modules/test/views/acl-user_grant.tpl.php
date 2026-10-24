<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/user_grant 视图 —— get_user_permissions() 的 allow 演示。
 *
 * 能渲染到这里说明三种模式各自放行：
 *   none  当前 ARO 命中静态 allow ["/admin"]
 *   user  用户级 allow ["/test/acl/user_grant"] 放行（不看 ARO）
 *   role  get_permissions() 给演示角色配的 allow ["/test/acl"] 补 /* 后覆盖本页
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

    <div class="ok">✓ ACL 验证通过：当前 ARO <code><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></code>
        <?php if ($perm_mode === 'user'): ?>
            命中了 <b>用户级</b> allow —— 权限挂在登录用户身上，与 ARO 无关
        <?php elseif ($perm_mode === 'role'): ?>
            命中了 <b>get_permissions()</b> 的 allow ["/test/acl"]（补 /* 后覆盖本页）—— 角色级动态配置已接管静态表
        <?php else: ?>
            命中了<b>静态</b> allow ["/admin"]（切到 /consumer 再开 user 模式可看到用户级 allow 覆盖效果）
        <?php endif; ?>。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/user_grant</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>静态基线</dt>      <dd>allow ["/admin"] → /consumer 被拒</dd>
        <dt>用户级配置</dt>    <dd>get_user_permissions() → allow ["/test/acl/user_grant"]</dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:272-275  check_byname()</span>
<span class="c">// function_exists("get_user_permissions") 为真时优先走用户级判定：</span>
<span class="k">if</span> (function_exists(<span class="v">"get_user_permissions"</span>)) {
    <span class="v">$check_rst</span> = <span class="v">$this</span>->check_user_permission(<span class="v">$aconame</span>);
    <span class="k">if</span> (<span class="v">$check_rst</span> !== -1) <span class="k">return</span> <span class="v">$check_rst</span>;   <span class="c">// 命中即返回，不再看角色/静态表</span>
}

<span class="c">// yangzie/acl.php:59-82  check_user_permission()</span>
<span class="c">// 注意 deny/allow 是 ACO 列表（与静态表"ACO → ARO 名单"的方向相反）；</span>
<span class="c">// in_array() 同样支持 "/test/acl" 补 "/*" 的前缀匹配；未命中返回 -1 落回角色判定。</span>
<span class="c">// 本函数没有 ARO/ACO 向上递归 —— 用户级配置要么精确/前缀命中，要么整体落回。</span>
    </div>

    <a class="btn primary" href="/test/acl/dynamic">回动态权限演示</a>
    <a class="btn muted"   href="/test/acl">回 ACL 首页</a>
</div>
