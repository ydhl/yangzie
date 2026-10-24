<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;
use \yangzie\YZE_Simple_View as YZE_Simple_View;

/**
 * 方式 ⑦ —— 视图组件重用：简单文件包含（docs/yangzie.md:733-745）。
 *
 * A 区：传统 include（同目录局部模板 _stat-partial.tpl.php，靠局部变量传值）
 * B 区：YZE_Simple_View + 模块 views/user-card.tpl.php（视图组件）
 * C 区：get_output() / output() 两种输出方式对比
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据：
 *   - cards array 控制器用 YZE_Simple_View::get_output() 渲染好的组件 HTML
 */
$cards = (array)$this->get_data('cards');

// A 区的数据：传统 include 就是在 include 之前把局部变量准备好
$stats = [
    ['label' => '今日订单', 'value' => '1,286', 'delta' => '12.4%', 'up' => true],
    ['label' => '活跃用户', 'value' => '3,542', 'delta' => '3.1%',  'up' => true],
    ['label' => '平均耗时', 'value' => '486ms', 'delta' => '8.2%',  'up' => false],
];
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:880px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .v .sec { padding:22px 28px; }
    .v .sec + .sec { border-top:1px solid #ebeef5; }
    .v h1 { margin:0 0 6px; font-size:22px; }
    .v h2 { margin:0 0 12px; font-size:15px; color:#444; }
    .v .sub { color:#888; font-size:13px; line-height:1.7; }
    .v .note { color:#666; font-size:12px; line-height:1.8; margin:12px 0 0; }
    .v .code { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
               line-height:1.7; color:#606266; white-space:pre-wrap; margin-top:12px; }
    .v .code .c { color:#888; }
    .v .code .k { color:#c45656; }
    .v .code .s { color:#228b22; }
    .v a.btn { display:inline-block; padding:8px 18px; border-radius:4px;
               text-decoration:none; font-size:13px; background:#409eff; color:#fff; }
    .v a.btn.gray { background:#909399; }

    /* A 区：include 的局部模板不带样式（依赖使用方提供），所以样式写在使用者这里 */
    .stats { display:flex; gap:10px; }
    .stat { flex:1; background:#fafbfc; border:1px solid #ebeef5; border-radius:8px; padding:14px 16px; }
    .stat .s-label { font-size:12px; color:#888; margin-bottom:6px; }
    .stat .s-value { font-size:22px; font-weight:600; color:#222; font-family:Menlo, Consolas, monospace; }
    .stat .s-delta { font-size:12px; margin-top:6px; font-family:Menlo, Consolas, monospace; }
    .stat .s-delta.up   { color:#67c23a; }
    .stat .s-delta.down { color:#f56c6c; }

    /* B 区卡片（.ucard）的样式由组件自带，这里不用管 —— 自包含 */
    .v .cardwrap { padding:2px 0 0; }

    .v table.cmp { border-collapse:collapse; width:100%; font-size:13px; }
    .v table.cmp th, .v table.cmp td { border:1px solid #ebeef5; padding:10px 12px;
                                       text-align:left; line-height:1.7; vertical-align:top; }
    .v table.cmp th { background:#fafbfc; color:#606266; font-weight:600; white-space:nowrap; }
    .v table.cmp td.m { font-family:Menlo, Consolas, monospace; white-space:nowrap; }
</style>

<div class="v">
    <div class="sec">
        <h1>视图组件重用 · 方式一：简单的文件包含</h1>
        <div class="sub">
            docs/yangzie.md:733-745 —— 重用视图最轻量的方式：组件就是普通的 php 模板文件。
            两种用法：<b>传统 include</b>（只建议同目录复用）和
            <b>YZE_Simple_View</b>（本演示组件放在模块 views 中；系统级重用时放 <code>app/vendor/views</code>）。
        </div>
    </div>

    <div class="sec">
        <h2>A. 传统 include —— 同目录复用</h2>
        <div class="stats">
            <?php foreach ($stats as $stat) { include __DIR__ . "/_stat-partial.tpl.php"; } ?>
        </div>
        <div class="code"><span class="c">// view-simple_view.tpl.php（本页）：include 之前把局部变量准备好</span>
<span class="v">$stat</span> = [<span class="s">'label'</span>=&gt;<span class="s">'今日订单'</span>, <span class="s">'value'</span>=&gt;<span class="s">'1,286'</span>, <span class="s">'delta'</span>=&gt;<span class="s">'12.4%'</span>, <span class="s">'up'</span>=&gt;<span class="k">true</span>];
<span class="k">include</span> __DIR__ . <span class="s">"/_stat-partial.tpl.php"</span>;   <span class="c">// 路径自己负责，传值靠局部变量</span>
<span class="c">// 局部模板里没有 $this / get_data，也无人帮你处理"视图格式"</span></div>
        <p class="note">
            弊端：跨模块用这种方式重用会导致维护困难，include 也无法自动处理格式问题 ——
            所以重用请用下面的 YZE_Simple_View（组件放模块 views，系统级重用时拷到 <code>app/vendor/views</code>）。
        </p>
    </div>

    <div class="sec">
        <h2>B. YZE_Simple_View + 模块 views 组件</h2>
        <div class="cardwrap">
            <?php foreach ($cards as $card_html) { echo $card_html; }   // 控制器 get_output() 渲染好传来的 ?>

            <?php
            // 组件不只能在控制器里用 —— 第 3 张卡片是本模板直接 new 出来的，
            // 证明 YZE_Simple_View 在视图里也能包含组件。
            // 注意：视图里的 $this 是视图对象（YZE_View_Adapter）而不是控制器，
            // 上下文控制器要用 $this->get_controller() 取（view.php:250，也可用 $this->controller）
            $inline = new YZE_Simple_View(
                \yangzie\YZE_Request::get_instance()->view_path() . "/user-card",  // 模块 views 目录
                ['user' => ['username' => 'dave', 'name' => '大卫', 'email' => 'dave@example.com'], 'tag' => '观察者'],
                $this->get_controller()
            );
            echo $inline->get_output();
            ?>
        </div>
        <div class="code"><span class="c">// View_Controller::simple_view() —— 组件 user-card.tpl.php 放在模块 views 中：</span>
<span class="v">$view</span> = <span class="k">new</span> YZE_Simple_View(
    <span class="v">$this</span>-&gt;request-&gt;view_path() . <span class="s">"/user-card"</span>,   <span class="c">// → modules/test/views/user-card.tpl.php</span>
    [<span class="s">'user'</span> =&gt; <span class="v">$user</span>, <span class="s">'tag'</span> =&gt; <span class="s">'管理员'</span>],   <span class="c">// 数组数据；组件里用 $this-&gt;get_data() 取</span>
    <span class="v">$this</span>                            <span class="c">// 上下文控制器（格式从这里推断）</span>
);
<span class="v">$cards</span>[] = <span class="v">$view</span>-&gt;get_output();              <span class="c">// 只要结果字符串，嵌入本页</span>
<span class="c">// 系统级重用：把组件拷贝到 app/vendor/views，第一个参数换成</span>
<span class="c">//   YZE_APP_VIEWS_INC . "user-card"（yangzie.md:743，两种放法组件本身都不用改）</span>
<span class="c">// 第 4 个参数 $format 不传 → 自动按请求格式找 user-card.{format}.php，</span>
<span class="c">// 找不到回落 tpl（view.php:496-507）；要固定格式可显式传入</span></div>
        <p class="note">
            组件是"传统"的 php 模板，不需要为被重用做任何改造（本页和方式④用到的是同一个类
            <code>YZE_Simple_View</code>，区别只在于：④ 按"控制器-action"命名约定选<b>页面</b>模板，⑦ 包含的是任意命名的<b>组件</b>模板）。
            组件自带样式，拷贝到其他模块 views 或 vendor/views 即可复用。
        </p>
    </div>

    <div class="sec">
        <h2>C. get_output() 与 output() —— 两种输出方式</h2>
        <table class="cmp">
            <tr><th>方法</th><th>行为</th><th>本演示中的用法</th></tr>
            <tr>
                <td class="m">get_output()</td>
                <td>返回渲染结果的字符串，不输出（内部即 output(true)，view.php:300-312）</td>
                <td>本页 B 区：组件 HTML 作为片段嵌入页面</td>
            </tr>
            <tr>
                <td class="m">output()</td>
                <td>直接 echo 到响应，组件输出就是响应本身</td>
                <td>
                    <a class="btn" href="/test/view/component_output">/test/view/component_output</a>
                    —— 同一个 user-card 组件当整页响应（action return YZE_Simple_View + layout=''）
                </td>
            </tr>
        </table>
    </div>

    <div class="sec">
        <div class="code"><span class="c">// 方式一的弊端（yangzie.md:745）：</span>
<span class="c">// 使用者无法直观地知道组件需要传入什么数据（没有代码提示），</span>
<span class="c">// 只能进入 user-card.tpl.php 查看顶部注释或实现双方约定。</span>
<span class="c">//</span>
<span class="c">// → 方式二 YZE_View_Component（yangzie.md:747-760）用 OOP 类 + output_component()</span>
<span class="c">//   解决这个问题，组件需要什么数据看类签名就知道</span></div>
    </div>

    <div class="sec">
        <a class="btn gray" href="/test/view">回视图演示首页</a>
    </div>
</div>
