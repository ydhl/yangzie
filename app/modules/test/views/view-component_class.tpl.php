<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;
use \app\test\User_Card_View as User_Card_View;

/**
 * 方式 ⑨ —— 视图组件重用：YZE_View_Component（docs/yangzie.md:747-760）。
 *
 * 与方式一（view-simple_view）同一个"用户卡片"的 OOP 实现：
 *   - 数据契约 = 类的公开方法（IDE 代码提示、常量可见）
 *   - 传错参数立刻抛异常，而不是静默渲染错
 *   - 视图格式在 output_component() 里自行判断（方式二的代价）
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据：
 *   - cards     array 控制器渲染好的组件 HTML（前 2 张）
 *   - card_json string 同一组件 json 格式的 get_output() 结果
 *   - typo_msg  string|null set_level 传错 key 的异常信息（演示 fail-fast）
 */
$cards     = (array)$this->get_data('cards');
$card_json = (string)$this->get_data('card_json');
$typo_msg  = (string)$this->get_data('typo_msg');
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
    .v table { border-collapse:collapse; width:100%; font-size:13px; }
    .v th, .v td { border:1px solid #ebeef5; padding:10px 12px; text-align:left;
                   vertical-align:top; line-height:1.7; }
    .v th { background:#fafbfc; color:#606266; font-weight:600; white-space:nowrap; }
    .v td.h { text-align:center; font-weight:600; white-space:nowrap; }
    .v td.h.one { color:#e6a23c; }
    .v td.h.two { color:#409eff; }
    .v .err { background:#fef0f0; border:1px solid #fde2e2; color:#f56c6c; border-radius:6px;
               padding:10px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
               margin-top:12px; line-height:1.7; }
    .v pre.json { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                  padding:12px 14px; font-size:12px; font-family:Menlo, Consolas, monospace;
                  color:#228b22; white-space:pre-wrap; margin-top:12px; }
</style>

<div class="v">
    <div class="sec">
        <h1>视图组件重用 · 方式二：YZE_View_Component</h1>
        <div class="sub">
            docs/yangzie.md:747-760 —— 把组件定义成继承 <code>YZE_View_Component</code> 的 class，
            解决方式一"看不到数据契约"的弊端。组件必须实现 <code>output_component()</code>，
            被 <code>output()</code> / <code>get_output()</code> 调用时执行；
            类名 <code>User_Card_View</code> ↔ 文件名 <code>user_card.view.php</code>，
            namespace 与路径对应，框架 autoload 自动加载，无需 include。
        </div>
    </div>
    <div class="sec">
        <h2>A. 渲染结果 —— 与方式一同一张卡片（alice / bob 由控制器渲染）</h2>
        <?php foreach ($cards as $card_html) { echo $card_html; } ?>

        <?php
        // 第 3 张在本模板里直接构建（视图里上下文控制器用 get_controller() 取），
        // 和方式一第 3 张的写法对照：调用方式完全一致，但这里每个方法都有代码提示
        echo User_Card_View::build($this->get_controller())
            ->set_user('erin', '艾琳', 'erin@example.com')
            ->set_level('guest')
            ->get_output();
        ?>

        <div class="code"><span class="c">// View_Controller::component_class() —— 组件：modules/test/views/user_card.view.php</span>
<span class="v">$cards</span>[] = User_Card_View::<span class="k">build</span>(<span class="v">$this</span>)       <span class="c">// 工厂方法：上下文控制器（构造约定，yangzie.md:751）</span>
    -&gt;<span class="k">set_user</span>(<span class="s">'alice'</span>, <span class="s">'爱丽丝'</span>, <span class="s">'alice@example.com'</span>)  <span class="c">// 参数签名 = 数据契约，IDE 逐个提示</span>
    -&gt;<span class="k">set_level</span>(<span class="s">'admin'</span>)                  <span class="c">// 只接受 User_Card_View::LEVELS 里的 key</span>
    -&gt;get_output();                        <span class="c">// 触发 output_component()，返回字符串</span></div>
    </div>

    <div class="sec">
        <h2>B. 方式一 vs 方式二 —— 同一张卡片的两种重用方式</h2>
        <table>
            <tr><th></th><td class="h one">方式一 · YZE_Simple_View</td><td class="h two">方式二 · YZE_View_Component</td></tr>
            <tr>
                <th>组件形态</th>
                <td>传统 php 模板（user-card.tpl.php），数据靠注释约定</td>
                <td>继承 YZE_View_Component 的 class，数据契约 = 公开方法</td>
            </tr>
            <tr>
                <th>怎么知道传什么</th>
                <td>打开模板源码看注释（无代码提示）</td>
                <td>set_user()/set_level() 签名 + LEVELS 常量，IDE 全程提示</td>
            </tr>
            <tr>
                <th>传错数据</th>
                <td>数组 key 拼错 → 静默不显示，页面见了才发现</td>
                <td>set_level('admim') → 当场抛 InvalidArgumentException</td>
            </tr>
            <tr>
                <th>视图格式</th>
                <td>框架自动找 user-card.{format}.php，找不到回落 tpl</td>
                <td>组件在 output_component() 里自行判断（本组件支持 tpl/json）</td>
            </tr>
            <tr>
                <th>加载方式</th>
                <td>new YZE_Simple_View(路径, 数据, 控制器)</td>
                <td>autoload 按命名约定自动加载（类名_View ↔ 文件.view.php）</td>
            </tr>
        </table>
        <?php if ($typo_msg !== ''): ?>
        <div class="err">演示 fail-fast —— 控制器里执行 set_level('admim')（拼错的 admin），组件当场抛出：<br><?= htmlspecialchars($typo_msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <div class="sec">
        <h2>C. 视图格式 —— 组件在 output_component() 里自行判断（方式二的代价）</h2>
        <div class="sub">
            同一个组件固定 json 格式渲染（构造第 3 参传 <code>'json'</code>），
            <code>get_output()</code> 得到的就是 JSON 字符串：
        </div>
        <pre class="json"><?= htmlspecialchars($card_json, ENT_QUOTES, 'UTF-8') ?></pre>
        <p class="note">
            组件对象还能直接被 action return 当整页响应（方式⑩）——
            URI 尾部带 <code>.json</code> 后缀（request.php:731 解析，注意 <code>?format=</code> 查询参数不受支持）时
            构造函数自动取请求格式，同一个组件两种输出：
        </p>
        <p>
            <a class="btn" href="/test/view/component_class_output">⑩ html 版</a>
            <a class="btn gray" href="/test/view/component_class_output.json">⑩ .json 版</a>
        </p>
    </div>

    <div class="sec">
        <div class="code"><span class="c">// 命名 / autoload 约定（yangzie.md:753-758，实现在 startup.php:42-46）：</span>
<span class="c">//   类名   User_Card_View（驼峰、下划线分词、_View 后缀）</span>
<span class="c">//   文件名 user_card.view.php（组件名全小写）</span>
<span class="c">//   放模块 views 中：namespace 用模块命名空间 app\test（本组件），</span>
<span class="c">//     autoload 主路径直接命中 modules/test/views/xxx.view.php</span>
<span class="c">//   放 app/vendor/views 等其他位置也可以：namespace 与路径对应（app\vendor\views），</span>
<span class="c">//     走 startup.php:45 的路径回退查找</span>
<span class="c">//</span>
<span class="c">// 方式二的收益：OOP 重用，可提供更多处理方式（工厂、校验、常量契约）</span>
<span class="c">// 方式二的代价：视图格式的支持需要组件自行判断处理</span>
<span class="c">//</span>
<span class="c">// → 方式三 master view / layout（yangzie.md:762-771）：组件要输出到页面</span>
<span class="c">//   多个位置（如顶部菜单 + 边栏 + 内容区）时用</span></div>
    </div>

    <div class="sec">
        <a class="btn gray" href="/test/view">回视图演示首页</a>
        <a class="btn gray" href="/test/view/simple_view" style="margin-left:6px">对比方式一</a>
    </div>
</div>
