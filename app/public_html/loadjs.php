<?php
$js = $_GET["js"];
header('Content-type: text/javascript');
if ($js) {
	$module        = $_GET["module"];
	$js_info       = explode(",", $js);
	$current_dir   = dirname(__FILE__);
	foreach ($js_info as $js_name) {	    
		$file = $current_dir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR.$js_name;
		if ( ! file_exists($file) ) {
            $file = $js_name[0] == "/" ? $current_dir . $js_name : $current_dir . DIRECTORY_SEPARATOR . $js_name;
		}
        if ( file_exists($file) ) {
            echo file_get_contents($file);
        }
	}
}
?>