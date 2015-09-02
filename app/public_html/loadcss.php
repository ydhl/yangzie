<?php
$css = $_GET["css"];
header("Content-Type:text/css");
if ($css) {
	$module = $_GET["module"];
	$css_info = explode(",", $css);
	
    $current_dir   = dirname(__FILE__);
    foreach ($css_info as $css_name) {  
        $file = $current_dir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR.$css_name;      
        if ( ! file_exists($file) ) {
            $file = $css_name[0] == "/" ? $current_dir . $css_name : $current_dir . DIRECTORY_SEPARATOR . $css_name;
        }
        if ( file_exists($file) ) {
            echo file_get_contents($file);
        }
    }
}
?>