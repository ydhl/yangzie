<?php

$css = $_GET["css"];
if ( ! $css ) return;

date_default_timezone_set('Asia/Chongqing');//时区

$module             = @$_GET["module"];
$css_info           = explode(",", $css);
$current_dir        = dirname(__FILE__);
$files              = array();
$last_modified_time = 0; //找出当前文件最后一次更新时间
$content_length     = 0;

foreach ($css_info as $css_name) {  
    if (empty($css_name)) continue;
    
    $file = $current_dir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR.$css_name;      
    if ( ! file_exists($file) ) {
        $file = $css_name[0] == "/" ? $current_dir . $css_name : $current_dir . DIRECTORY_SEPARATOR . $css_name;
    }
    if ( ! file_exists($file) ) continue;
    
    $files[]        = $file;
    
    $modified_time  = filemtime($file);
    if ($last_modified_time == 0 || $modified_time > $last_modified_time) {
        $last_modified_time = $modified_time;
    }
}

$key  = md5($css);
$eTag = $key . $last_modified_time;

header("Content-Type:text/css");
header("Cache-Control:must-revalidate");
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
header('Etag:' . $eTag);

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
    echo file_get_contents($file);
}     

?>