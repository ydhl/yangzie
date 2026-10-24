<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ⑭ —— Section 演示 · 解释页（docs/yangzie.md:865-877）。
 *
 * @var YZE_View_Adapter $this
 *
 * 视图数据：
 *   - master_demo_html  string ⑮ 链 get_output（master view 输出 sections）
 *   - layout_demo_html  string ⑯ 链 get_output（layout 输出 sections）
 */
$master_html = (string)$this->get_data('master_demo_html');
$layout_html = (string)$this->get_data('layout_demo_html');
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:960px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
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
               text-decoration:none; font-size:13px; background:#409eff; color:#fff; }
    .v a.btn.gray { background:#909399; }
    .v a.btn.alt  { background:#67c23a; }

    .grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .grid .panel { border:1px solid #ebeef5; border-radius:6px; overflow:hidden; }
    .grid .panel .ph { background:#fafbfc; padding:8px 12px; border-bottom:1px solid #ebeef5;
                       font-size:12px; color:#606266; }
    .grid .panel .pb { background:#fff; }

    /* 让嵌入的 section 演示 HTML 不被全局 body 样式破坏（自带完整 <html>）*/
    .embed { background:#fff; }
</style>

<div class="v">
    <div class="sec">
        <h1>Section 演示 · 方式⑮⑯</h1>
        <div class="sub">
            docs/yangzie.md:865-877 —— Action 需要把不同片段输出到界面不同位置时，
            用 <code>begin_section()</code> + <code>end_section($name)</code> 在 view 里定义 section，
            用 <code>$this-&gt;content_of_section($name)</code> 在容器（master view 或 layout）里 echo。
            同一份 section 内容可以在多个位置 echo（容器自己决定摆位），section 的内容由 view 贡献。
        </div>
    </div>

    <div class="sec">
        <h2>A. 两种容器同时演示：左侧 master view、右侧 layout</h2>
        <div class="grid">
            <div class="panel">
                <div class="ph">⑮ master view 输出 section</div>
                <div class="pb embed">
                    <?= $master_html ?>
                </div>
            </div>
            <div class="panel">
                <div class="ph">⑯ 自定义 layout 输出 section</div>
                <div class="pb embed">
                    <?= $layout_html ?>
                </div>
            </div>
        </div>
        <p class="note">
            两个面板用的是同一份内层 view（master/section-product）的同一组 section（breadcrumb / sidebar / og_meta），
            但由不同容器 echo：左边由 master view 在 <code>&lt;head&gt;</code> / <code>&lt;header&gt;</code> /
            <code>&lt;main&gt;</code> / <code>&lt;aside&gt;</code> 4 处分别 echo；右边由自定义 layout
            <code>section-demo.layout.php</code> 在同样的 3 处 echo。同一份内容在两个面板里呈现一致。
        </p>
    </div>

    <div class="sec">
        <h2>B. Section API（view.php:319-341）</h2>
        <div class="code"><span class="c">// View 里定义 section（PHP ob 嵌套机制）</span>
<span class="v">$this</span>-&gt;<span class="k">begin_section</span>();
    ... 任意 HTML / PHP 输出 ...
<span class="v">$this</span>-&gt;<span class="k">end_section</span>(<span class="s">'breadcrumb'</span>);   <span class="c">// 名字自己定；同一 view 可定义多个</span>

<span class="c">// 容器（master view 或 layout）里输出 section</span>
&lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'breadcrumb'</span>); ?&gt;

<span class="c">// 调试：列出当前 view 收集到的所有 section（view.php:319）</span>
<span class="v">$this</span>-&gt;view_sections();   <span class="c">// 返回 ['breadcrumb'=&gt;..., 'sidebar'=&gt;..., ...] 或 null</span></div>

        <h3>B-1. 内层 view 定义 section</h3>
        <div class="code"><span class="c">// modules/test/views/master/section-product.tpl.php</span>
&lt;article&gt;...产品正文（content_of_view 的内容）...&lt;/article&gt;

&lt;?php <span class="v">$this</span>-&gt;<span class="k">begin_section</span>(); ?&gt;
&lt;nav class="sp-breadcrumb"&gt;首页 / 产品 / 详情&lt;/nav&gt;
&lt;?php <span class="v">$this</span>-&gt;<span class="k">end_section</span>(<span class="s">'breadcrumb'</span>); ?&gt;

&lt;?php <span class="v">$this</span>-&gt;<span class="k">begin_section</span>(); ?&gt;
&lt;aside class="sp-sidebar"&gt;相关推荐...&lt;/aside&gt;
&lt;?php <span class="v">$this</span>-&gt;<span class="k">end_section</span>(<span class="s">'sidebar'</span>); ?&gt;

&lt;?php <span class="v">$this</span>-&gt;<span class="k">begin_section</span>(); ?&gt;
&lt;meta property="og:title" content="..."&gt;
&lt;?php <span class="v">$this</span>-&gt;<span class="k">end_section</span>(<span class="s">'og_meta'</span>); ?&gt;</div>

        <h3>B-2. ⑮ master view 容器 wiring（master/section-shell.tpl.php 节选）</h3>
        <div class="code">&lt;head&gt;
    &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'og_meta'</span>);   <span class="c">// section 进 &lt;head&gt;&lt;/span&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;header&gt; &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'breadcrumb'</span>); ?&gt; &lt;/header&gt;
    &lt;main&gt;   &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_view</span>();            ?&gt; &lt;/main&gt;
    &lt;aside&gt;  &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'sidebar'</span>' &lt;/aside&gt;
&lt;/body&gt;</div>

        <h3>B-3. ⑯ 自定义 layout 容器 wiring（app/vendor/layouts/section-demo.layout.php 节选）</h3>
        <div class="code"><span class="c">// View_Controller::section_layout() —— 换 layout，不走 master view：</span>
<span class="v">$this</span>-&gt;layout = <span class="s">'section-demo'</span>;

<span class="c">// app/vendor/layouts/section-demo.layout.php —— 在 &lt;head&gt;/&lt;header&gt;/&lt;aside&gt; echo 同一组 section</span>
&lt;head&gt;
    &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'og_meta'</span>); ?&gt;
&lt;/head&gt;
&lt;header&gt; &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'breadcrumb'</span>); ?&gt; &lt;/header&gt;
&lt;main&gt;   &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_view</span>(); ?&gt; &lt;/main&gt;
&lt;aside&gt;  &lt;?= <span class="v">$this</span>-&gt;<span class="k">content_of_section</span>(<span class="s">'sidebar'</span>); ?&gt; &lt;/aside&gt;</div>
    </div>

    <div class="sec">
        <h2>C. 数据流：section 在 master view 链中的传递（view.php:280-285）</h2>
        <div class="code">output_master() 调用链（⑮ 演示）：
1. current view (master/section-product) display_self
   <span class="c">//   → render 主内容 + 收集 breadcrumb / sidebar / og_meta 三个 section 到</span>
   <span class="c">//     $this-&gt;data['content_of_section']（view.php:339-341）</span>
2. ob_get_clean() → $data （仅主内容，section 内容已被 ob 抽出）
3. check_master() 找到 master/section-shell
4. output_master($data):
   <span class="v">$datas</span> = <span class="v">$this</span>-&gt;get_datas();
   <span class="v">$datas</span>[<span class="s">'content_of_section'</span>] = <span class="v">$this</span>-&gt;view_sections();   <span class="c">// 内层的 3 个 section</span>
   <span class="v">$datas</span>[<span class="s">'content_of_view'</span>]    = <span class="v">$data</span>;
   <span class="v">$master</span>-&gt;set_datas(<span class="v">$datas</span>);                                    <span class="c">// 整组传给 master</span>
   <span class="v">$master</span>-&gt;get_output()
5. master view display_self 跑 tpl：
   <span class="c">//   - 在 &lt;head&gt;/&lt;header&gt;/&lt;main&gt;/&lt;aside&gt; 4 处 echo 各 section</span>
   <span class="c">//   - master view 自身也可再 begin/end_section（master 也支持 content_of_section, view.php:187）</span>
6. YZE_Layout (default tpl.layout.php) 在 display_self 里 view.php:783 拉取最终的 sections 数组</div>

        <p class="note">
            section 名是 view 决定的；同一个 section 名只 echo 一次（容器一般 echo 一次），
            没被任何容器 echo 的 section 仍然会被收集，只是不显示。
            ⑮ 的 master view 自己定义了一个 <code>analytics_meta</code> section（演示用），
            但默认的 tpl.layout.php 不会 echo 它 —— 改用 ⑯ 自定义 layout 才会把它摆出来。
        </p>
    </div>

    <div class="sec">
        <h2>D. master view vs layout —— 两种容器的选择</h2>
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#fafbfc;">
                    <th style="text-align:left; padding:8px 10px; border:1px solid #ebeef5; width:24%;"></th>
                    <th style="text-align:left; padding:8px 10px; border:1px solid #ebeef5;">master view (⑮)</th>
                    <th style="text-align:left; padding:8px 10px; border:1px solid #ebeef5;">custom layout (⑯)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="padding:8px 10px; border:1px solid #ebeef5; color:#888;">位置</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">模块 views 或 app/vendor/views</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">app/vendor/layouts</td></tr>
                <tr><td style="padding:8px 10px; border:1px solid #ebeef5; color:#888;">启用方式</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;"><code>$view-&gt;master_view = "master/xxx"</code></td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;"><code>$this-&gt;layout = 'xxx'</code></td></tr>
                <tr><td style="padding:8px 10px; border:1px solid #ebeef5; color:#888;">默认 layout 再包一层</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">会（除非 <code>$this-&gt;layout=''</code>）</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">它就是布局</td></tr>
                <tr><td style="padding:8px 10px; border:1px solid #ebeef5; color:#888;">支持 section</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">是 (view.php:187)</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">是 (view.php:783)</td></tr>
                <tr><td style="padding:8px 10px; border:1px solid #ebeef5; color:#888;">适合</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">UI 共享片段（项目区/卡片层）</td>
                    <td style="padding:8px 10px; border:1px solid #ebeef5;">整页骨架（head/sidebar/footer）</td></tr>
            </tbody>
        </table>
        <p class="note">
            默认的 <code>tpl.layout.php</code> 已经 echo <code>content_of_section('section1')</code>（line 19），
            习惯上 Action 只要定义 <code>section1</code> 就能自动出现在 body 底部 —— 这是框架的"开箱即用"约定。
        </p>
    </div>

    <div class="sec">
        <p>
            <a class="btn"      href="/test/view/section_master">⑮ master view 输出 section</a>
            <a class="btn alt"  href="/test/view/section_layout">⑯ 自定义 layout 输出 section</a>
        </p>
        <a class="btn gray" href="/test/view">回视图演示首页</a>
    </div>
</div>