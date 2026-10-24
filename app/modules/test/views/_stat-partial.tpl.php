<?php
/**
 * 模块内局部模板 —— 指标小面板（演示"传统 include"重用方式）。
 *
 * 只建议同目录（modules/test/views）内的视图 include 复用；
 * 传值方式就是最传统的：靠 include 之前定义好的局部变量，
 * 没有 $this / get_data，也无人帮你处理视图格式（与 YZE_Simple_View 组件的差别）。
 *
 * 变量约定：
 *   @var array $stat ['label' => 标签, 'value' => 数值, 'delta' => 环比, 'up' => bool]
 */
$label = (string)($stat['label'] ?? '');
$value = (string)($stat['value'] ?? '');
$delta = (string)($stat['delta'] ?? '');
$up    = (bool)($stat['up'] ?? true);
?>
<div class="stat">
    <div class="s-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="s-value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="s-delta <?= $up ? 'up' : 'down' ?>"><?= $up ? '▲' : '▼' ?> <?= htmlspecialchars($delta, ENT_QUOTES, 'UTF-8') ?></div>
</div>
