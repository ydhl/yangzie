<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ③ —— 运行时选模板（full 版）。
 *
 * action 按 GET ?tpl=full 把 $this-&gt;view 设为 "view-runtime_select_full"，
 * 因此框架命中本文件而非 compact 版。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:center; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:680px; border-radius:8px;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .v h1 { margin:0 0 6px; font-size:22px; }
    .v .badge { display:inline-block; padding:3px 10px; border-radius:4px; font-size:11px;
                background:#ecf5ff; color:#409eff; font-family:Menlo, Consolas, monospace;
                margin-bottom:14px; }
    .v dl { display:grid; grid-template-columns:110px 1fr; gap:8px 16px; font-size:13px; margin:14px 0; }
    .v dt { color:#888; }
    .v dd { margin:0; font-family:Menlo, Consolas, monospace; color:#222; word-break:break-all; }
    .v .code { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
               line-height:1.7; color:#606266; white-space:pre-wrap; }
    .v .code .c { color:#888; }
    .v .code .k { color:#c45656; }
    .v .code .v { color:#228b22; }
    .v .panel { background:#f0f9eb; border:1px solid #e1f3d8; border-radius:6px;
                padding:14px 18px; margin:14px 0; font-size:13px; color:#67c23a; line-height:1.7; }
    .v .panel b { color:#222; }
    .v a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
               text-decoration:none; font-size:13px; margin-right:8px; }
    .v a.btn.primary { background:#409eff; color:#fff; }
    .v a.btn.muted   { background:#909399; color:#fff; }
    .v .row-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .v .row-actions .btn { background:#909399; color:#fff; }
    .v .row-actions .btn.active { background:#409eff; color:#fff; }
</style>

<div class="v">
    <h1>方式 ③ 运行时选模板 — full</h1>
    <span class="badge">view-runtime_select_full.tpl.php</span>

    <dl>
        <dt>action</dt>   <dd>View_Controller::runtime_select()</dd>
        <dt>GET 参数</dt> <dd>tpl=<b><?= htmlspecialchars((string)$this->get_Data('chosen'), ENT_QUOTES, 'UTF-8') ?></b></dd>
        <dt>命中模板</dt> <dd><?= htmlspecialchars((string)$this->get_Data('chosen') === 'full' ? 'view-runtime_select_full.tpl.php' : 'view-runtime_select_compact.tpl.php', ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>

    <div class="panel">
        ✓ 这是 <b>full 版</b>模板，展示较多调试信息（命中规则、当前 GET 参数、解释代码）。
        切到 <code>?tpl=compact</code> 看另一个模板 —— 同一份业务数据只换了呈现层。
    </div>

    <div class="code"><span class="c">// controller.php:83-87：$this-&gt;view 一旦非空就用它，覆盖默认 view-{action}</span>
<span class="k">public function</span> runtime_select() {
    <span class="v">$tpl</span> = <span class="v">$this</span>-&gt;request-&gt;get_from_get(<span class="v">'tpl'</span>, <span class="v">'full'</span>);
    <span class="k">if</span> (!in_array(<span class="v">$tpl</span>, [<span class="v">'full'</span>, <span class="v">'compact'</span>], <span class="k">true</span>)) <span class="v">$tpl</span> = <span class="v">'full'</span>;

    <span class="v">$this</span>-&gt;view = <span class="v">"view-runtime_select_{$tpl}"</span>;   <span class="c">// ★ 决定命中哪个模板</span>
    <span class="v">$this</span>-&gt;set_View_Data(<span class="v">'chosen'</span>, <span class="v">$tpl</span>);
    <span class="c">// 不 return → get_Response() 用上面设置的 $this-&gt;view 拼路径</span>
}
    </div>

    <p style="color:#666;font-size:13px;line-height:1.7;margin-top:14px">
        <?= htmlspecialchars((string)$this->get_Data('note'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <div class="row-actions">
        <a class="btn <?= $this->get_Data('chosen') === 'full' ? 'active' : '' ?>"    href="/test/view/runtime_select?tpl=full">tpl=full</a>
        <a class="btn <?= $this->get_Data('chosen') === 'compact' ? 'active' : '' ?>" href="/test/view/runtime_select?tpl=compact">tpl=compact</a>
        <a class="btn muted" href="/test/view">回演示首页</a>
    </div>
</div>