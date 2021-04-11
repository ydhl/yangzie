<?php

namespace yangzie;

/**
 * 显示给定的视图并停止执行
 *
 * @param YZE_View_Adapter $view
 * @param YZE_Resource_Controller $controller
 */
function yze_die(YZE_View_Adapter $view, YZE_Resource_Controller $controller) {
    $layout = new YZE_Layout ( "error", $view, $controller );
    $layout->output ();
    die ( 0 );
}


/**
 * 返回当前控制器的出错信息
 *
 * @author leeboo
 *
 * @param $begin_tag 每条错误消息的开始html标签
 * @param $end_tag 每条错误消息的结束html标签
 *
 * @return string
 *
 */
function yze_controller_error($begin_tag = null, $end_tag = null) {
    if (($exception = YZE_Request::get_instance ()->getException (  ))) {
        return $begin_tag . $exception->getMessage() . $end_tag;
    }
}

/**
 * 在当前请求的url参数的基础上加上args参数，并于url一并返回
 *
 * @param unknown $url
 * @param unknown $args
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
 * 输出css加载html, 工作路径是load文件
 * 所在的目录，这在css文件中会访问相对路径下的font，img时需要注意, yze会对../和./的路径修改成正确的路径
 * @param string $bundle, 多个bundle用,号分隔
 * @param string version 版本
 */
function yze_js_bundle($bundle, $version=""){
?>
<script type="text/javascript" src="/load.php?t=js&v=<?php echo $version?>&b=<?php echo $bundle?>"></script>
<?php
}

/**
 * 输出css加载html, 工作路径是load文件
 * 所在的目录，这在css文件中会访问相对路径下的font，img时需要注意, yze会对../和./的路径修改成正确的路径
 * @param string $bundle, 多个bundle用,号分隔
 * @param string version 版本
 */
function yze_css_bundle($bundle, $version=""){
?>
<link rel="stylesheet" type="text/css" href="/load.php?t=css&v=<?php echo $version?>&b=<?php echo $bundle?>" />
<?php
}
/**
 * 输出module指定的js bundle，通常在controller中通过request->addJSBundle()加入的
 */
function yze_module_js_bundle($version=""){
	$request = YZE_Request::get_instance();
	?>
<script type="text/javascript" src="/load.php?t=js&v=<?php echo $version?>&m=<?php echo $request->module()?>"></script>
<?php
}
/**
 * 输出module指定的css bundle，通常在controller中通过request->addCSSBundle()加入的
 */
function yze_module_css_bundle($version=""){
	$request = YZE_Request::get_instance();
?>
<link rel="stylesheet" type="text/css" href="/load.php?t=css&v=<?php echo $version?>&m=<?php echo $request->module()?>" />
<?php
}
?>
