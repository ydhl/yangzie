<?php
/**
 * PHP 内置开发服务器的 router 脚本（生产环境用 Apache/nginx 时用不到）。
 *
 * 为什么需要它：`php -S localhost:8080` 不带 router 时，内置服务器把带扩展名的
 * 请求（如 /test/view/xxx.json）当静态文件，不存在就直接 404，不会交给 index.php；
 * 而框架的"视图格式"功能恰恰依赖 URI 尾部的 .json/.pdf 等后缀
 * （yangzie/request.php:731 解析 __yze_resp_format__）。
 *
 * 注意文件名：不能叫 router.php / view.php 等框架文件名 ——
 * yangzie/init.php 里的 require_once 'router.php' 是相对路径，
 * include_path 的 "."（即本目录）会优先命中本目录同名文件，劫持框架类的加载。
 *
 * 启动方式：cd app/public_html && php -S localhost:8080 dev-server.php
 */
$docroot = realpath(__DIR__);
$uri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file    = realpath(__DIR__ . $uri);

// 真实存在的静态文件（js/css/favicon...）交给内置服务器直接输出
if ($file !== false && strpos($file, $docroot . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
    return false;
}
// 其余请求（含 .json 等后缀）全部交给框架入口
require __DIR__ . '/index.php';
