<?php

$js = $_GET["js"];
if ( ! $js ) return;

date_default_timezone_set('Asia/Chongqing');//时区

$module             = @$_GET["module"];
$js_info           = explode(",", $js);
$current_dir        = dirname(__FILE__);
$files              = array();
$last_modified_time = 0; //找出当前文件最后一次更新时间
$content_length     = 0;

foreach ($js_info as $js_name) {  
    if (empty($js_name)) continue;
    
    $file = $current_dir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR.$js_name;      
    if ( ! file_exists($file) ) {
        $file = $js_name[0] == "/" ? $current_dir . $js_name : $current_dir . DIRECTORY_SEPARATOR . $js_name;
    }
    if ( ! file_exists($file) ) continue;
    
    $files[]        = $file;
    
    $modified_time  = filemtime($file);
    if ($last_modified_time == 0 || $modified_time > $last_modified_time) {
        $last_modified_time = $modified_time;
    }
}
//var_dump(date("Y-m-d H:i:s",$last_modified_time));
$key  = md5($js);
$eTag = $key . $last_modified_time;

header('Content-type: text/javascript');
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