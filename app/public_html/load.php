<?php
use app\App_Module;
use yangzie\YZE_Request;
require 'init.php';

$type = strtolower($_GET["t"]);
if( ! in_array($type, array("js","css")))return;

$bundle = $_GET["b"];//load static bundle
$module = $_GET["m"];//load module bundle
if ( ! $bundle && ! $module ) return;

date_default_timezone_set('Asia/Chongqing');
$bundle_files = array();

if($module){
	$temp = $type=="js" ? YZE_Request::jsBundle($module) : YZE_Request::cssBundle($module);
	foreach(@$temp as $file){
		$bundle_files[] = "/modules/{$module}{$file}";
	}
	
}else{
	$app = new App_Module();
	foreach (explode(",", $bundle) as $bundle) {
		if (empty($bundle)) continue;
		$temp = $type=="js" ? $app->js_bundle($bundle) : $app->css_bundle($bundle);
		if( ! $temp)continue;
		
		$bundle_files = array_merge($bundle_files, $temp);
	}
}

$current_dir        = dirname(__FILE__);
$last_modified_time = 0; //找出当前文件最后一次更新时间
$files              = array();

foreach ($bundle_files as $bundle_file) {  
    if (empty($bundle_file)) continue;
    
    $file = $current_dir . $bundle_file;
    
    if ( ! file_exists($file) ) continue;
    
    $files[]    = $file;
    
    $modified_time  = filemtime($file);
    if ($last_modified_time == 0 || $modified_time > $last_modified_time) {
        $last_modified_time = $modified_time;
    }
}

$key  = md5($bundle.$module);
$eTag = $key . $last_modified_time;
if( "css" == $type){
	header("Content-Type: text/css");
}else{
	header('Content-type: text/javascript');
}
header("Cache-Control:must-revalidate");
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
header('Etag:' . $eTag);
header('Expires:' . gmdate('D, d M Y H:i:s', time()+1800).' GMT');//30分钟内客户端不用在做请求

if (@$_SERVER['HTTP_IF_NONE_MATCH'] == $eTag) {
    header("HTTP/1.0 304 Not Modified");
    exit(0);
}

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $browser_time = strtotime(preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']));
    if ($browser_time >= $last_modified_time) {
        header("HTTP/1.0 304 Not Modified");
        exit(0);
    }
}


foreach ($files as $file) {
	$path = realpath($file);
	if(!file_exists($path))continue;
	if($current_dir != substr($path, 0, strlen($current_dir))) continue;//只允许读取app目录下的
	$path_info = pathinfo($path);
	if( strcasecmp( $path_info['extension'], $type) === 0) echo file_get_contents($file);
}
?>