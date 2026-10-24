<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/settings 视图 —— 精确 ACO + deny 优先演示。
 *
 * 注意：本视图只在当前 ARO 不在 deny 名单时才能渲染。如果当前 ARO = /admin/banned，
 * 会被框架直接抛 YZE_Permission_Deny_Exception，根本走不到这里。
 *
 * 对照方式：
 *   1. 切到 /admin/exact / /admin/normal → 能进；说明 allow 在生效。
 *   2. 切到 /admin/banned                → 进不来；说明 deny 优先于 allow。
 *   3. 切到 /consumer                    → 也进不来；说明 allow 不命中、向上回 / 也无 allow → 拒绝。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<?php
$aco           = (string)$this->get_Data('aco');
$rule_kind     = (string)$this->get_Data('rule_kind');
$matched_rule  = (string)$this->get_Data('matched_rule');
$aro           = (string)$this->get_Data('aro');
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .r { background:#fff; width:560px; padding:32px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .r h1 { margin:0 0 6px; font-size:22px; }
    .r .rule { display:inline-block; padding:4px 12px; border-radius:4px;
               background:#fef0f0; color:#f56c6c; font-size:12px; margin-bottom:18px; }
    .r .ok { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8;
             padding:14px 18px; border-radius:6px; font-size:14px; margin-bottom:18px; }
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
    .r a.muted { background:#909399; color:#fff; }
    .r a.danger { background:#f56c6c; color:#fff; }
</style>

<div class="r">
    <h1><?= htmlspecialchars((string)$this->get_Data('yze_page_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="rule"><?= htmlspecialchars($rule_kind, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="ok">✓ ACL 验证通过：当前 ARO 不在 deny 名单，且在 allow 中命中。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/settings</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:130-134  check_role_permission()</span>
<span class="c">// 第 1 步先判 deny：</span>
<span class="k">if</span> (in_array(<span class="v">$aroname</span>, <span class="v">['/admin/banned']</span>)) <span class="k">return false</span>;
<span class="c">//   → /admin/banned 直接拒绝，allow 里的 /admin 也救不回来</span>

<span class="c">// 第 2 步才判 allow：</span>
<span class="k">if</span> (in_array(<span class="v">$aroname</span>, <span class="v">['/admin/exact', '/admin']</span>)) <span class="k">return true</span>;
<span class="c">//   → /admin/exact 精确匹配，/admin/normal 向上递归到 /admin</span>

<span class="c">// /consumer：allow 不命中、向上递归 / 也无配置 → 进入子层递归再回到 / → false</span>
    </div>

    <a class="btn danger" href="/test/acl/switch_role?aro=/admin/banned">
        切到 /admin/banned 验证 deny 优先
    </a>
    <a class="btn primary" href="/test/acl">回到 ACL 演示首页</a>
    <a class="btn muted" href="/test">回 test 模块首页</a>
</div>