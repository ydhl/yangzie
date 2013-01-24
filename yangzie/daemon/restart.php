<?php
namespace yangzie;
/**
 * 启动进程的脚本
 */

include 'cli.php';
$pid = yze_getpid();

if($pid){
	exec("kill -9 $pid");
	clear_pid();
}
yze_run_daemon();
?>