<?php
/**
 * 用户分组的形式是：
 *  /------组根
 *  大组名/
 *  小组名/
 *  用户id
 *
 *  aco分为action ACOs与View ACOs，其标识是：
 *  /module/resouce_controller/request method(get|post|get|put|*)，如
 *  /module/resouce_controller/*			所有的请求
 *  /module/resouce_controller/get			get请求
 *  /module/resouce_controller/post			post请求
 *  /module/resouce_controller/(post|get)	get,post请求
 */


/**
 * 定义需要权限控制的ACOs及其说明
 */
function yze_get_acos()
{
	return array(
		'/orders/*'	=> "订单设置"
	);
}

function yze_get_aco_desc($aconame)
{
	foreach ((array)yze_get_acos() as $aco=> $desc) {
		$aco = strtr($aco, array("*"=>"(get|post|put|delete)"));
		if (preg_match("{^".$aco."}", $aconame)){
			return $desc;
		}
	}
	return '';
}

function yze_get_acos_aros(){
	return
	array("/" => array(
	    "deny"=>"*",//默认任何aro禁止访问所有aco
	    "allow"=>array()
	)
	);
}
?>