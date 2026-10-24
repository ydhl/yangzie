<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ② —— $this-&gt;view 属性覆盖。
 *
 * action 把 $this-&gt;view 设为 "view-property_override_alt"，于是
 * get_Response() 命中本文件而非默认的 view-property_override.tpl.php。
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
                background:#fdf6ec; color:#e6a23c; font-family:Menlo, Consolas, monospace;
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
    <h1>方式 ② $this-&gt;view 属性覆盖</h1>
    <span class="badge">view-property_override_alt.tpl.php</span>

    <dl>
        <dt>action</dt>  <dd>View_Controller::property_override()</dd>
        <dt>默认应命中</dt><dd>view-property_override.tpl.php（不存在也行）</dd>
        <dt>实际命中</dt><dd style="color:#e6a23c">view-property_override_alt.tpl.php（覆盖了）</dd>
    </dl>

    <div class="code"><span class="c">// controller.php:80-87 get_Response() 关键逻辑：</span>
<span class="k">if</span> (<span class="v">$this</span>-&gt;view) {                            <span class="c">// 非空 → 用属性里的名字</span>
    <span class="v">$tpl</span> = <span class="v">$this</span>-&gt;view;
} <span class="k">else</span> {                                       <span class="c">// 空 → 默认 view-{action} 命名</span>
    <span class="v">$tpl</span> = str_replace("_Controller", "", get_class($this)) . "-" . <span class="v">$method</span>;
}

<span class="c">// 本 action 把属性覆盖了：</span>
<span class="k">public function</span> property_override() {
    <span class="v">$this</span>-&gt;view = <span class="v">'view-property_override_alt'</span>;
    <span class="c">// 没 return → handle_request 调 get_Response()，走到 if 分支</span>
}
    </div>

    <p style="color:#666;font-size:13px;line-height:1.7;margin-top:14px">
        <?= htmlspecialchars((string)$this->get_Data('note'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <a class="btn" href="/test/view">回视图选择演示首页</a>
</div>