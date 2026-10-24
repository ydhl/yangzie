<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/reports 视图 —— ACO 前缀匹配演示。
 *
 * 关键差异：__aros_acos__.php 里**没有** /test/acl/reports 这条 key，
 * 但配了 /test/acl 更短前缀。acl.php:172-177 用 ^... 正则匹配，
 * /test/acl/reports 能被 ^/test/acl 命中 → 返回 '/test/acl'，
 * 然后进入 in_array() 用 /* 自动补齐来匹配 ARO。
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
               background:#fdf6ec; color:#e6a23c; font-size:12px; margin-bottom:18px; }
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

    <div class="ok">✓ ACL 验证通过：当前 ARO 经 ACO 前缀匹配命中 <code>/test/acl</code> 规则。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/reports</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:172-177  need_controll()</span>
<span class="c">// acos_aros 里候选前缀按 krsort 排序：</span>
<span class="c">//   /test/acl/settings   — 比 reports 长但前缀不匹配 reports，跳过</span>
<span class="c">//   /test/acl/admin_tools — 同上跳过</span>
<span class="c">//   /test/acl            — preg_match {^/test/acl} 命中 reports，命中并返回 "/test/acl"</span>

<span class="c">// ACL 表里 /test/acl 的 deny=[]，allow=[/admin/exact, /admin, /admin/banned, /consumer]，</span>
<span class="c">// 4 种 ARO 都能命中 allow → 全部放行。</span>
<span class="c">// 这跟 admin_tools 的"deny 含 /admin/banned → 拒"形成对照：</span>
<span class="c">//   - 同一 ARO（/admin/banned）→ admin_tools 拒，reports 放行。</span>
<span class="c">//   - 原因：更长前缀的 deny 会"挖洞"，更短前缀的 allow 会被这个洞卡住。</span>
<span class="c">//   - 想对所有 action 统一拒 /admin/banned，要么在每条 ACO 都写 deny，要么用 ignore。</span>
    </div>

    <a class="btn primary" href="/test/acl">回到 ACL 演示首页</a>
    <a class="btn muted"   href="/test">回 test 模块首页</a>
</div>