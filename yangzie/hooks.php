<?php

namespace yangzie;

/**
 * 该文件为系统提供hook机制，hook主要用于下面的地方：
 * 1.数据输入,输出处理
 * 2.事件通知
 * 3.模块之间功能调用
 *
 * hook提供入处理的方式是：
 * 1.在系统启动前加载所有的hook 函数：加载每个hooks目录下的文件
 * 2.通过do_hook($hook_name, $args)调用hook，$args会传入hook函数
 * 3.对于注册到统一hook的多个函数，每一个函数返回的$args会进入下一个hook 函数
 *
 * 注册的hook函数接受一个数组参数，函数的返回值也是通过参数返回
 * 
 * @author liizii
 * @since 2009-9-1
 */

// 定义架构使用的hook常量
define ( 'YZE_ACTION_BEFORE_POST', 'do_before_post' );
define ( 'YZE_ACTION_BEFORE_GET', 'do_before_get' );
define ( 'YZE_ACTION_BEFORE_PUT', 'do_before_put' );
define ( 'YZE_ACTION_BEFORE_DELETE', 'do_before_delete' );
define ( 'YZE_ACTION_AFTER_POST', 'do_after_post' );
define ( 'YZE_ACTION_AFTER_GET', 'do_after_get' );
define ( 'YZE_ACTION_AFTER_PUT', 'do_after_put' );
define ( 'YZE_ACTION_AFTER_DELETE', 'do_after_delete' );
define ( 'YZE_ACTION_TRANSACTION_COMMIT', 'transaction_commit' );
define ( 'YZE_ACTION_UNRESUME_EXCEPTION', 'yze_action_unresume_exception' );
define ( 'YZE_ACTION_BEFORE_DO_EXCEPTION', 'yze_action_before_do_exception' );

define ( 'YZE_FILTER_BEFORE_CHECK_REQUEST_TOKEN', 'before_check_request_token' );

/**
 * 框架处理时出现了异常
 *
 * @var unknown
 */
define ( 'YZE_FILTER_YZE_EXCEPTION', 'yze_filter_yze_exception' );
/**
 * 获取aro，回调的参数格式是：'/';
 * 
 * @var unknown
 */
define ( 'YZE_FILTER_GET_USER_ARO_NAME', 'yze_filter_get_user_aro_name' );

/**
 * 解析地址得到请求url，如module/controller/var
 * uri过滤，传入uri分离后的数据或者就是uri字符串本身
 */
define ( 'YZE_HOOK_FILTER_URI', 'filter_uri' );
/**
 * 取得登录的用户，由YZE_HOOK_SET_LOGIN_USER设置
 * @var unknown
 */
define ( 'YZE_HOOK_GET_LOGIN_USER', 'YZE_HOOK_GET_LOGIN_USER' );
/**
 * 设置登录的用户，比如设置在回话中，参数是用户信息
 * @var unknown
 */
define ( 'YZE_HOOK_SET_LOGIN_USER', 'YZE_HOOK_SET_LOGIN_USER' );
final class YZE_Hook {
    private static $listeners = array ();
    private static $currModule;
    /**
     * 增加hook
     */
    public static function add_hook($event, $func_name, $object = null) {
        //include_hooks中已经知道模块名了
        self::$listeners [$event] [self::$currModule] [] = array (
                "function" => $func_name,
                "object" => $object 
        );
    }
    private static function _call_user_func($listeners, $data){
        foreach ( $listeners as $listener ) {
            if (is_object ( $listener ['object'] )) {
                $data = call_user_func ( array($listener ['object'], $listener ['function']), $data);
            } else {
                $data = call_user_func ( $listener ['function'], $data );
            }
        }
        return $data;
    }
    
    /**
     * 
     * @param unknown hookname
     * @param unknown $data
     * @param unknown $module
     * @return unknown|mixed
     */
    public static function do_hook($filter_name, $data=array(), $module=null) {
        $listeners = self::has_hook ( $filter_name, $module );
        
        if (! $listeners) return $data;
        
        if ($module){
            return self::_call_user_func($listeners, $data);
        }
        
        foreach ( $listeners as $_listeners ) {
            $data = self::_call_user_func($_listeners, $data);
        }
        return $data;
    }
    
    public static function has_hook($filter_name, $module=null) {
        if($module){
            return @self::$listeners [$filter_name][$module];
        }else{
            return @self::$listeners [$filter_name];
        }
        
    }
    
    public static function include_hooks($module, $dir){
        if( ! file_exists($dir) )return;
        self::$currModule = $module;
        foreach(glob($dir."/*") as $file){
            if (is_dir($file)) {
                self::include_hooks($module, $file);
            }else if(is_file($file)){
                require_once $file;
            }
        }
    }
}
?>
