<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 方式 ⑬ —— Master View 三级嵌套解释页（docs/yangzie.md:850-863）。
 *
 * 默认视图，由 master_nesting() action 渲染。页内通过 get_output()
 * 把 ⑪ 同样的三级链渲染出来嵌进 A 区，并附完整 wiring 分解 + 共享 master 链接。
 *
 * @var YZE_View_Adapter $this
 *
 * 视图数据：
 *   - rendered_html string controller 渲染好的三级链（site-shell > project-page > content）
 */
$rendered = (string)$this->get_data('rendered_html');
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .v { background:#fff; width:920px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .v .sec { padding:22px 28px; }
    .v .sec + .sec { border-top:1px solid #ebeef5; }
    .v h1 { margin:0 0 6px; font-size:22px; }
    .v h2 { margin:0 0 12px; font-size:15px; color:#444; }
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

    /* ===== 三层包装的视觉标注（与 master/*.tpl 输出对照） ===== */
    .stage { border:2px dashed #dcdfe6; border-radius:8px; padding:0; overflow:hidden; }
    .layer { padding:14px 16px; border-radius:6px; margin:0; }
    .layer .lvl { font-size:11px; font-weight:600; letter-spacing:.5px; padding:2px 8px;
                  border-radius:10px; margin-right:8px; }
    .layer .note { color:#888; font-size:11px; margin-left:6px; }
    .lvl2 { background:#ecf5ff; color:#409eff; border:1px solid #d9ecff; }
    .lvl1 { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8; }
    .lvl0 { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8; }
    /* 注：.mv-* 链内样式由 master/site-shell.tpl.php 注入，解释页不再重复定义 */
</style>

<div class="v">
    <div class="sec">
        <h1>Master View 三级嵌套 · 方式⑪⑫</h1>
        <div class="sub">
            docs/yangzie.md:850-863 —— 公共部分 A、B 用 Master View 串成多层嵌套：
            Action 内容 → project-page（"公共部分A"） → site-shell（"公共部分B"）。
            每个 tpl 顶部用 <code>$this-&gt;master_view = "master/project-page"</code> 声明自己
            被谁包起来；框架 <code>view.php:458-494</code> 按"模块 views → app/vendor/views → 相对路径"
            顺序解析。tpl 内 <code>$this-&gt;content_of_view()</code> 输出被它包住的内层渲染结果。
        </div>
    </div>

    <div class="sec">
        <h2>A. 渲染结果 —— ⑪ master_three_level（标签展示 3 层边界）</h2>
        <div class="stage">
            <div class="layer" style="background:#eef5fb;">
                <span class="lvl lvl2">Level 2 · site-shell</span>
                <span class="note">外层站点壳</span>
                <div style="margin-top:10px">
                    <?php
                    // 控制器把同样的 3 级链 get_output() 出来嵌在这里 —— 展示完整渲染结果
                    echo $rendered;
                    ?>
                </div>
            </div>
        </div>
        <p class="note">
            上面那团嵌套里你能看到三个色块：蓝标签的是 site-shell（Level 2，本页 stage 的虚线框），
            黄边的是 project-page（Level 1），绿边的是 content（Level 0）。
        </p>
    </div>

    <div class="sec">
        <h2>B. 框架输出顺序（view.php:300-306）</h2>
        <div class="code">output() 调用链：
1. display_self()                       <span class="c">// 渲染当前视图（content.tpl.php） → ob_get_clean → $data</span>
2. check_master()                       <span class="c">// 解析 $this-&gt;master_view = "master/project-page"</span>
   <span class="c">//   顺序：modules/test/views/master/project-page.tpl.php → app/vendor/views/... → 相对路径</span>
3. output_master($data)                 <span class="c">// 把 $data 放进新 view 的 content_of_view，再 get_output()</span>
   <span class="c">//   递归触发上面 1~3 步；这次渲染的是 project-page.tpl.php，</span>
   <span class="c">//   它顶部又 declare 了 master_view = "master/site-shell"</span>
4. 再次 output_master → 渲染 site-shell.tpl.php（最外层，未声明 master_view，stop）
5. 把整条链的输出作为响应回给浏览器</div>
    </div>

    <div class="sec">
        <h2>C. 三个 tpl 顶部的 master_view 声明（整条链的 wiring 都在这里）</h2>
        <div class="code"><span class="c">// modules/test/views/master/content.tpl.php</span>
<span class="v">$this</span>-&gt;master_view = <span class="s">"master/project-page"</span>;

<span class="c">// modules/test/views/master/project-page.tpl.php</span>
<span class="v">$this</span>-&gt;master_view = <span class="s">"master/site-shell"</span>;
&lt;div class="mv-project"&gt;
    &lt;h2&gt;&lt;?= htmlspecialchars(<span class="v">$this</span>-&gt;get_data(<span class="s">'page_title'</span>)) ?&gt;&lt;/h2&gt;
    &lt;div class="mv-slot"&gt; &lt;?= <span class="v">$this</span>-&gt;content_of_view(); ?&gt; &lt;/div&gt;   <span class="c">// 内层 content 的渲染结果</span>
&lt;/div&gt;

<span class="c">// modules/test/views/master/site-shell.tpl.php（最外层，不再 declare）</span>
    &lt;?= <span class="v">$this</span>-&gt;content_of_view(); ?&gt;                            <span class="c">// 内层 project-page 的渲染结果</span></div>

        <div class="code"><span class="c">// View_Controller::master_three_level() —— action 只负责"造最内层 + 关 layout"</span>
<span class="v">$this</span>-&gt;layout = <span class="s">''</span>;                                         <span class="c">// 关掉全局 tpl 布局，让 master 链成为响应</span>
<span class="v">$inner</span> = <span class="k">new</span> YZE_Simple_View(
    <span class="v">$this</span>-&gt;request-&gt;view_path() . <span class="s">"/master/content"</span>,       <span class="c">// → modules/test/views/master/content.tpl.php</span>
    [<span class="s">'username'</span>=&gt;<span class="s">'爱丽丝'</span>, <span class="s">'role'</span>=&gt;<span class="s">'管理员'</span>, <span class="s">'email'</span>=&gt;<span class="s">'alice@example.com'</span>],
    <span class="v">$this</span>                                                       <span class="c">// 上下文控制器</span>
);
<span class="k">return</span> <span class="v">$inner</span>;                                                <span class="c">// master_view 由 content.tpl.php 自己 declare</span></div>
        <p class="note">
            master_view 也可以直接赋值为一个 <code>YZE_View_Component</code> 对象
            （view.php:276 检测后绕过字符串解析直接用），适合在 action 里组合多种组件当 master：
        </p>
        <div class="code"><span class="c">// 组件作为 master：</span>
<span class="v">$inner</span>-&gt;master_view = My_Shell_View::<span class="k">build</span>(<span class="v">$this</span>)-&gt;set_title(<span class="s">'项目区'</span>);   <span class="c">// 整层都是组件，不再走 tpl 文件解析</span></div>
    </div>

    <div class="sec">
        <h2>D. 共享 master_view —— docs/yangzie.md:854</h2>
        <div class="sub">
            "Action1 和 Action2 两个 URI 请求的响应中都有相同的界面部分公共部分A"——
            下面这两个 action 共用同一个 <code>master/project-page</code> 壳，差异只在 content 部分。
        </div>
        <p>
            <a class="btn" href="/test/view/master_three_level">⑪ master_three_level（alice/管理员）</a>
            <a class="btn alt" href="/test/view/master_action2">⑫ master_action2（bob/编辑）</a>
        </p>
        <p class="note">
            两个 action 写法完全相同，只是传入 inner content 的数据不同。
            点开两个链接对比：外壳（project-page）和最外层（site-shell）一致，
            只有绿框（content）的用户名/角色/邮箱变了 —— 这就是 master view "壳里装不同东西"的价值。
        </p>
    </div>

    <div class="sec">
        <a class="btn gray" href="/test/view">回视图演示首页</a>
    </div>
</div>