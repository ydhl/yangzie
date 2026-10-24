<?php
namespace app\test;

/**
 * Section 演示 ⑯ 的默认视图：被自定义 layout 'section-demo' 包起来。
 *
 * 本视图本身定义 3 个 section（和 master/section-product.tpl.php 同名同结构，
 * 但走 layout 而非 master view 这条路），由 section-demo.layout.php 在 3 处 echo。
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
    <h4>相关推荐（layout demo）</h4>
    <ul>
        <li>配套充电线</li>
        <li>配套收纳袋</li>
        <li>延保服务</li>
    </ul>
    <p class="sp-meta">侧栏内容由本视图贡献，由 layout 摆位</p>
</aside>
<?php $this->end_section('sidebar'); ?>

<?php $this->begin_section(); ?>
<meta property="og:title"    content="<?= htmlspecialchars((string)$this->get_data('title'), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type"     content="product">
<meta property="og:price"    content="<?= number_format((float)$this->get_data('price'), 2) ?>">
<meta property="og:currency" content="CNY">
<?php $this->end_section('og_meta'); ?>