<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ④ —— 直接 return YZE_Simple_View。
 *
 * 控制器把模板路径拼好后传给 Simple_View；本文件没有用默认 view-return_full
 * 命名（碰巧同名而已），是控制器显式选定的结果 —— 这种方式下模板路径完全自由。
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
    .v a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
               text-decoration:none; font-size:13px; background:#909399; color:#fff; }
</style>

<div class="v">
    <h1>方式 ④ 直接 return YZE_Simple_View</h1>
    <span class="badge">view-return_full.tpl.php</span>

    <dl>
        <dt>action</dt>      <dd>View_Controller::return_full()</dd>
        <dt>构造时刻</dt>    <dd>date('H:i:s') on the controller side：<b><?= htmlspecialchars((string)$this->get_Data('built_at'), ENT_QUOTES, 'UTF-8') ?></b></dd>
        <dt>handle_request</dt><dd>看到非空 return → 不调 get_Response()，直接用返回的 view（controller.php:184-187）</dd>
    </dl>

    <div class="code"><span class="c">// view.php:452-457  YZE_Simple_View 构造：</span>
<span class="c">//   public function __construct($tpl_name, $data, YZE_Resource_Controller $controller, $format=null)</span>
<span class="c">//   $this-&gt;tpl = $tpl_name;  模板路径完全自定（甚至可以脱离模块 views 目录）</span>

<span class="k">public function</span> return_full() {
    <span class="v">$this</span>-&gt;set_View_Data(<span class="v">'built_at'</span>, date(<span class="v">'H:i:s'</span>));
    <span class="k">return</span> <span class="k">new</span> YZE_Simple_View(
        <span class="v">$this</span>-&gt;request-&gt;view_path() . <span class="v">'/view-return_full'</span>,
        <span class="v">$this</span>-&gt;view_data,
        <span class="v">$this</span>
    );
}
    </div>

    <p style="color:#666;font-size:13px;line-height:1.7;margin-top:14px">
        <?= htmlspecialchars((string)$this->get_Data('note'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <a class="btn" href="/test/view">回视图选择演示首页</a>
</div>