<?php
namespace app\test;

/**
 * 三级 master 嵌套 · Level 0：内层内容
 *
 * 这一层只负责渲染"用户信息"，至于它被谁包起来、放在哪个项目区/站点里，
 * 由本模板顶部的 $this->master_view = "master/project-page" 声明——
 * 框架会先去模块 views、再去 app/vendor/views 找 project-page.tpl.php
 * （view.php:466-475 的解析顺序）。
 *
 * @var \yangzie\YZE_View_Adapter $this
 */
$this->master_view = "master/project-page";
?>
<div class="mv-content">
    <h3>内层内容 · content</h3>
    <p>用户：<strong><?= htmlspecialchars((string)$this->get_data('username'), ENT_QUOTES, 'UTF-8') ?></strong>
       （<?= htmlspecialchars((string)$this->get_data('role'), ENT_QUOTES, 'UTF-8') ?>）</p>
    <p>邮箱：<?= htmlspecialchars((string)$this->get_data('email'), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="mv-meta">本层只管"我是谁"，不关心"被谁包起来"，包它的视图由 master_view 声明。</p>
</div>