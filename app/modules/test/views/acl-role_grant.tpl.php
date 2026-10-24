<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/role_grant 视图 —— get_permissions($aro_name) 的 allow 演示。
 *
 * 能渲染到这里说明：role 模式下当前 ARO 命中了 get_permissions() 返回的角色级
 * allow；或非 role 模式下命中了静态 allow ["/admin"]。
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
    .r { background:#fff; width:620px; padding:32px; border-radius:8px; margin:auto 0;
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
        <?= $perm_mode === 'role'
            ? '命中了 <b>get_permissions()</b> 返回的角色级 allow —— 动态授权存数据库，随时增删无需改代码'
            : '命中了<b>静态</b> allow ["/admin"]（切到 /consumer 再开 role 模式可看到角色级 allow 覆盖效果）' ?>。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/role_grant</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>静态基线</dt>      <dd>allow ["/admin"] → /consumer 被拒</dd>
        <dt>角色级配置</dt>    <dd>get_permissions("/consumer") → allow ["/test/acl/role_grant", "/test/acl"]</dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:95-123  check_role_permission() 的动态分支</span>
<span class="k">if</span> (function_exists(<span class="v">"get_permissions"</span>)) {
    <span class="v">$perm</span> = get_permissions(<span class="v">$aroname</span>);          <span class="c">// 传角色名，返回该角色的 ACO deny/allow</span>
    <span class="c">// deny 先判、allow 后判，"*" 表示全部 —— 同静态表规则。</span>
    <span class="c">// ⚠ 两个和静态分支不同的行为：</span>
    <span class="c">//   1. 本分支完全不查 yze_get_acos_aros() —— 定义了 get_permissions 后，</span>
    <span class="c">//      静态表的角色判定整体失效，漏配的角色会被全拒；</span>
    <span class="c">//   2. 未命中时向上递归的是 ACO（acl.php:117-122 pop $aconame），</span>
    <span class="c">//      而静态分支递归的是 ARO（acl.php:148-153 pop $aroname）。</span>
    <span class="c">//      所以动态模式下 "/admin/exact" 必须是 get_permissions 的精确 key，</span>
    <span class="c">//      不能指望像静态表那样靠 "/admin" 向上继承。</span>
}
    </div>

    <a class="btn primary" href="/test/acl/dynamic">回动态权限演示</a>
    <a class="btn muted"   href="/test/acl">回 ACL 首页</a>
</div>
