<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/public_info 视图 —— ignore 列表演示。
 *
 * 任何 ARO 都能进来（甚至 /consumer 和 /admin/banned），因为本 ACO 在
 * yze_get_ignore_acos() 里被显式排除：
 *
 *   need_controll() 第一段 foreach 就是 yze_get_ignore_acos()，
 *   preg_match("{^" } ... 后返回 null；然后 check_byname() 在 $aconame 为
 *   null 时直接 return true（acl.php:266-270）。
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
               background:#f0f9eb; color:#67c23a; font-size:12px; margin-bottom:18px; }
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
    .r a.danger { background:#f56c6c; color:#fff; }
    .r a.muted { background:#909399; color:#fff; }
</style>

<div class="r">
    <h1><?= htmlspecialchars((string)$this->get_Data('yze_page_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="rule"><?= htmlspecialchars($rule_kind, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="ok">✓ ACL 验证通过：本 ACO 在 ignore 列表里，框架直接放行，与 ARO 无关。</div>

    <dl>
        <dt>请求 URL</dt>      <dd>/test/acl/public_info</dd>
        <dt>生成 ACO</dt>      <dd><?= htmlspecialchars($aco, ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>当前 ARO</dt>      <dd><span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span></dd>
        <dt>命中规则</dt>      <dd><?= htmlspecialchars($matched_rule, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="expl">
<span class="c">// yangzie/acl.php:163-179  need_controll()</span>
<span class="c">// 第 1 段 foreach 走的是 yze_get_ignore_acos()，命中即返回 null：</span>
<span class="k">foreach</span> ((array)<span class="v">yze_get_ignore_acos()</span> <span class="k">as</span> <span class="v">$aco</span>) {
    <span class="k">if</span> (preg_match(<span class="v">"{^".strtr($aco, ['*'=>'.*'])."}i"</span>, <span class="v">$aconame</span>)) {
        <span class="k">return null</span>;                 <span class="c">// ← 这里 hit</span>
    }
}

<span class="c">// yangzie/acl.php:266-270  check_byname()</span>
<span class="v">$aconame</span> = <span class="v">$this</span>->need_controll(<span class="v">$aconame</span>);
<span class="k">if</span> (! <span class="v">$aconame</span>) <span class="k">return true</span>;     <span class="c">// ← ignore 命中后直接 return true</span>
    </div>

    <a class="btn danger" href="/test/acl/switch_role?aro=/consumer">
        切到 /consumer 重试（其它演示页会被拒，这里仍然能进）
    </a>
    <a class="btn primary" href="/test/acl">回到 ACL 演示首页</a>
    <a class="btn muted" href="/test">回 test 模块首页</a>
</div>