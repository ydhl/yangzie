<?php
namespace yangzie;

/**
 * 自定义 layout —— "Section · Layout Demo" 专用布局（演示 ⑯）。
 *
 * 和默认的 tpl.layout.php 对比：
 *   - tpl.layout.php 只 echo 一个固定名字的 section（'section1'）
 *   - 本 layout 在 <head> / <header> / <aside> 三处 echo 不同 section，
 *     展示"layout 也是 container，也能在不同位置输出 section"的能力
 *     （view.php:186-187 master/layout 都支持 content_of_section）。
 *
 * Section 在 view 的 tpl 里定义（yangzie.md:875）：
 *   begin_section() ... end_section($name)
 * 由本 layout 在不同位置 echo：
 *   $this->content_of_section($name)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars((string)$this->get_data('yze_page_title'), ENT_QUOTES, 'UTF-8') ?> · Layout Section Demo</title>
    <?= $this->content_of_section('og_meta'); /* og/head meta 进 <head> */ ?>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
               background:#f5f6f8; margin:0; color:#222; }
        .ld-shell { background:#fff; max-width:920px; margin:24px auto;
                    border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.06);
                    overflow:hidden; }
        .ld-head { padding:14px 22px; background:#fafbfc; border-bottom:1px solid #ebeef5; }
        .ld-body { display:flex; min-height:60vh; }
        .ld-main { flex:1; padding:22px; }
        .ld-side { width:220px; padding:18px; background:#fafafa;
                   border-left:1px solid #ebeef5; }
        .ld-foot { padding:12px 22px; background:#fafbfc; border-top:1px solid #ebeef5;
                   font-size:12px; color:#888; }
        .ld-tag  { background:#ecf5ff; color:#409eff; border:1px solid #d9ecff;
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
<div class="ld-shell">
    <header class="ld-head">
        <?= $this->content_of_section('breadcrumb'); ?>
    </header>
    <div class="ld-body">
        <main class="ld-main">
            <?= $this->content_of_view(); ?>
        </main>
        <aside class="ld-side">
            <?= $this->content_of_section('sidebar'); ?>
        </aside>
    </div>
    <footer class="ld-foot">
        <span class="ld-tag">app/vendor/layouts/section-demo.layout.php</span>
        custom layout 在 3 处 echo 不同 section；对比默认 tpl.layout.php 只 echo 一个固定 section
    </footer>
</div>
</body>
</html>