<?php
interface IAuth{
	/**
	 * 身份认证方法接口，验证用户是否登录，验证用户是否有访问的权限
	 * 验证成功返回true，失败返回应用自定义的异常
	 */
	public function do_auth();
	
	/**
	 * 取得当前请求的访问请求对象名字，比如当前的登录用户，
	 * 它用户系统中所有需要权限认证的地方，这些地方判断这个方法返回的内容是否有访问权限
	 */
	public function get_request_aro_name();
}
?>