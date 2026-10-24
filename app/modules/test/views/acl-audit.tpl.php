<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/audit 视图 —— ARO 向上匹配演示。
 *
 * 能渲染到这里说明当前 ARO 命中了 allow = ["/admin"] 这条**只配父级**的规则：
 * ARO 是 /admin/exact / /admin/normal 这种 /admin 的子级时，即便 allow 里
 * 没写它们，也能通过 in_array() 的 "/\*" 自动补齐或 check_role_permission()
 * 的 array_pop 向上递归命中。
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

    <div class="ok">✓ ACL 验证通过：allow 只配了父级 <code>/admin</code>，
        当前 ARO <code><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></code> 作为其子级命中了这条规则。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/audit</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>allow 配置</dt>    <dd>["/admin"] &nbsp;<span style="color:#888">← 没有任何子级角色</span></dd>
        <dt>deny 配置</dt>     <dd>["/admin/banned"]</dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// 本条配置：deny = ["/admin/banned"], allow = ["/admin"]</span>
<span class="c">//</span>
<span class="c">// yangzie/acl.php:187-200  in_array() —— 第一层：规则自动补 "/*"</span>
<span class="c">// allow 里的 "/admin" 不是 * 结尾，被补成 "/admin/*"（正则 ^/admin/.*$），</span>
<span class="c">// 当前 ARO "/admin/exact" 直接被这条正则匹配 → true，</span>
<span class="c">// 和 ACO 前缀匹配（reports 演示）走的是同一个 in_array。</span>
<span class="c">//</span>
<span class="c">// yangzie/acl.php:148-153  check_role_permission() —— 第二层：ARO 逐级向上</span>
<span class="c">// 若正则没命中（比如 allow 配 "/"，补成 "//*" 后匹配不上子级），</span>
<span class="c">// 会 array_pop 掉 ARO 尾段后重试：</span>
<span class="c">//   /admin/exact → /admin → /</span>
<span class="c">// 在哪一级命中 allow 就在哪一级放行；回到 "/" 仍无配置 → 拒绝。</span>
<span class="c">//</span>
<span class="c">// 反方向不成立：allow 配 "/admin/exact" 时，ARO "/admin" 不会继承子级权限。</span>
<span class="c">//</span>
<span class="c">// deny 先于 allow 判定（acl.php:130-142）："/admin/banned" 是 "/admin" 的子级，</span>
<span class="c">// 但命中 deny 后直接拒绝 —— 想排除某个子角色必须在 deny 里显式写它。</span>
    </div>

    <a class="btn primary" href="/test/acl">回到 ACL 演示首页</a>
    <a class="btn muted"   href="/test">回 test 模块首页</a>
</div>
