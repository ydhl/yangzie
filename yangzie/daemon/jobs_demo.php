<?php
/**
 * 
 * 返回后台进程中运行的函数，返回函数名，后台进程将每隔yze_get_sleep seconds后依次运行这些函数
 * 您可以在这些函数中编写后台运行的代码
 * 
 */


function yze_get_jobs() {
	return "hello_yze";
}

function hello_yze(){
	yze_daemon_log("hello YZE Daemon ".date("Y-m-d H:i:s"));
}

function yze_get_sleep()
{
	60;
}
?>