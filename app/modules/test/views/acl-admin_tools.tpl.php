<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/admin_tools 视图 —— 精确 ACO 匹配演示。
 *
 * 能渲染到这里说明：
 *   1. $auths['acl'] = '*' 让用户必须先登录（未登录会被重定向到 /test/signin/login）；
 *   2. 当前 ARO 不在 /test/acl/admin_tools 的 deny 里，并且能在 allow 中找到。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<?php
/** @var string $aco */
/** @var string $rule_kind */
/** @var string $matched_rule */
/** @var string $aro */
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
               background:#ecf5ff; color:#409eff; font-size:12px; margin-bottom:18px; }
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
    .r a.primary:hover { background:#66b1ff; }
    .r a.muted { background:#909399; color:#fff; }
</style>

<div class="r">
    <h1><?= htmlspecialchars((string)$this->get_Data('yze_page_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="rule"><?= htmlspecialchars($rule_kind, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="ok">✓ ACL 验证通过：当前 ARO 拥有 <code>/test/acl/admin_tools</code> 的访问权限。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/admin_tools</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:172-177  need_controll()</span>
<span class="c">// 用 ^... 正则匹配 acos_aros 的 key，krsort 保证最长前缀先匹配</span>
<span class="c">// 这里 "/test/acl/admin_tools" 是最长的 key，所以直接返回它。</span>

<span class="c">// yangzie/acl.php:130-146  check_role_permission()</span>
<span class="c">// 1. 先判 deny = in_array($aroname, ['/admin/banned']) → /admin/banned 直接 false</span>
<span class="c">// 2. 再判 allow = in_array($aroname, ['/admin/exact', '/admin'])</span>
<span class="c">//    - /admin/exact 完全相等 → true（精确匹配）</span>
<span class="c">//    - /admin/normal 精确不命中 → 向上递归到 /admin → 命中 allow → true</span>
<span class="c">// 3. ARO 在 allow 里逐级向上递归：</span>
<span class="c">//      /admin/normal → /admin → /</span>
<span class="c">//    命中 /admin 即返回 true。</span>
    </div>

    <a class="btn primary" href="/test/acl">回到 ACL 演示首页</a>
    <a class="btn muted"   href="/test">回 test 模块首页</a>
</div>