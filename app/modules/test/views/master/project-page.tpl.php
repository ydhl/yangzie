<?php
namespace app\test;

/**
 * 三级 master 嵌套 · Level 1：项目区公共壳（"公共部分A"）
 *
 * 这是 content 和 site-shell 之间的容器，对应 yangzie.md:854 的"公共部分A"。
 * 多个 action 可以共用本壳（参见 master_action2 / view-master_three_level.tpl.D 节），
 * 每个 action 只提供不同的 inner content，公共框架由这里统一渲染。
 *
 * 顶部 $this->master_view = "master/site-shell" 声明自己再被谁包起来；
 * $this->content_of_view() 输出被它包住的内层（content）渲染结果。
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
$this->master_view = "master/site-shell";
?>
<div class="mv-project">
    <div class="mv-project-head">
        <span class="mv-tag">项目区公共壳</span>
        <span class="mv-label">master/project-page</span>
    </div>
    <h2><?= htmlspecialchars((string)($this->get_data('page_title') ?: '项目区'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="mv-slot">
        <?= $this->content_of_view(); ?>
    </div>
    <p class="mv-meta">本壳被多个 action 共用 —— 共享"项目区"的导航/标题，差异只在 content 部分。</p>
</div>