<?php

namespace yangzie;

use \app\App_Module;

/**
 * 自动加载类文件
 *
 * 根据类名命名规则（如 Foo_Controller、Foo_Model、Foo_Module、Foo_View 等）
 * 定位到对应模块下的类文件并加载，支持 phar 模块；找不到文件时触发 YZE_HOOK_AUTO_LOAD_CLASS hook
 *
 * @param string $class 需要加载的完整类名（含命名空间，如 \app\admin\Index_Controller）
 * @return void
 */
function yze_autoload($class) {
    $_ = preg_split("{\\\\}", strtolower($class));

    // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
    $module_name = $_[1] ?? null;
    $class_name = $_[2] ?? null;
    $loaded_module_info = \yangzie\YZE_Object::loaded_module($module_name);

    $file = "";
    // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
    if(($loaded_module_info['is_phar'] ?? null)){
        $module_name .= ".phar";
        $file = "phar://";
    }
    $file .= YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $module_name . DS ;

    if(preg_match("{_controller$}i", $class)){
        $file .= "controllers" . DS . preg_replace("{_controller$}i", "", $class_name) . ".controller.php";
    }else if(preg_match("{_model$}i", $class)){
        $file .= "models" . DS . preg_replace("{_model$}i", "", $class_name) . ".model.php";
    }else if(preg_match("{_method$}i", $class)){//model meta define
        $file .= "models" . DS . preg_replace("{_method$}i", "", $class_name) . ".method.php";
    }else if(preg_match("{_enum$}i", $class)){//enum
        $file .= "models" . DS . preg_replace("{_enum$}i", "", $class_name) . ".enum.php";
    }else if(preg_match("{_module$}i", $class)){
        $file .= "__config__.php";
    }else if(preg_match("{_view$}i", $class)){
        $file .= "views" . DS . preg_replace("{_view$}i", "", $class_name) . ".view.php";
        if ( ! file_exists($file)){
            $file = YZE_INSTALL_PATH . preg_replace("{_view$}i", "",strtr(strtolower($class), array("\\"=>"/"))) . ".view.php";
        }
    }
    if (!file_exists($file)){
        $file = YZE_INSTALL_PATH . strtr(strtolower($class), array("\\"=>"/")) . ".class.php";
    }
    if (!file_exists($file)){
        $file = YZE_INSTALL_PATH . strtr(strtolower($class), array("\\"=>"/")) . ".trait.php";
    }

    // ai@2026-05-27 替换 @ 抑制符，使用 ?? null 显式处理
    if(($file ?? null) && file_exists($file)){
        include $file;
    }else{
        YZE_Hook::do_hook("YZE_HOOK_AUTO_LOAD_CLASS", $class);
    }
}

spl_autoload_register("\yangzie\yze_autoload");



/**
 * 加载应用及所有模块，初始化配置
 *
 * 加载 app 配置、模块包含文件、hooks，并注册各模块的路由
 *
 * @return void
 */
function yze_load_app() {
    // 加载app配置
    if (! file_exists ( YZE_APP_PATH . "__config__.php" )) {
        die ( __ ( "app/__config__.php not found" ) );
    }
    include_once YZE_APP_PATH . '__config__.php';
    include_once YZE_APP_PATH . '__aros_acos__.php';

    $app_module = new App_Module ();
    $app_module->check ();

    $module_include_files = $app_module->get_module_config('include_files');
    foreach ( ( array ) $module_include_files as $path ) {
        $path = YZE_INSTALL_PATH.ltrim($path, DS);
        if(is_dir($path)){
            foreach (glob(rtrim($path, DS) . "/*") as $file) {
                include_once $file;
            }
        }else {
            include_once $path;
        }
    }

    YZE_Hook::include_hooks("app", YZE_APP_PATH.'hooks');
    YZE_Router::load_routers();
}

/**
 * yangzie 处理入口
 *
 * 开始处理请求，如果没有指定 uri，默认处理当前的 uri 请求。
 * 依次执行请求初始化、控制器加载、认证、分发调度，并在最后提交所有事务；
 * 处理过程中抛出的异常会回滚事务并交由异常控制器输出错误页面
 *
 * @return void
 */
function yze_handle_request() {
    $output = function($request, $controller, $response) {
        if(is_a($response,"\\yangzie\\YZE_View_Adapter")){
            $layout = new YZE_Layout($controller->get_layout(), $response, $controller);
            $layout->output();
            return;
        }
        $output = $response->output(true);
        if ($output)header("Location: {$output}");
    };

    try {
        $request = YZE_Request::get_instance ();

        $request->init ();
        $controller = $request->controller_instance ();

        foreach($controller->response_headers() as $header){
            header($header);
        }

        $request->auth ();

        \yangzie\YZE_Hook::do_hook(YZE_HOOK_BEFORE_DISPATCH);
        $response = $request->dispatch();
        \yangzie\YZE_Hook::do_hook(YZE_HOOK_AFTER_DISPATCH);

        $output($request, $controller, $response);

        YZE_DBAImpl::commit_all();
    }catch(\Exception $e){
        $controller = $request->controller_instance ();
        try{
            YZE_DBAImpl::rollBack_all();
            if( !$controller) $controller = new YZE_Exception_Controller();
            if(is_a($e, "\\yangzie\\YZE_Suspend_Exception")) $controller = new YZE_Exception_Controller();

            $response = $controller->do_exception($e);
            if( ! $response){
                $controller = new YZE_Exception_Controller();
                $response = $controller->do_exception($e);
            }

            $filter_data = ["exception"=>$e, "controller"=>$controller, "response"=>$response];
            $filter_data = \yangzie\YZE_Hook::do_hook(YZE_HOOK_YZE_EXCEPTION,$filter_data);
            $response = $filter_data['response'];

            $output($request, $controller, $response);
        }catch (\Exception $notCatch){
            $controller = new YZE_Exception_Controller();
            $controller->do_exception(new YZE_RuntimeException($notCatch->getMessage()))->output();
        }
    }
}
?>
