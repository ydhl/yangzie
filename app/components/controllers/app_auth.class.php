<?php
/**
 * 验证用户是否登录及用户的身份对请求的资源是否有访问权限
 * 
 * @author leeboo
 *
 */
class App_Auth implements IAuth{
	public function do_auth(){
		//TODO 验证用户是否登录
		throw new Permission_Deny_Exception("没有访问权限");
	}
	public function get_request_aro_name(){
		//TODO 取得登录用户的aro（access request object）名，该名由开发者自己定义
	}
}
?>