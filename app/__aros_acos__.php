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
 * 定义需要权限控制的ACOs
 */
function yze_get_acos()
{
	return array(
		'/admin/*'				=> "系统设置",
		'/admin/role/*'			=> "角色查看",
		'/admin/add_role/*'		=> "角色管理",
		'/admin/signatures/*'	=> "电子章查看",
		'/admin/add_signature/*'=> "电子章管理",
		
		'/attendant/attendant/*'	=> "考勤查看",
		'/attendant/checkout/*'		=> "签到",
		
		'/salary/salary_items/*'	=> "查看薪资项",
		'/salary/add_salary_item/*'	=> "薪资配置",
		'/salary/salary_setting/*'	=> "为员工设置薪资",
		'/salary/employee_salary/*'	=> "查看员工工资表",
		'/salary/salary/*'			=> "查看所有工资表",
		
		'/work/add_work_group/*'	=> "班组维护",
		'/work/add_work_segment/*'	=> "班次维护",
		'/work/scheme/*'			=> "排班",
		'/work/work/*'				=> "查看排班计划",
		'/work/work_group/*'		=> "查看班组",
		'/work/work_segment/*'		=> "查看班次",
		
		'/user/add_department/*'	=> "部门维护",
		'/user/add_duty/*'			=> "职务维护",
		'/user/add_employee/*'		=> "员工维护",
		'/user/add_employee_extinfo/*'		=> "员工属性维护",
		'/user/add_job/*'			=> "员工工作经历维护",
		'/user/add_social/*'		=> "员工社会关系维护",
		'/user/add_study/*'			=> "员工学习经历维护",
		'/user/add_train/*'			=> "员工培训经历维护",
		'/user/apply/*'				=> "查看申请列表",
		'/user/duty/*'				=> "查看职务列表",
		'/user/employee/*'			=> "查看员工信息",
		'/user/employees/*'			=> "查看员工列表",
		'/user/hire_setting/*'		=> "员工聘用维护",
		'/user/signin_setting/*'	=> "员工登录维护",
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

/**
 * 定义自己的访问权限树，格式如下：
 * $__acos_aros__["/aro_name"] = array(
 * "allow"=>"*",
 * "deny"=>array(
 * 	"aco_name"=>"*",
 * )
 * );
 */
?>