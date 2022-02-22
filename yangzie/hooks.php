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
    /**
     * 在处理post前调用, 这发生在已经做了基本处理，初始花了request和controller，但还没有进入流程前，参数YZE_Request
     * @var unknown
     */
    define ( 'YZE_ACTION_BEFORE_POST', 'do_before_post' );
    /**
     * 在处理get前调用, 这发生在已经做了基本处理，初始花了request和controller，但还没有进入流程前，参数YZE_Request
     * @var unknown
     */
    define ( 'YZE_ACTION_BEFORE_GET', 'do_before_get' );
    define ( 'YZE_ACTION_AFTER_POST', 'do_after_post' );
    define ( 'YZE_ACTION_AFTER_GET', 'do_after_get' );
    define ( 'YZE_ACTION_BEFORE_DISPATCH', 'YZE_ACTION_BEFORE_DISPATCH' );
    define ( 'YZE_ACTION_AFTER_DISPATCH', 'YZE_ACTION_AFTER_DISPATCH' );

    /**
     * 参数 即将要保持进数据库的对象；在实际更新数据库之前调用
     * @var unknown
     */
    define ( 'YZE_HOOK_MODEL_UPDATE',    'YZE_HOOK_MODEL_UPDATE' );
    /**
     * 参数 即将要保持进数据库的对象；在实际插入数据库之前调用
     * @var unknown
     */
    define ( 'YZE_HOOK_MODEL_INSERT',    'YZE_HOOK_MODEL_INSERT' );
    /**
     * 参数 即将要删除的对象；在实际删除数据库之前调用
     * @var unknown
     */
    define ( 'YZE_HOOK_MODEL_DELETE',    'YZE_HOOK_MODEL_DELETE');
    /**
     * 查询回调，参数数查询出来的的model数组
     * @var unknown
     */
    define ( 'YZE_HOOK_MODEL_SELECT',    'YZE_HOOK_MODEL_SELECT');
    define ( 'YZE_ACTION_TRANSACTION_COMMIT', 'transaction_commit' );
    define ( 'YZE_ACTION_BEFORE_DO_EXCEPTION', 'yze_action_before_do_exception' );


    /**
     * 框架处理时出现了异常的filter，传入
     * ["exception"=>$e, "controller"=>$controller, "response"=>$response]
     * filter处理函数可修改response自定义响应
     * @var unknown
     */
    define ( 'YZE_FILTER_YZE_EXCEPTION', 'yze_filter_yze_exception' );
    /**
     * 获取aro，传入用户对象，返回user的aro
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
    /**
     * 当自动加载类无法找到时触发该hook,交给开发者自己处理如何include相关文件, 传入的参数是类名
     */
    define ( 'YZE_HOOK_AUTO_LOAD_CLASS', 'YZE_HOOK_AUTO_LOAD_CLASS' );
    /**
     * 获取当前的语言设置，默认获取request中的accept_language, get_accept_language()取得
     */
    define('YZE_HOOK_GET_LOCALE', 'YZE_HOOK_GET_LOCALE');
    final class YZE_Hook {
        private static $listeners = array ();
        private static $currModule;

        /**
         * 增加hook， 如果有多个注册回调，则返回的是最后一个回调函数的返回结果，如果想把所有回调的数据汇总，则可以通过修改引用参数的方式返回；
         * 具体如何做，需要针对具体的$filterName说明清楚
         * @param $event
         * @param $funcName 参数必须是引用，需要加&
         * @param $object
         * @return void
         */
        public static function add_hook($event, $funcName, $object = null) {
            //include_hooks中已经知道模块名了
            self::$listeners [$event] [self::$currModule] [] = ["function" => $funcName, "object" => $object ];
        }

        /**
         * 如果没有hook注册，返回null;
         *
         * 如果有多个注册回调，则返回的是最后一个回调函数的返回结果，如果想把所有回调的数据汇总，则可以通过修改引用参数的方式返回;
         * 具体如何做，需要针对具体的$filterName说明清楚
         *
         * @param string $filterName
         * @param unknown $data 传递给回调函数的data，如果修改了data的内容，会影响后续的回调，
         * @param unknown $module 指定则指调用该module下面的hook，多个可用,分隔，依次调用其中的module
         * @return unknown|mixed
         */
        public static function do_hook($filterName, &$data=null, $module=null) {
            $listeners = self::has_hook ( $filterName, $module );
            if (! $listeners) return null;

            $filter_data = null;
            foreach ( $listeners as $listener ) {
                if (is_object ( $listener['object'] ) && method_exists($listener['object'], $listener['function'])) {
                    $filter_data = $listener['object']->$listener['function']($data);
                } else if (is_callable($listener['function'])){
                    $filter_data = $listener['function']( $data );
                }
            }
            return $filter_data;
        }

        public static function has_hook($filterName, $module=null) {
            if($module){
                $modules = explode(",", $module);
                $funcs = array();
                foreach ($modules as $module){
                    foreach ((array)@self::$listeners [$filterName][$module] as $func){
                        $funcs[] = $func;
                    }
                }
                return $funcs;
            }

            $funcs = array();
            foreach ((array)@self::$listeners [$filterName] as $m=>$_funcs){
                foreach ((array)$_funcs as $func){
                    $funcs[] = $func;
                }
            }
            return $funcs;
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
