<?php
$js = $_GET["css"];
header("Content-Type:text/css");
if ($js) {
	$module = $_GET["module"];
	$js_info = explode(",", $js);
	foreach ($js_info as $js_name) {
		$file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR.$js_name.".css";
		echo file_get_contents($file);
	}
}
?>