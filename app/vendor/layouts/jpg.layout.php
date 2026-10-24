<?php
namespace yangzie;

/**
 * jpg 格式专用布局 —— 仅做两件事：
 *   1. 声明 Content-Type: image/jpeg（让浏览器按图片渲染而不是当成文本）
 *   2. 原样 echo view 输出的二进制字节
 *
 * 对比 tpl.layout.php：tpl 布局要给 html 加 <head><body>、调 css/js bundle 等；
 * jpg 布局"什么都不包装" —— 二进制前后任何多余字符都会破坏图片。
 *
 * 没有 PHP 结束标签，避免任何意外空白被拼进响应体。
 */
header('Content-Type: image/jpeg');
echo $this->content_of_view();