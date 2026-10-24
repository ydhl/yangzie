<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ① —— 默认命名约定。
 *
 * 本模板被 view/default_view action 自动命中，模板名"view-default_view"
 * 来自 View_Controller 去掉 _Controller 后缀 + 减号 + 方法名。
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据：
 *   - hit  string 命中的模板全路径（来自控制器，便于核对）
 *   - note string 控制器补充的说明
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
                background:#f0f9eb; color:#67c23a; font-family:Menlo, Consolas, monospace;
                margin-bottom:14px; }
    .v dl { display:grid; grid-template-columns:110px 1fr; gap:8px 16px; font-size:13px; margin:14px 0; }
    .v dt { color:#888; }
    .v dd { margin:0; font-family:Menlo, Consolas, monospace; color:#222; word-break:break-all; }
    .v .code { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
               line-height:1.7; color:#606266; white-space:pre-wrap; }
    .v .code .c { color:#888; }
    .v .code .k { color:#c45656; }
    .v a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
               text-decoration:none; font-size:13px; background:#909399; color:#fff; }
</style>

<div class="v">
    <h1>方式 ① 默认命名约定</h1>
    <span class="badge">view-default_view.tpl.php</span>

    <dl>
        <dt>action</dt>    <dd>View_Controller::default_view()</dd>
        <dt>命中模板</dt>  <dd><?= htmlspecialchars((string)$this->get_Data('hit'), ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>判定位置</dt>  <dd>yangzie/controller.php:80-87（get_Response）</dd>
    </dl>

    <div class="code"><span class="c">// View_Controller::default_view() 没有 return 值：</span>
<span class="k">public function</span> default_view() {
    <span class="v">$this</span>-&gt;set_View_Data(<span class="v">'hit'</span>, <span class="v">'view-default_view.tpl.php'</span>);
}
<span class="c">// handle_request() 看到 $response 为空，调用 get_Response()，</span>
<span class="c">// 因为 $this-&gt;view 还是空字符串，走到 controller.php:86：</span>
<span class="c">//   $tpl = str_replace("_Controller", "", "View_Controller") . "-" . "default_view"</span>
<span class="c">//     = "view-default_view"</span>
<span class="c">// 最终 require view_path() . "/view-default_view.tpl.php"</span>
    </div>

    <p style="color:#666;font-size:13px;line-height:1.7;margin-top:14px">
        <?= htmlspecialchars((string)$this->get_Data('note'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <a class="btn" href="/test/view">回视图选择演示首页</a>
</div>