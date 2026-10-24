<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;
use \yangzie\YZE_Simple_View;

/**
 * 响应格式 ⑰ · tpl 视图（默认渲染，同时是解释页）。
 *
 * 这是 URI 没有任何后缀时 / 末尾为 .tpl 时框架选中的视图 —— 也就是
 * "PC 端默认响应格式"（yangzie.md:891）。本视图既呈现一个真实的产品卡片
 * （tpl 格式下的内容长什么样），又把另外两种格式（json / jpg）的输出直接
 * 嵌进页里对比。
 *
 * @var YZE_View_Adapter $this
 *
 * 视图数据：
 *   - product  array 控制器 set_view_data 的同一份数据
 */
$product = (array)($this->get_data('product') ?: []);

// 拿到 json 视图的 get_output，嵌进 B-2 区做"现场对比"
// view 里访问 controller 的属性要通过公开 getter：get_Request()（protected $request）
$json_view = new YZE_Simple_View(
    $this->get_controller()->get_Request()->view_path() . "/view-formats_demo",
    $this->get_datas(),
    $this->get_controller(),
    'json'
);
$json_output = $json_view->get_output();
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; color:#222; }
    .v { background:#fff; width:960px; margin:24px auto;
         border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .v .sec { padding:22px 28px; }
    .v .sec + .sec { border-top:1px solid #ebeef5; }
    .v h1 { margin:0 0 6px; font-size:22px; }
    .v h2 { margin:0 0 12px; font-size:15px; color:#444; }
    .v h3 { margin:0 0 8px; font-size:13px; color:#606266; }
    .v .sub { color:#888; font-size:13px; line-height:1.7; }
    .v .note { color:#666; font-size:12px; line-height:1.8; margin:12px 0 0; }
    .v .code { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
               line-height:1.7; color:#606266; white-space:pre-wrap; margin-top:10px; }
    .v .code .c { color:#888; }
    .v .code .k { color:#c45656; }
    .v .code .s { color:#228b22; }
    .v .code .v { color:#333; }
    .v a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
               text-decoration:none; font-size:13px; background:#409eff; color:#fff;
               margin-right:8px; }
    .v a.btn.gray { background:#909399; }
    .v a.btn.alt  { background:#67c23a; }
    .v a.btn.warn { background:#e6a23c; }

    .grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:10px; }
    .grid .panel { border:1px solid #ebeef5; border-radius:6px; overflow:hidden; }
    .grid .panel .ph { background:#fafbfc; padding:8px 12px; border-bottom:1px solid #ebeef5;
                       font-size:12px; color:#606266; }
    .grid .panel .pb { padding:14px; }

    .pcard { border:1px solid #ebeef5; border-radius:6px; padding:14px 18px; background:#fafbfc; }
    .pcard h3 { margin:0 0 6px; font-size:16px; }
    .pcard .pmeta { color:#888; font-size:12px; }
    .pcard .pprice { color:#f56c6c; font-size:18px; font-weight:600; margin:6px 0; }

    pre.json-preview { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                        padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
                        line-height:1.7; color:#303133; white-space:pre-wrap; margin:0; }

    .jpg-preview { display:block; max-width:100%; border:1px solid #ebeef5;
                   border-radius:6px; background:#fafafa; }
</style>

<div class="v">
    <div class="sec">
        <h1>响应格式演示 · 方式⑰（tpl / json / jpg）</h1>
        <div class="sub">
            docs/yangzie.md:878-885 —— 同一个 Action，三种响应格式，三种内容：
            URI 末尾的后缀 <code>.json</code> / <code>.jpg</code> 让框架挑出对应的视图文件
            （<code>view-formats_demo.json.php</code> / <code>view-formats_demo.jpg.php</code>），
            而 controller 里 set_view_data 的同一份数据在三种视图里都能用。
            没后缀时走默认的 <code>view-formats_demo.tpl.php</code>（PC 端默认响应格式）。
        </div>
    </div>

    <div class="sec">
        <h2>A. tpl 格式（默认 / PC 端） —— 当前这一页就是它</h2>
        <div class="pcard">
            <h3><?= htmlspecialchars((string)($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="pmeta">编号 <?= htmlspecialchars((string)($product['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                · 分类 <?= htmlspecialchars((string)($product['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                · 库存 <?= (int)($product['stock'] ?? 0) ?></div>
            <div class="pprice">¥<?= number_format((float)($product['price'] ?? 0), 2) ?></div>
        </div>
        <p class="note">直接访问 <code>/test/view/formats_demo</code> 或 <code>/test/view/formats_demo.tpl</code> 命中此视图。</p>
    </div>

    <div class="sec">
        <h2>B. 同一份数据：json / jpg 两种现场输出</h2>
        <div class="grid">
            <div class="panel">
                <div class="ph">B-1 json 格式 —— view-formats_demo.json.php get_output() 嵌入</div>
                <div class="pb">
                    <pre class="json-preview"><?= htmlspecialchars($json_output, ENT_QUOTES, 'UTF-8') ?></pre>
                </div>
            </div>
            <div class="panel">
                <div class="ph">B-2 jpg 格式 —— &lt;img src="/test/view/formats_demo.jpg"&gt; 嵌入</div>
                <div class="pb">
                    <img class="jpg-preview" src="/test/view/formats_demo.jpg" alt="jpg view">
                    <p class="note" style="margin:8px 0 0;">
                        本图由 view-formats_demo.jpg.php 用 <strong>GD 动态生成</strong>，
                        内容与 JSON 的同一份 <code>$product</code> 数据一一对应（name / id / category / stock / price）。
                        环境缺 php-gd 时自动回落到 <code>docs/images/uml.jpg</code>。
                    </p>
                </div>
            </div>
        </div>
        <p class="note">
            两个面板展示的都是真实响应 —— B-1 是从 <code>view-formats_demo.json.php</code> 现场
            get_output 出来的字符串（同一份 <code>$product</code> 数据）；B-2 是浏览器去
            <code>/test/view/formats_demo.jpg</code> 真实加载的 JPEG（框架按 URI 后缀挑了
            <code>view-formats_demo.jpg.php</code>，再由 <code>jpg.layout.php</code> 设
            <code>Content-Type: image/jpeg</code>）。
        </p>
    </div>

    <div class="sec">
        <h2>C. 三种视图对应的"物理文件"</h2>
        <div class="code"><span class="c">// View_Controller::formats_demo() —— 一份数据，三种格式</span>
<span class="v">$this</span>-&gt;set_view_data(<span class="s">'product'</span>, [
    <span class="s">'id'</span>=&gt;<span class="s">'P-1001'</span>, <span class="s">'name'</span>=&gt;<span class="s">'iPhone Air 保护壳'</span>,
    <span class="s">'price'</span>=&gt;<span class="s">199.0</span>, <span class="s">'category'</span>=&gt;<span class="s">'accessory'</span>, <span class="s">'stock'</span>=&gt;<span class="s">42</span>
]);

<span class="c">// app/modules/test/views/view-formats_demo.tpl.php   —— PC 端默认格式（本视图）</span>
<span class="c">// app/modules/test/views/view-formats_demo.json.php —— URI 末尾 .json 时命中</span>
<span class="c">// app/modules/test/views/view-formats_demo.jpg.php  —— URI 末尾 .jpg 时命中（GD 动态生成）</span>

<span class="c">// app/vendor/layouts/jpg.layout.php —— jpg 专属布局：只做 header + echo content_of_view</span>
<span class="v">header</span>(<span class="s">'Content-Type: image/jpeg'</span>);
<span class="k">echo</span> <span class="v">$this</span>-&gt;content_of_view();</div>
    </div>

    <div class="sec">
        <h2>D. 框架怎么按 URI 后缀选视图（view.php:496-507）</h2>
        <div class="code">URI: /test/view/formats_demo.json
   ↓ request.php:731  preg_match 提取 __yze_resp_format__=<span class="s">'json'</span>
   ↓ controller.php:109-110   $this-&gt;layout 自动设为 <span class="s">'json'</span>
   ↓ view.php:496-507  check_view 在模块 views 下找 <span class="s">view-formats_demo.json.php</span>
   <span class="c">//   找不到时 format 回落 'tpl' 再找一遍（view.php:484-491 的同款机制）</span>
   ↓ YZE_Layout 包 <span class="s">app/vendor/layouts/json.layout.php</span>（只 echo content_of_view）
   ↓ 浏览器收到 <span class="s">Content-Type: text/html</span> + JSON 字符串（按 yangzie.md:891 默认 PC 布局声明的）

jpg 走完全相同的路径，区别只在 layout 是 <span class="s">jpg.layout.php</span>（设 Content-Type: image/jpeg）</div>

        <p class="note">
            json 视图演示里 Content-Type 还是 text/html —— 框架默认用 PC 布局声明；
            如果想让 .json 也设 application/json，可以让 json 视图或 json 布局自己 header。
            jpg 演示必须由布局主动设 image/jpeg，否则浏览器会把二进制当成乱码文本。
        </p>
    </div>

    <div class="sec">
        <p>
            <a class="btn"      href="/test/view/formats_demo">默认 tpl</a>
            <a class="btn alt"  href="/test/view/formats_demo.json">.json 视图</a>
            <a class="btn warn" href="/test/view/formats_demo.jpg">.jpg 视图</a>
        </p>
        <a class="btn gray" href="/test/view">回视图演示首页</a>
    </div>
</div>