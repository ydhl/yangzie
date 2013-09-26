<?php 

function log4web($log, $tag, $level="debug", $app="易点微信管理"){
	if(YZE_DEVELOP_MODE)return;
	$queue = new SaeTaskQueue("log");
	$queue->addTask("http://ydweixin.sinaapp.com/jobs/log",
			"access_token=8111c21e775a4400c0a5f19ff5fab99c&log=".urlencode($log)."&app={$app}&tag={$tag}&level={$level}");
	$queue->push();
}
?>