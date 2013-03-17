<?php
/**
 * 该文件是YZE Daemon的核心文件，在YZE框架中（load.php）
 */

function yze_start_daemon()
{
	if(PHP_OS!="WIN"){
		pclose(popen("php ".dirname(__FILE__)."/start.php","r"));
	}
}
function yze_stop_daemon()
{
	pclose(popen("php ".dirname(__FILE__)."/stop.php","r"));
}
function yze_restart_daemon()
{
	pclose(popen("php ".dirname(__FILE__)."/restart.php","r"));
}
/**
 * array(
 *  pid: 进程id
 *  time: 运行时间
 *  sleep:休眠时间
 * )
 */
function yze_daemon_status()
{
	$pid = yze_getpid();
	if(!$pid){
		return array();
	}
	$f = popen("ps -eo pid,tty,user,comm,etime | grep '{$pid} .* php'","r");
	$string = fgets($f);
	pclose($f);
	if(!$string){
		return array();
	}
	
	$cliinfo = preg_match_all("/[^\s]+/", trim($string), $matchs);
	$sleep = function_exists("yze_get_sleep") ? yze_get_sleep() : 60;
	return array('pid'=>$pid, "time"=>trim(@$matchs[0][4]),"sleep"=>$sleep);
}


function yze_getpid()
{
	$pid_file 	= YZE_APP_PATH."logs/pid";
	
	if(file_exists($pid_file)){
		$pid = trim(file_get_contents($pid_file));
		$f = popen("ps -e | grep '{$pid} .* php'","r");
		$string = fgets($f);
		pclose($f);
		if(!$string){
			return null;
		}
		return $pid;
	}
	return null;
	
}

function clear_pid()
{
	if(file_exists(YZE_APP_PATH."logs/pid")){
		@unlink(YZE_APP_PATH."logs/pid");
		return true;
	}
	return false;
}

function yze_savepid($pid)
{
	$f = fopen(YZE_APP_PATH."logs/pid", "w+");
	fwrite($f, $pid);
	fclose($f);
}

?>