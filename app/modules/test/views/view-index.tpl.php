<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/view 视图 —— 视图选择演示首页。
 *
 * 列出 17 种"在控制器中选 view / 重用视图组件 / Master View 嵌套 / Section 容器输出 /
响应格式"的方式，对应 17 个 action 入口（⑰ 是 URI 后缀多入口）。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:880px; border-radius:8px;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .v h1 { margin:0 0 4px; font-size:22px; }
    .v .sub { color:#888; font-size:13px; margin-bottom:18px; line-height:1.7; }
    .v table { border-collapse:collapse; width:100%; font-size:13px; }
    .v th, .v td { border:1px solid #ebeef5; padding:10px 12px; text-align:left;
                   vertical-align:top; line-height:1.7; }
    .v th { background:#fafbfc; color:#606266; font-weight:600; white-space:nowrap; }
    .v td.name { font-family:Menlo, Consolas, monospace; white-space:nowrap; }
    .v td a.btn { display:inline-block; padding:4px 12px; border-radius:4px; font-size:12px;
                  background:#409eff; color:#fff; text-decoration:none; }
    .v td a.btn:hover { background:#66b1ff; }
    .v td a.btn.alt { background:#909399; margin-left:6px; }
    .v .expl { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:14px 16px; font-size:12px; color:#606266;
               font-family:Menlo, Consolas, monospace; line-height:1.8;
               margin-top:18px; white-space:pre-wrap; }
    .v .expl .c { color:#888; }
    .v .expl .k { color:#c45656; }
    .v .expl .v { color:#228b22; }
</style>

<div class="v">
    <h1>视图选择演示</h1>
    <div class="sub">
        控制器中指定 / 构造响应视图的 17 种方式：①-⑥ 在控制器里"选视图"，⑦-⑩ 视图组件重用
        （⑦⑧ 方式一 · 简单文件包含 yangzie.md:733-745；⑨⑩ 方式二 · YZE_View_Component yangzie.md:747-760），
        ⑪⑫⑬ Master View 多级嵌套 yangzie.md:850-863，⑭⑮⑯ Section 容器输出 yangzie.md:865-877，
        ⑰ 响应格式 tpl/json/jpg yangzie.md:878-885。核心入口：
        <code>YZE_Resource_Controller::get_Response()</code>（<code>yangzie/controller.php:71-98</code>）、
        <code>handle_request()</code>（<code>yangzie/controller.php:179-195</code>）、
        <code>YZE_Simple_View</code>（<code>yangzie/view.php:435-514</code>）。
    </div>

    <table>
        <tr>
            <th>方式</th>
            <th>URL / action</th>
            <th>模板路径</th>
            <th>入口</th>
        </tr>
        <tr>
            <td class="name">① 默认命名约定</td>
            <td>view/default_view</td>
            <td>modules/test/views/view-default_view.tpl.php</td>
            <td><a class="btn" href="/test/view/default_view">访问</a></td>
        </tr>
        <tr>
            <td class="name">② $this-&gt;view 覆盖</td>
            <td>view/property_override</td>
            <td>…view-property_override_alt.tpl.php<br><span style="color:#888">（action 覆盖了默认命名）</span></td>
            <td><a class="btn" href="/test/view/property_override">访问</a></td>
        </tr>
        <tr>
            <td class="name">③ 运行时按 GET 参数选</td>
            <td>view/runtime_select</td>
            <td>…view-runtime_select_{full|compact}.tpl.php</td>
            <td>
                <a class="btn"      href="/test/view/runtime_select?tpl=full">full</a>
                <a class="btn alt"  href="/test/view/runtime_select?tpl=compact">compact</a>
            </td>
        </tr>
        <tr>
            <td class="name">④ return YZE_Simple_View</td>
            <td>view/return_full</td>
            <td>…view-return_full.tpl.php<br><span style="color:#888">（路径由 action 传）</span></td>
            <td><a class="btn" href="/test/view/return_full">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑤ return YZE_Notpl_View</td>
            <td>view/return_raw</td>
            <td><b>无模板文件</b>，控制器直接生成字符串</td>
            <td><a class="btn" href="/test/view/return_raw">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑥ return YZE_JSON_View</td>
            <td>view/return_json</td>
            <td><b>无模板文件</b>，输出 JSON</td>
            <td><a class="btn" href="/test/view/return_json">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑦ include / 组件文件包含</td>
            <td>view/simple_view</td>
            <td>…views/_stat-partial.tpl.php<span style="color:#888">（include）</span><br>
                …views/user-card.tpl.php<span style="color:#888">（模块 views，YZE_Simple_View）</span></td>
            <td><a class="btn" href="/test/view/simple_view">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑧ 组件当整页响应</td>
            <td>view/component_output</td>
            <td>…views/user-card.tpl.php<br><span style="color:#888">（return YZE_Simple_View + layout=''）</span></td>
            <td><a class="btn" href="/test/view/component_output">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑨ YZE_View_Component</td>
            <td>view/component_class</td>
            <td>…views/user_card.view.php<br><span style="color:#888">（类 app\test\User_Card_View，autoload 自动加载）</span></td>
            <td><a class="btn" href="/test/view/component_class">访问</a></td>
        </tr>
        <tr>
            <td class="name">⑩ 组件类当整页响应</td>
            <td>view/component_class_output</td>
            <td>…views/user_card.view.php<br><span style="color:#888">（return 组件对象，自带 tpl/json 格式自适应）</span></td>
            <td>
                <a class="btn" href="/test/view/component_class_output">html</a>
                <a class="btn alt" href="/test/view/component_class_output.json">json</a>
            </td>
        </tr>
        <tr>
            <td class="name">⑪ Master View 三级嵌套</td>
            <td>view/master_three_level</td>
            <td>…views/master/content.tpl.php 等<br><span style="color:#888">（content → project-page → site-shell）</span></td>
            <td>
                <a class="btn" href="/test/view/master_three_level">渲染</a>
                <a class="btn alt" href="/test/view/master_nesting">⑬ 解释页</a>
            </td>
        </tr>
        <tr>
            <td class="name">⑫ 共享 master_view</td>
            <td>view/master_action2</td>
            <td>…views/master/content.tpl.php<br><span style="color:#888">（与⑪共用 project-page + site-shell）</span></td>
            <td><a class="btn" href="/test/view/master_action2">渲染</a></td>
        </tr>
        <tr>
            <td class="name">⑭ Section · 解释页</td>
            <td>view/section_demo</td>
            <td>…views/view-section_demo.tpl.php<br><span style="color:#888">（A 区并列演示 ⑮⑯；B 区 API+wiring；D 区对比表）</span></td>
            <td><a class="btn" href="/test/view/section_demo">解释页</a></td>
        </tr>
        <tr>
            <td class="name">⑮ Section · master view 输出</td>
            <td>view/section_master</td>
            <td>…views/master/section-{product,shell}.tpl.php<br><span style="color:#888">（master view 在 4 处 echo 不同 section）</span></td>
            <td><a class="btn" href="/test/view/section_master">渲染</a></td>
        </tr>
        <tr>
            <td class="name">⑯ Section · 自定义 layout 输出</td>
            <td>view/section_layout</td>
            <td>…layouts/section-demo.layout.php<br><span style="color:#888">（自定义 layout 在 3 处 echo section）</span></td>
            <td><a class="btn" href="/test/view/section_layout">渲染</a></td>
        </tr>
        <tr>
            <td class="name">⑰ 响应格式 tpl/json/jpg</td>
            <td>view/formats_demo[.json|.jpg]</td>
            <td>…views/view-formats_demo.{tpl,json,jpg}.php<br><span style="color:#888">（同 Action / 同 data / 三种响应）</span></td>
            <td>
                <a class="btn"      href="/test/view/formats_demo">tpl</a>
                <a class="btn alt"  href="/test/view/formats_demo.json">json</a>
                <a class="btn warn" href="/test/view/formats_demo.jpg">jpg</a>
            </td>
        </tr>
    </table>

    <div class="expl"><h4 style="margin:0 0 8px;font-family:inherit;font-size:13px">核心调度：controller.php:179-195 handle_request()</h4>
<span class="c">// 调 action（GET index / POST post_index / DELETE delete_index，控制器名-action 名映射）</span>
<span class="v">$response</span> = <span class="v">$this</span>-&gt;<span class="v">$method</span>();
<span class="k">if</span> (! <span class="v">$response</span>) {                                  <span class="c">// ①/②/③ 走这里：action 没返回 → get_Response()</span>
    <span class="v">$response</span> = <span class="v">$this</span>-&gt;get_Response();                <span class="c">//     用 $this-&gt;view 覆盖默认命名（controller.php:83-87）</span>
}
<span class="c">// ④/⑤/⑥ 不走上面 if：action 直接 return YZE_Simple_View / Notpl_View / JSON_View</span>
<span class="c">// handle_request() 把 $response 交给 startup.php:111-115 的 YZE_Layout 包裹输出</span>

<span class="c">// get_Response() 的关键判定（controller.php:80-92）：</span>
<span class="k">if</span> (<span class="v">$this</span>-&gt;view) {
    <span class="v">$tpl</span> = <span class="v">$this</span>-&gt;view;                                <span class="c">// ②/③ 用到的覆盖路径</span>
} <span class="k">else</span> {
    <span class="v">$tpl</span> = str_replace("_Controller","","View")."-".<span class="v">$method</span>;  <span class="c">// ① 默认：view-default_view</span>
}
<span class="v">$view</span> = <span class="v">$request</span>-&gt;view_path()."/".<span class="v">$tpl</span>;          <span class="c">// 模块 views 目录</span>
<span class="k">return</span> <span class="k">new</span> YZE_Simple_View(<span class="v">$view</span>, <span class="v">$view_data</span>, <span class="v">$this</span>, <span class="v">$format</span>);
    </div>

    <div style="margin-top:18px">
        <a class="btn" href="/test" style="background:#909399">回 test 模块首页</a>
    </div>
</div>