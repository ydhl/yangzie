<?php
$js = $_GET["js"];
header('Content-type: text/javascript');
if ($js) {
	$module = $_GET["module"];
	$js_info = explode(",", $js);
	foreach ($js_info as $js_name) {
		$file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR.$js_name.".js";
		echo file_get_contents($file);
	}
}
?>