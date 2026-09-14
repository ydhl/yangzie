<?php

/**
 * 定义一些系统回调，需要定义的回调有：
 * YZE_HOOK_GET_USER_ARO_NAME: 返回用户的aro，默认为/
 * YZE_HOOK_YZE_EXCEPTION: 扬子鳄处理过程中出现的异常回调
 *
 * @author leeboo
 *
 */
namespace app;

use yangzie\YZE_FatalException;
use yangzie\YZE_Redirect;
use \yangzie\YZE_Request;
use \yangzie\YZE_Hook;

// 返回当前登录的用户信息，具体返回什么，由开发者定义，可以是用户对象，也可以是用户的标识ID，
// 通过YZE_HOOK_GET_LOGIN_USER设置
YZE_Hook::add_hook ( YZE_HOOK_GET_LOGIN_USER, function  () {
	// ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
	$loginUser = $_SESSION['admin'] ?? null;
	if( ! $loginUser)return null;

	return $loginUser;
} );
// 设置当前登录的用户信息，具体是什么，由开发者定义，可以是用户对象，也可以是用户的标识ID；
// 设置后通过YZE_HOOK_GET_LOGIN_USER获取
YZE_Hook::add_hook ( YZE_HOOK_SET_LOGIN_USER, function  ( &$data ) {
	$_SESSION [ 'admin' ] = $data;
} );

YZE_Hook::add_hook ( YZE_HOOK_NEED_SIGNIN, function  (&$datas) {
//    把下面的/signin修改成你实际的登录地址; 并删除掉该throw new 语句。
	throw new YZE_FatalException('在app/hooks/auth.php中修改YZE_HOOK_NEED_SIGNIN hook替换实际的登录地址');
//    $datas['response'] = new YZE_Redirect("/signin", $datas['controller']);

} );

// 传入的$datas格式为["exception"=>$e, "controller"=>$controller, "response"=>$response]
YZE_Hook::add_hook(YZE_HOOK_YZE_EXCEPTION, function (&$datas){
    $request = YZE_Request::get_instance();

    return $datas;
});

?>
