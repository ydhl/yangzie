<?php
namespace yangzie;
/**
 * 该文件是YZE BD的核心文件，命令行下分别包含
 */
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include_once 'init.php';
include_once 'load.php';

//TODO 可能有多个jobs文件
if(file_exists(APP_PATH.'daemon-jobs/jobs.php')){
	include APP_PATH.'daemon-jobs/jobs.php';
}else{
	include YANGZIE.'/daemon/jobs_demo.php';
}

declare(ticks=1);

function sig_handler($signo)
{

	switch ($signo) {
		case SIGTERM:
			// handle shutdown tasks
			exit;
			break;
		case SIGHUP:
			// handle restart tasks
			break;
		default:
			// handle all other signals
	}

}

function yze_run_daemon()
{
	yze_daemon_log("yze_run_daemon");
	if(yze_daemon_status()){
		yze_daemon_log("can not start, YZE Daemon is running");
		exit();
	}
	$pid = pcntl_fork();
	if ($pid == -1) {
		yze_daemon_log("could not fork YZE Daemon, abort.");
		exit();
	} else if ($pid) {// we are the parent
		yze_savepid($pid);
		exit();
	}


	//here is child

	// detatch from the controlling terminal
	if (posix_setsid() == -1) {
		yze_daemon_log("could not detach from terminal. abort. ");
		exit();
	}

	// setup signal handlers
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGHUP, "sig_handler");

	// loop forever performing tasks
	while (1) {
		yze_daemon_log("yze_run_daemon ".date("Y-m-d H:i:s"));
		foreach ((array)yze_get_jobs() as $job){
			if(function_exists($job)){
				yze_daemon_log("call  $job at ".date("Y-m-d H:i:s"));
				$callinfo = call_user_func($job);
				yze_daemon_log("called:  $callinfo");
			}
		}
		sleep(function_exists("yze_get_sleep") ? yze_get_sleep() : 60);
	}
}


function yze_daemon_log($msg)
{
	$dir = APP_PATH."logs/daemon-log-".date("Y-m-d");
	$log = @fopen($dir,"a+");
	if(empty($log)){
		return false;
	}
	@fwrite($log,$msg.PHP_EOL);
	return true;
}
?>