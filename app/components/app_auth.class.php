<?php
class App_Auth implements IAuth{
	public function do_auth(){
		//TODO 验证用户是否登录
		throw new YZE_Permission_Deny_Exception("请在app/components/controllers/app_auth.class.php 的do_auth方法中实现系统的登录认证");
	}
	public function get_request_aro_name(){
		//TODO 取得登录用户的aro（access request object）名，该名由开发者自己定义
	}
}
?>