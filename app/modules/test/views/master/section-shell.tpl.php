<?php
namespace app\test;

/**
 * Section · Level 1：master view（演示用，充当整页响应）。
 *
 * 本视图把 section-product 提供的 3 个 section 和正文（content_of_view）
 * 在不同位置 echo 出来 —— 这就是"容器在 layout 中输出 section"的 wiring
 * （yangzie.md:876）。
 *
 * 本视图自己也定义了一个 section（analytics_meta），证明 master view 也
 * 支持 section（view.php:187 "master 也支持 content_of_section"）。
 * 它会被更上一级（这里是默认的 tpl.layout.php）的容器看到。
 *
 * 演示场景下 action 会 $this->layout = ''，所以本 master view 直接作为响应，
 * 不会被默认的 tpl.layout.php 再包一层 <html>。
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>产品详情 · Master View Section Demo</title>
    <?= $this->content_of_section('og_meta'); /* 内层 view 收集的 og/标签进 <head> */ ?>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
               background:#f5f6f8; margin:0; color:#222; }
        .ss-shell { background:#fff; max-width:920px; margin:24px auto;
                    border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.06);
                    overflow:hidden; }
        .ss-head { padding:14px 22px; background:#fafbfc; border-bottom:1px solid #ebeef5; }
        .ss-body { display:flex; min-height:60vh; }
        .ss-main { flex:1; padding:22px; }
        .ss-side { width:220px; padding:18px; background:#fafafa;
                   border-left:1px solid #ebeef5; }
        .ss-foot { padding:12px 22px; background:#fafbfc; border-top:1px solid #ebeef5;
                   font-size:12px; color:#888; display:flex; align-items:center; gap:8px; }
        .ss-tag { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8;
                  padding:2px 8px; border-radius:10px; font-size:11px; }
        .sp-article h1 { margin:0 0 6px; font-size:20px; }
        .sp-article .sp-meta { color:#888; font-size:12px; margin:0 0 12px; }
        .sp-breadcrumb a, .sp-breadcrumb span { color:#606266; font-size:12px; text-decoration:none; }
        .sp-breadcrumb .sp-sep { margin:0 6px; color:#c0c4cc; }
        .sp-sidebar h4 { margin:0 0 8px; font-size:13px; color:#606266; }
        .sp-sidebar ul { margin:0 0 10px; padding-left:18px; color:#606266; font-size:13px; }
        .sp-sidebar .sp-meta { font-size:11px; color:#909399; margin:0; }
    </style>
</head>
<body>
<div class="ss-shell">
    <header class="ss-head">
        <?= $this->content_of_section('breadcrumb'); /* 内层 view 收集的面包屑放头部 */ ?>
    </header>
    <div class="ss-body">
        <main class="ss-main">
            <?= $this->content_of_view(); /* 主响应（product detail）放主体 */ ?>
        </main>
        <aside class="ss-side">
            <?= $this->content_of_section('sidebar'); /* 内层 view 收集的侧栏放侧边 */ ?>
        </aside>
    </div>
    <footer class="ss-foot">
        <span class="ss-tag">master/section-shell</span>
        <span>master view 在不同位置 echo content_of_view / content_of_section</span>
    </footer>
</div>
</body>
</html>

<?php $this->begin_section(); ?>
<!-- analytics_meta 由本 master view 自己定义，可以被更上一级（layout）使用 -->
<!-- 默认 tpl.layout.php 不 echo 这个 section，所以会"沉默"地收集起来 -->
<meta name="x-section-origin" content="master-view">
<meta name="x-section-name"   content="analytics_meta">
<?php $this->end_section('analytics_meta'); ?>