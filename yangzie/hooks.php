<?php
namespace yangzie;
/**
 * 该文件为系统提供hook机制，hook主要用于下面的地方：
 * 1.产生用户的行为消息（create event,update event,read event,delete event）
 * 4.数据输入处理（filter，validatet）
 * 5.数据输出处理（filter，validatet）
 * 6.事件通知（action）
 * 
 * hook提供入处理的方式是：
 * 1.在系统启动前加载所有的hook：加载每个目录下的hooks.php文件
 * 2.在上面的处理的地方调用hook
 * 
 * hook分为两类：
 * 1.改变数据的hook，叫filter
 * 1.验证数据的hook，叫validater
 * 2.事件通过hook，叫action
 * 
 * 如果hook成功，filter返回修改后的值，其它hook返回true；失败都抛出异常：Hook_Exception
 * @author liizii
 * @since 2009-9-1
 */

#定义架构使用的hook常量
define('YZE_ACTION_BEFORE_POST','do_before_post');
define('YZE_ACTION_BEFORE_GET','do_before_get');
define('YZE_ACTION_BEFORE_PUT','do_before_put');
define('YZE_ACTION_BEFORE_DELETE','do_before_delete');
define('YZE_ACTION_AFTER_POST','do_after_post');
define('YZE_ACTION_AFTER_GET','do_after_get');
define('YZE_ACTION_AFTER_PUT','do_after_put');
define('YZE_ACTION_AFTER_DELETE','do_after_delete');
define('YZE_ACTION_TRANSACTION_COMMIT','transaction_commit');
define('YZE_ACTION_UNRESUME_EXCEPTION','yze_action_unresume_exception');
define('YZE_ACTION_BEFORE_DO_EXCEPTION','yze_action_before_do_exception');
define('YZE_ACTION_CHECK_USER_HAS_LOGIN', 'yze_action_check_user_has_login');

define('YZE_FILTER_BEFORE_CHECK_REQUEST_TOKEN', 'before_check_request_token');

/**
 * 框架处理时出现了异常
 * 
 * @var unknown
 */
define('YZE_FILTER_YZE_EXCEPTION', 'yze_filter_yze_exception');
/**
 * 获取aro，回调的参数格式是：array('aro'=>'/');
 * @var unknown
 */
define('YZE_FILTER_GET_USER_ARO_NAME', 'yze_filter_get_user_aro_name');


/**
 * 解析地址得到请求url，如module/controller/var
 * uri过滤，传入uri分离后的数据或者就是uri字符串本身
 */
define('YZE_HOOK_FILTER_URI','filter_uri');

final class YZE_Hook{
	private $listeners = array();
	private static $instance;
	private function __construct(){}

	/**
	 * 
	 *
	 * @return YZE_Hook
	 */
	public static function the_hook(){
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
	/**
	 * 增加hook
	 *
	 */
	public function add_hook($event,$func_name,$object=null){
		$this->listeners[$event][] = array("function"=>$func_name,"object"=>$object);
	}
	
	public function do_filter($filter_name, $data){

		if(!$this->has_hook($filter_name))return $data;
		foreach($this->listeners[$filter_name] as $listeners){
			if(is_object($listeners['object'])){
//				TODO call object method
			}else{
				$data = call_user_func($listeners['function'],$data);
			}
		}
		return $data;
	}

	public function do_action($event,$args = null){
		if(!$this->has_hook($event))return;
		foreach(@$this->listeners[$event] as $listeners){
			if(is_object($listeners['object'])){
//				TODO call object method
			}else{
				//use call_user_func
				call_user_func($listeners['function'],$args);
			}
		}
	}
	
	public function has_hook($filter_name){
		return @$this->listeners[$filter_name];
	}
}

///////////////////function //////////////////

function do_action($action_name, $args = null){
	$hook = YZE_Hook::the_hook();
	$hook->do_action($action_name,$args);
}
/**
 * 
 * 调用注册的过滤器回调，如果有多个回调，则数据依次经过每个回调后返回
 * 
 * @param unknown_type $filter_name
 * @param unknown_type $filter_data
 * 
 * @return array;
 */
function do_filter($filter_name, $filter_data){
	$hook = YZE_Hook::the_hook();
	return $hook->do_filter($filter_name,$filter_data);
}
/**
 * 对数据进入验证，正确则什么也不做，错误抛出Validate_Failed异常
 * 
 * @param $filter_name 验证器名字
 * @param $filter_data 验证的数据
 * @return void
 * @author liizii
 * @since 2009-12-21
 */
function do_validate($filter_name, $filter_data){
	$hook = YZE_Hook::the_hook();
	return $hook->do_filter($filter_name,$filter_data);
}
?>
