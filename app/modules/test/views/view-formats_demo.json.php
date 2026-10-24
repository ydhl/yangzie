<?php
namespace app\test;

/**
 * 响应格式 ⑰ · .json 视图 —— 同一 Action 的 JSON 格式输出。
 *
 * 被 URI 末尾的 .json 后缀触发：request.php:731 preg_match 解析 __yze_resp_format__=json
 * → controller.php:109-110 layout 自动设为 'json'
 * → view.php:496-507 check_view 找 view-formats_demo.json.php
 * → YZE_Layout 包 json.layout.php（只 echo content_of_view，不改 Content-Type）
 *
 * 与 tpl 视图用同一份 set_view_data('product', ...)。
 */
echo json_encode(
    $this->get_data('product') ?: [],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);