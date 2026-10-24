<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ③ —— 运行时选模板（compact 版）。
 *
 * 命中条件：?tpl=compact。和 full 版共用一份控制器代码，只换模板。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:center; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:380px; border-radius:8px;
         box-shadow:0 4px 16px rgba(0,0,0,.06); text-align:center; }
    .v h1 { margin:0 0 6px; font-size:18px; }
    .v .badge { display:inline-block; padding:2px 8px; border-radius:3px; font-size:11px;
                background:#f0f9eb; color:#67c23a; font-family:Menlo, Consolas, monospace;
                margin-bottom:10px; }
    .v p { font-size:13px; color:#666; line-height:1.7; margin:8px 0; }
    .v .row-actions { display:flex; gap:8px; justify-content:center; margin-top:14px; }
    .v a.btn { display:inline-block; padding:6px 14px; border-radius:4px;
               text-decoration:none; font-size:12px; }
    .v a.btn.active { background:#409eff; color:#fff; }
    .v a.btn.muted   { background:#909399; color:#fff; }
</style>

<div class="v">
    <span class="badge">view-runtime_select_compact.tpl.php</span>
    <h1>compact 模式</h1>
    <p>tpl=compact；同一 action 同一数据，换了一版极简呈现。</p>
    <div class="row-actions">
        <a class="btn <?= $this->get_Data('chosen') === 'full' ? 'active' : '' ?>"    href="/test/view/runtime_select?tpl=full">full</a>
        <a class="btn <?= $this->get_Data('chosen') === 'compact' ? 'active' : '' ?>" href="/test/view/runtime_select?tpl=compact">compact</a>
        <a class="btn muted" href="/test/view">回首页</a>
    </div>
</div>