<?php
/**
 * 该文件是YZE BD的核心文件，命令行下分别包含
 */
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include_once 'init.php';
include_once 'load.php';

if(file_exists(APP_PATH.'bd-hooks/jobs.php')){
	include APP_PATH.'bd-hooks/jobs.php';
}else{
	include YANGZIE.'/bd/jobs_demo.php';
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

function yze_run_bd()
{
	yze_bd_log("yze_run_bd");
	if(yze_bd_status()){
		yze_bd_log("can not start, YZE BD is running");
		exit();
	}
	$pid = pcntl_fork();
	if ($pid == -1) {
		yze_bd_log("could not fork YZE BD, abort.");
		exit();
	} else if ($pid) {// we are the parent
		yze_savepid($pid);
		exit();
	}


	//here is child

	// detatch from the controlling terminal
	if (posix_setsid() == -1) {
		yze_bd_log("could not detach from terminal. abort. ");
		exit();
	}

	// setup signal handlers
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGHUP, "sig_handler");

	// loop forever performing tasks
	while (1) {
		yze_bd_log("yze_run_bd ".date("Y-m-d H:i:s"));
		foreach ((array)yze_get_jobs() as $job){
			if(function_exists($job)){
				yze_bd_log("call  $job at ".date("Y-m-d H:i:s"));
				$callinfo = call_user_func($job);
				yze_bd_log("called:  $callinfo");
			}
		}
		sleep(function_exists("yze_get_sleep") ? yze_get_sleep() : 60);
	}
}


function yze_bd_log($msg)
{
	$dir = APP_PATH."logs/bd-log-".date("Y-m-d");
	$log = @fopen($dir,"a+");
	if(empty($log)){
		return false;
	}
	@fwrite($log,$msg.PHP_EOL);
	return true;
}
?>