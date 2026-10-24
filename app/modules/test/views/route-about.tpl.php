<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * 自定义路由演示：关于（只有静态 args，没有动态 path 参数）。
 *
 * ai@2026-09-22 对应路由 'test/demo/about'，
 * args 里写死的 name / version 通过 $request->get_var() 获取。
 *
 * @var YZE_View_Adapter $this 视图
 */
?>
<style>
    .route-demo { max-width:720px; margin:24px auto; padding:0 16px;
                  font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .route-demo h1 { font-size:20px; margin-bottom:4px; }
    .route-demo .sub { color:#888; font-size:13px; margin-bottom:20px; word-break:break-all; }
    .route-demo table { width:100%; border-collapse:collapse; background:#fff;
                        border:1px solid #ebeef5; border-radius:8px; overflow:hidden; }
    .route-demo th, .route-demo td { text-align:left; padding:12px 16px; font-size:13px; }
    .route-demo th { background:#fafafa; color:#909399; font-weight:500;
                     border-bottom:1px solid #ebeef5; }
    .route-demo td { border-bottom:1px solid #f5f5f5; }
    .route-demo tr:last-child td { border-bottom:none; }
    .route-demo code { background:#f5f7fa; padding:2px 6px; border-radius:3px;
                       font-size:12px; color:#e6a23c; font-family:Consolas, Monaco, monospace; }
    .route-demo .val { font-weight:600; color:#409eff; word-break:break-all; }
    .route-demo .back { display:inline-block; margin-top:20px; font-size:13px; color:#409eff; }
</style>

<div class="route-demo">
    <h1>自定义路由演示（静态 args）</h1>
    <div class="sub">
        路由配置：<code>'test/demo/about' =&gt; ['controller'=&gt;'route', 'action'=&gt;'about', 'args'=&gt;['name'=&gt;'Yangzie', 'version'=&gt;'1.0']]</code><br>
        实际请求 URI：<code><?= htmlspecialchars((string)$this->get_Data('uri'), ENT_QUOTES, 'UTF-8') ?></code>
    </div>

    <table>
        <tr>
            <th style="width:150px">参数来源</th>
            <th style="width:240px">取值方式</th>
            <th>值</th>
        </tr>
        <tr>
            <td>静态 args 参数</td>
            <td><code>$request-&gt;get_var('name')</code></td>
            <td class="val"><?= htmlspecialchars((string)$this->get_Data('name'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td>静态 args 参数</td>
            <td><code>$request-&gt;get_var('version')</code></td>
            <td class="val"><?= htmlspecialchars((string)$this->get_Data('version'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>

    <a class="back" href="/test">← 返回演示列表</a>
</div>