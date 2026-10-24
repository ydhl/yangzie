<?php
namespace app\test;

/**
 * Section · Level 0：产品详情内容。
 *
 * 本视图"管自己"的输出：除了正文（content_of_view），
 * 还用 begin_section / end_section 定义了 3 个 section：
 *   - breadcrumb : 面包屑，放在 master/layout 的 <header>
 *   - sidebar    : 侧栏，放在 master/layout 的 <aside>
 *   - og_meta    : og/head meta，放在 master/layout 的 <head>
 *
 * 这些 section 在哪里 echo、怎么摆，由容器（master view 或 layout）决定；
 * 本视图只负责"我能贡献哪些片段"（yangzie.md:875）。
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
?>
<article class="sp-article">
    <h1><?= htmlspecialchars((string)$this->get_data('title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="sp-meta">
        价格 ¥<?= number_format((float)$this->get_data('price'), 2) ?>
        &nbsp;·&nbsp; 库存 <?= (int)$this->get_data('stock') ?>
    </p>
    <p><?= htmlspecialchars((string)$this->get_data('desc'), ENT_QUOTES, 'UTF-8') ?></p>
</article>

<?php $this->begin_section(); ?>
<nav class="sp-breadcrumb">
    <a href="javascript:void(0)" onclick="return false">首页</a>
    <span class="sp-sep">/</span>
    <a href="javascript:void(0)" onclick="return false">产品</a>
    <span class="sp-sep">/</span>
    <span><?= htmlspecialchars((string)$this->get_data('title'), ENT_QUOTES, 'UTF-8') ?></span>
</nav>
<?php $this->end_section('breadcrumb'); ?>

<?php $this->begin_section(); ?>
<aside class="sp-sidebar">
    <h4>相关推荐</h4>
    <ul>
        <li>配套充电线</li>
        <li>配套收纳袋</li>
        <li>延保服务</li>
    </ul>
    <p class="sp-meta">侧栏内容由 Action（这个 view）决定</p>
</aside>
<?php $this->end_section('sidebar'); ?>

<?php $this->begin_section(); ?>
<meta property="og:title"    content="<?= htmlspecialchars((string)$this->get_data('title'), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type"     content="product">
<meta property="og:price"    content="<?= number_format((float)$this->get_data('price'), 2) ?>">
<meta property="og:currency" content="CNY">
<?php $this->end_section('og_meta'); ?>