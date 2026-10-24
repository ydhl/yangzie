<?php
namespace app\test;

/**
 * 响应格式 ⑰ · .jpg 视图 —— GD 动态生成产品卡图片（与 JSON 视图同源数据）。
 *
 * 同一份 set_view_data('product') 在 tpl / json / jpg 三种格式里都被消费：
 *   - tpl  → HTML 卡片       （view-formats_demo.tpl.php）
 *   - json → JSON 文本       （view-formats_demo.json.php）
 *   - jpg  → 本视图用 GD 把同一份数据画成图片
 *
 * 环境要求：php-gd 扩展。运行时若 extension_loaded('gd')===false，自动 readfile
 * docs/images/uml.jpg 作为兜底（保持旧版行为，避免 500）。
 *
 * 同 JSON 一样由 URI 末尾 .jpg 后缀触发：request.php:731 解析 __yze_resp_format__='jpg'
 * → controller.php:110 layout='jpg' → view.php:496-507 找本视图 → YZE_Layout 包
 * app/vendor/layouts/jpg.layout.php，里面设 header('Content-Type: image/jpeg')。
 */

if (!extension_loaded('gd')) {
    $fallback = YZE_INSTALL_PATH . 'docs' . DS . 'images' . DS . 'uml.jpg';
    if (is_file($fallback)) {
        readfile($fallback);
    }
    return;
}

$p = (array)($this->get_data('product') ?: []);

// —— 画布 ——
$w = 640; $h = 360;
$im = imagecreatetruecolor($w, $h);

// —— 配色（对齐演示页面 .pcard 风格：Element UI 调色板）——
$bg        = imagecolorallocate($im, 245, 247, 250);  // body bg
$card      = imagecolorallocate($im, 255, 255, 255);  // 卡片
$border    = imagecolorallocate($im, 220, 223, 230);
$ink       = imagecolorallocate($im, 48,  49,  51);   // 主文字
$muted     = imagecolorallocate($im, 144, 147, 153);  // 弱文字
$white     = imagecolorallocate($im, 255, 255, 255);
$accent    = imagecolorallocate($im, 64,  158, 255);  // #409eff
$accentBg  = imagecolorallocate($im, 236, 245, 255);
$danger    = imagecolorallocate($im, 245, 108, 108);  // #f56c6c
$dangerBg  = imagecolorallocate($im, 254, 240, 240);
$footBg    = imagecolorallocate($im, 250, 251, 252);

// —— 背景 + 卡片 ——
imagefilledrectangle($im, 0, 0, $w, $h, $bg);
imagefilledrectangle($im, 24, 24, $w - 24, $h - 24, $card);
imagerectangle($im, 24, 24, $w - 24, $h - 24, $border);

// —— 顶部色带 ——
imagefilledrectangle($im, 24, 24, $w - 24, 64, $accent);
imagestring($im, 5, 36, 38, 'Yangzie . Format Demo (GD dynamic)', $white);

// —— 产品名（与 tpl/json 的 .pcard h3 一致：name 字段）——
$name = (string)($p['name'] ?? '');
imagefilledrectangle($im, 36, 84, $w - 36, 124, $accentBg);
imagestring($im, 5, 48, 96, $name, $ink);

// —— 元信息（id / category / stock —— 与 tpl/.pcard .pmeta 同等字段）——
$id    = (string)($p['id'] ?? '');
$cat   = (string)($p['category'] ?? '');
$stock = (int)($p['stock'] ?? 0);
imagestring($im, 3, 36, 146,
    sprintf('ID: %s  |  Category: %s  |  Stock: %d', $id, $cat, $stock),
    $muted);

// —— 价格（与 tpl/.pcard .pprice 同等：price 字段 + 红色强调）——
$price = (float)($p['price'] ?? 0);
imagefilledrectangle($im, 36, 184, $w - 36, 264, $dangerBg);
imagestring($im, 5, 48, 212, sprintf('CNY  %s', number_format($price, 2)), $danger);

// —— footer ——（标识这张图是 GD 动态生成的）
imagefilledrectangle($im, 24, $h - 64, $w - 24, $h - 24, $footBg);
imagerectangle($im, 24, $h - 64, $w - 24, $h - 24, $border);
imagestring($im, 3, 36, $h - 44, 'view-formats_demo.jpg.php', $muted);
imagestring($im, 3, 36, $h - 30, 'GD-generated from $product (same as JSON view)', $muted);

// —— 输出 JPEG（Quality 88）——
imagejpeg($im, null, 88);
imagedestroy($im);