<?php

/**
 * 定义一些系统回调，需要定义的回调有：
 * YZE_FILTER_GET_USER_ARO_NAME: 返回用户的aro，默认为/
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
use app\sp\Service_Provider_Model;

YZE_Hook::add_hook ( YZE_HOOK_GET_LOGIN_USER, function  ( $datas ) {
	$loginUser = $_SESSION [ 'admin' ];
	if( ! $loginUser)return null;
	
	return $loginUser;
} );

YZE_Hook::add_hook ( YZE_HOOK_SET_LOGIN_USER, function  ( $data ) {
	$_SESSION [ 'admin' ] = $data;
} );

YZE_Hook::add_hook ( YZE_FILTER_GET_USER_ARO_NAME, function  ( $data ) {
	if ( !@$_SESSION [ 'admin' ] )return "/";
	return "TODO your ARO NAME";
} );


YZE_Hook::add_hook(YZE_FILTER_YZE_EXCEPTION, function ($datas){
    //如果array("exception"=>$e, "controller"=>$controller, "response"=>$response)
    // 把signin替换成自己的登录url

    $request = YZE_Request::get_instance();
    if(! is_a($datas['exception'], "\\yangzie\\YZE_Need_Signin_Exception")) return $datas;
    
    $datas['response'] = new YZE_Redirect("/signin", $datas['controller']);
    if($request->isInWeixin()){
        $datas['response'] = new YZE_Redirect("/signin", $datas['controller']);
    }
    return $datas;
});
?>