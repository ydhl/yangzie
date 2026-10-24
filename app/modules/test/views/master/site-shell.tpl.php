<?php
namespace app\test;

/**
 * 三级 master 嵌套 · Level 2：站点级公共壳（"公共部分B"）
 *
 * 顶层包装，对应 yangzie.md:855 的"公共部分B"。本模板不再声明 master_view，
 * 是整条嵌套链的最外层 —— 框架在 check_master 后不会再向上一层包装。
 *
 * 这里同时挂上整条链的 .mv-* 样式：直接访问 /test/view/master_three_level 时
 * 只有 site-shell.tpl.php 是顶层模板，不会再有人注入 <style>；
 * 解释页 /test/view/master_nesting 用 get_output() 内嵌链时也会带这些样式。
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
$nav_items = (array)$this->get_data('nav_items') ?: ['首页', '产品', '关于', '帮助'];
$site_title = (string)($this->get_data('site_title') ?: 'Yangzie 示例站点');
?>
<style>
    /* 整条 master 链共用的样式 —— 由最外层 site-shell 注入 */
    .mv-site-head { display:flex; align-items:center; justify-content:space-between;
                    background:#2c3e50; color:#fff; padding:14px 22px; }
    .mv-site-brand { font-size:16px; font-weight:600; }
    .mv-site-nav a { color:#cfd8dc; margin-left:18px; text-decoration:none; font-size:13px; }
    .mv-site-body { display:flex; min-height:300px; }
    .mv-site-side { width:200px; background:#fafafa; padding:18px; border-right:1px solid #ebeef5; }
    .mv-side-title { font-size:13px; font-weight:600; color:#606266; margin-bottom:8px; }
    .mv-side-block { background:#fff; border:1px solid #ebeef5; border-radius:6px; padding:12px; }
    .mv-site-main { flex:1; padding:22px; background:#fff; }
    .mv-site-foot { background:#fafbfc; padding:12px 22px; border-top:1px solid #ebeef5;
                    font-size:12px; color:#888; display:flex; align-items:center; gap:8px; }
    .mv-project { border:2px solid #e6a23c; border-radius:6px; padding:16px 18px; background:#fffdf6; }
    .mv-project-head { display:flex; align-items:center; margin-bottom:8px; gap:8px; }
    .mv-project-head .mv-tag { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8;
                                 padding:2px 8px; border-radius:10px; font-size:11px; }
    .mv-project-head .mv-label { color:#909399; font-size:11px; font-family:Menlo, monospace; }
    .mv-project h2 { font-size:16px; margin:0 0 10px; color:#222; }
    .mv-slot { background:#fff; border-radius:4px; }
    .mv-content { border:2px solid #67c23a; border-radius:6px; padding:14px 16px;
                  background:#fcfffa; }
    .mv-content h3 { margin:0 0 8px; font-size:14px; color:#67c23a; }
    .mv-content p { margin:4px 0; font-size:13px; color:#606266; }
    .mv-content .mv-meta { font-size:11px; color:#909399; margin-top:10px; }
    .mv-tag { background:#ecf5ff; color:#409eff; border:1px solid #d9ecff;
              padding:2px 8px; border-radius:10px; font-size:11px; margin-right:6px; }
    .mv-label { color:#909399; font-size:11px; font-family:Menlo, monospace; }
    .mv-meta { font-size:11px; color:#909399; margin:0; }
</style>
<header class="mv-site-head">
    <div class="mv-site-brand"><?= htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8') ?></div>
    <nav class="mv-site-nav">
        <?php foreach ($nav_items as $item): ?>
            <a href="javascript:void(0)" onclick="return false"><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<div class="mv-site-body">
    <aside class="mv-site-side">
        <div class="mv-side-block">
            <div class="mv-side-title">站点侧栏</div>
            <p class="mv-meta">站点级的导航/统计/广告位</p>
        </div>
    </aside>
    <main class="mv-site-main">
        <?= $this->content_of_view(); ?>
    </main>
</div>
<footer class="mv-site-foot">
    <span class="mv-tag">站点级公共壳</span>
    <span class="mv-label">master/site-shell</span>
    <span class="mv-meta">整条嵌套链最外层，本模板不声明 master_view</span>
</footer>