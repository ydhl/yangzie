<?php

namespace yangzie;

/**
 * 显示给定的视图（使用 error 布局）并停止执行
 *
 * @param YZE_View_Adapter $view       要显示的视图
 * @param YZE_Resource_Controller $controller 视图所属的控制器
 * @return void
 */
function yze_die(YZE_View_Adapter $view, YZE_Resource_Controller $controller) {
    $layout = new YZE_Layout ( "error", $view, $controller );
    $layout->output ();
    die ( 0 );
}


/**
 * 返回当前请求中保存的异常信息（由控制器 do_exception 设置）
 *
 * @author leeboo
 * @param string $begin_tag 每条错误消息的开始 html 标签
 * @param string $end_tag   每条错误消息的结束 html 标签
 * @return string|null 异常消息（含标签包裹），没有异常时返回 null
 */
function yze_controller_error($begin_tag = null, $end_tag = null) {
    if (($exception = YZE_Request::get_instance ()->get_exception (  ))) {
        return $begin_tag . $exception->getMessage() . $end_tag;
    }
}

/**
 * 在当前请求的 url 参数的基础上加上 args 参数，并与 url 一并返回
 *
 * @param string      $url    原始 url
 * @param array       $args   需要合并到 query string 的参数
 * @param string|null $format 输出格式后缀（如 json、xml），会替换 url 中已有的后缀
 * @return string 合并参数后的 url
 */
function yze_merge_query_string($url, $args = array(), $format=null){
    $path   = parse_url($url, PHP_URL_PATH);
    $query  = parse_url($url, PHP_URL_QUERY);
    $get    = array_merge($_GET, $args);
    if($query && parse_str($query, $newArgs)){
        $get    = array_merge($get, $newArgs);
    }

    if ($format){
        $url = (strrpos($url, ".")===false ? $url : substr($url, 0, strrpos($url, "."))).".{$format}";
    }

    return $url."?".http_build_query($get);
}

/**
 * 输出 js 加载 script 代码，工作路径是网站工作目录（public_html），
 * 所以 js 中如果有资源地址访问，请注意要调成相对于网站工作目录
 *
 * @param string $bundle  资源分组名，多个 bundle 用逗号分隔
 * @param string $version 版本号，用于缓存控制
 * @return void
 */
function yze_js_bundle($bundle, $version=""){
?>
<script type="text/javascript" src="/load.php?t=js&v=<?php echo $version?>&b=<?php echo $bundle?>"></script>
<?php
}

/**
 * 输出 css 加载 link 代码，工作路径是网站工作目录（public_html），
 * 所以 css 中如果有资源地址访问，请注意要调成相对于网站工作目录
 *
 * @param string $bundle  资源分组名，多个 bundle 用逗号分隔
 * @param string $version 版本号，用于缓存控制
 * @return void
 */
function yze_css_bundle($bundle, $version=""){
?>
<link rel="stylesheet" type="text/css" href="/load.php?t=css&v=<?php echo $version?>&b=<?php echo $bundle?>" />
<?php
}
/**
 * 输出 module 指定的 js bundle，bundle 在 __config__.php 中配置
 *
 * @param string $bundle  资源分组名，多个 bundle 用逗号分隔
 * @param string $version 版本号，用于缓存控制
 * @return void
 */
function yze_module_js_bundle($bundle="", $version=""){
	$request = YZE_Request::get_instance();
	?>
<script type="text/javascript" src="/load.php?t=js&v=<?php echo $version?>&m=<?php echo $request->module()?>&b=<?php echo $bundle?>"></script>
<?php
}
/**
 * 输出 module 指定的 css bundle，bundle 在 __config__.php 中配置
 *
 * @param string $bundle  资源分组名，多个 bundle 用逗号分隔
 * @param string $version 版本号，用于缓存控制
 * @return void
 */
function yze_module_css_bundle($bundle="", $version=""){
	$request = YZE_Request::get_instance();
?>
<link rel="stylesheet" type="text/css" href="/load.php?t=css&v=<?php echo $version?>&m=<?php echo $request->module()?>&b=<?php echo $bundle?>" />
<?php
}

/**
 * 返回当前访问模块下 html 模块里面 src 的访问地址
 *
 * @param string $src     资源的模块内相对路径
 * @param string $version 版本号，用于缓存控制
 * @return string 模块资源的完整访问 url
 */
function yze_module_asset_url($src, $version='') {
    $request = YZE_Request::get_instance();
    return "/load.php?t=asset&m=".$request->module()."&v={$version}&src=".urlencode($src);
}
?>
