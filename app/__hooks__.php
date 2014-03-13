<?php
/**
 * 定义一些系统回调，需要定义的回调有：
 * YZE_FILTER_GET_USER_ARO_NAME: 返回用户的aro，默认为/
 * YZE_ACTION_CHECK_USER_HAS_LOGIN: 判断用户是否登录，没有登录请抛出异常
 * YZE_FILTER_YZE_EXCEPTION: 扬子鳄处理过程中出现的异常回调
 * 
 * @author leeboo
 *
 */
namespace app;
use yangzie\YZE_Redirect;

use \yangzie\YZE_Request;
use \yangzie\YZE_Hook;
use \yangzie\YZE_Need_Signin_Exception;
use \yangzie\YZE_Session_Context;

function check_exception($datas){
	//如果array("exception"=>$e, "controller"=>$controller, "response"=>$response)
	// 把signin替换成自己的登录url
	if(is_a($datas['exception'], "\\yangzie\\YZE_Need_Signin_Exception")){
		$datas['response'] = new YZE_Redirect("/signin", $datas['controller']);
	}
	return $datas;
}

function do_auth($datas){
	//这里判断用户是否登录，并把用户登录的标志，如uid，或者用户对象设置在datas['user']中
	//$datas['user'] = $user;//datas['user']有值表示登录成功
	return $datas;
}

YZE_Hook::the_hook()->add_hook(YZE_FILTER_YZE_EXCEPTION, "\\app\\check_exception");
YZE_Hook::the_hook()->add_hook(YZE_ACTION_CHECK_USER_HAS_LOGIN, "\\app\\do_auth");
?>