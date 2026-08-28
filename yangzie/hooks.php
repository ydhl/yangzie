<?php

namespace yangzie;

/**
 * 该文件为系统提供hook机制，hook主要用于下面的地方：
 * 1.数据输入,输出处理
 * 2.事件通知
 * 3.模块之间功能调用
 *
 * hook处理的方式是：
 * 1.在系统启动前加载所有的hook 函数：加载每个hooks目录下的文件
 * 2.通过do_hook($hook_name, $args)调用hook，$args会传入hook函数
 * 3.对于注册到统一hook的多个函数，每一个函数返回的$args会进入下一个hook 函数
 *
 * 注册的hook函数接受一个数组参数，函数的返回值也是通过参数返回
 *
 * @author liizii
 * @since 2009-9-1
 */

/**
 * 在开始执行具体的action前调用
 */
define ( 'YZE_HOOK_BEFORE_DISPATCH', 'YZE_HOOK_BEFORE_DISPATCH' );
/**
 * 在执行具体的action后调用
 */
define ( 'YZE_HOOK_AFTER_DISPATCH', 'YZE_HOOK_AFTER_DISPATCH' );

/**
 * 在实际更新数据库之后调用，传入更新的model
 */
define ( 'YZE_HOOK_MODEL_UPDATE',    'YZE_HOOK_MODEL_UPDATE' );
/**
 * 在实际插入数据库之后调用，传入model
 */
define ( 'YZE_HOOK_MODEL_INSERT',    'YZE_HOOK_MODEL_INSERT' );
/**
 * 实际删除数据库之后调用，传入model
 */
define ( 'YZE_HOOK_MODEL_DELETE',    'YZE_HOOK_MODEL_DELETE');
/**
 * 查询回调，参数数查询出来的的model数组
 */
define ( 'YZE_HOOK_MODEL_SELECT',    'YZE_HOOK_MODEL_SELECT');
/**
 * 处理流程中出现来异常，在执行控制器的exception前调用
 */
define ( 'YZE_HOOK_BEFORE_DO_EXCEPTION', 'yze_action_before_do_exception' );


/**
 * 框架处理时出现了异常的hook，传入
 * ["exception"=>$e, "controller"=>$controller, "response"=>$response]
 * 处理函数可修改response自定义响应
 * @var unknown
 */
define ( 'YZE_HOOK_YZE_EXCEPTION', 'yze_hook_yze_exception' );
/**
 * 获取登录用户的aro
 *
 * @var unknown
 */
define ( 'YZE_HOOK_GET_USER_ARO_NAME', 'yze_hook_get_user_aro_name' );

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
 * <ol>
 * <li>控制器文件：文件放置在app/modules/模块名/controllers/控制器名.controller.php, 类命名规则：控制器名_Controller</li>
 * <li>模型文件：文件放置在app/modules/模块名/models/模型名.model.php, 类命名规则：模型名_Model</li>
 * <li>模型文件逻辑代码文件：文件放置在app/modules/模块名/models/模型名.method.php, 类命名规则：模型名_Model_Method</li>
 * <li>模块的配置文件：文件放置在app/modules/模块名/__config__.php, 类命名规则：模块名_Module</li>
 * <li>视图文件：文件可放置在app下任何地方, 但命名空间和和其存储路径要对应，比如放置在app/foo/bar.view.php，那么其命名空间就是namespace app\foo，文件名命名规则：视图.view.php, 类命名规则：视图名_View</li>
 * <li>其他情况下的类文件，可以放置在app任何地方, 但命名空间和和其存储路径要对应，比如放置在app/foo/bar.class.php，那么其命名空间就是namespace app\foo，文件名命名规则：类名.class.php</li>
 * </ol>
 */
define ( 'YZE_HOOK_AUTO_LOAD_CLASS', 'YZE_HOOK_AUTO_LOAD_CLASS' );
/**
 * 获取当前的语言设置，默认获取request中的accept_language, get_accept_language()取得
 */
define('YZE_HOOK_GET_LOCALE', 'YZE_HOOK_GET_LOCALE');
final class YZE_Hook extends YZE_Object {
    /**
     * hook 监听器注册表
     * 结构：listeners[事件名][模块名][] = ["function"=>回调函数, "object"=>对象]
     *
     * @var array
     */
    private static $listeners = array ();

    /**
     * 当前正在加载 hook 文件的模块名
     *
     * @var string
     */
    private static $currModule;

    /**
     * 增加 hook 回调
     *
     * 如果有多个注册回调，则返回的是最后一个回调函数的返回结果，如果想把所有回调的数据汇总，则可以通过修改引用参数的方式返回；
     * 具体如何做，需要针对具体的 $filterName 说明清楚
     *
     * @param string $event     hook 事件名，使用 YZE_HOOK_* 常量
     * @param string $funcName  回调函数名（或对象方法名），对象方法需在 $object 中指定对象
     * @param object|null $object 回调函数所在的对象，为 null 时按普通函数调用
     * @return void
     */
    public static function add_hook($event, $funcName, $object = null) {
        //include_hooks中已经知道模块名了
        self::$listeners [$event] [self::$currModule] [] = ["function" => $funcName, "object" => $object ];
    }

    /**
     * 触发指定 hook 事件，依次调用注册的所有回调
     *
     * 如果没有 hook 注册，返回 null；
     * 如果有多个注册回调，则返回的是最后一个回调函数的返回结果，如果想把所有回调的数据汇总，则可以通过修改引用参数的方式返回；
     * 具体如何做，需要针对具体的 $filterName 说明清楚
     *
     * @param string $filterName hook 事件名
     * @param mixed  $data       传递给回调函数的数据（引用传递），回调中修改 $data 会影响后续的回调
     * @param string|null $module 指定则只调用该 module 下面的 hook，多个用逗号分隔，依次调用其中的 module
     * @return mixed|null 最后一个回调函数的返回结果，没有 hook 注册时返回 null
     */
    public static function do_hook($filterName, &$data=null, $module=null) {
        $listeners = self::get_hook ( $filterName, $module );
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

    /**
     * 返回指定注册在 filterName 下面的 hook 回调函数列表
     *
     * @param string $filterName hook 事件名
     * @param string|null $module 模块名，多个用逗号分隔；为 null 时返回所有模块的注册回调
     * @return array 回调函数列表，结构：[["function"=>..., "object"=>...], ...]
     */
    public static function get_hook($filterName, $module=null) {
        if($module){
            $modules = explode(",", $module);
            $funcs = array();
            foreach ($modules as $module){
                // ai@2026-05-27 替换 @ 抑制符，使用 ?? [] 显式处理
                foreach ((array)(self::$listeners[$filterName][$module] ?? []) as $func){
                    $funcs[] = $func;
                }
            }
            return $funcs;
        }

        $funcs = array();
        // ai@2026-05-27 替换 @ 抑制符，使用 ?? [] 显式处理
        foreach ((array)(self::$listeners[$filterName] ?? []) as $m=>$_funcs){
            foreach ((array)$_funcs as $func){
                $funcs[] = $func;
            }
        }
        return $funcs;
    }

    /**
     * 递归包含 module 模块下 dir 目录下面的所有 hook 文件
     *
     * @param string $module 模块名，作为该模块 hook 回调的注册归属
     * @param string $dir    要遍历的目录路径
     * @return void
     */
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
