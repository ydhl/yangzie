<?php

namespace yangzie;

use \app\App_Module;

//自动加载处理
function yze_autoload($class) {
    $_ = preg_split("{\\\\}", strtolower($class));

    if($_[0]=="app"){

        $module_name = $_[1];
        $class_name = $_[2];
        $loaded_module_info = \yangzie\YZE_Object::loaded_module($module_name);

        $file = "";
        if($loaded_module_info['is_phar']){
            $module_name .= ".phar";
            $file = "phar://";
        }
        $file .= YZE_INSTALL_PATH . "app" . DS . "modules" . DS . $module_name . DS ;
        if(preg_match("{_controller$}i", $class)){
            $file .= "controllers" . DS . $class_name . ".class.php";
        }else if(preg_match("{_model$}i", $class)){
            $file .= "models" . DS . $class_name . ".class.php";
        }else if(preg_match("{_module$}i", $class)){
            $file .= "__module__.php";
        }else{
            $file = YZE_INSTALL_PATH . strtr(strtolower($class), array("\\"=>"/")) . ".class.php";
        }

        if(@$file && file_exists($file)){
            include $file;
        }
    }
}

/**
 * 加载所有的模块，设置其配置
 */
function yze_load_app() {
    // 加载app配置
    if (! file_exists ( YZE_APP_PATH . "__config__.php" )) {
        die ( __ ( "app/__config__.php not found" ) );
    }
    include_once YZE_APP_INC . '__config__.php';
    @include_once YZE_APP_INC . '__aros_acos__.php';
    
    
    $app_module = new App_Module ();
    $module_include_files = $app_module->module_include_files ( );
    foreach ( ( array ) $module_include_files as $path ) {
        include_once $path;
    }
    
    YZE_Hook::include_hooks("app", YZE_APP_INC.'hooks');
    
    foreach ( glob ( YZE_APP_MODULES_INC . "*" ) as $module ) {
        $phar_wrap = "";
        if (is_file ( $module )) { // phar
            $phar_wrap = "phar://";
        }
        
        if (@file_exists ( "{$phar_wrap}{$module}/__module__.php" )) {
            require_once "{$phar_wrap}{$module}/__module__.php";
            
            $module_name = strtolower ( basename ( $module ) );
            if ($phar_wrap) {
                $module_name = ucfirst ( preg_replace ( '/\.phar$/', "", $module_name ) );
            }
            $class = "\\app\\{$module_name}\\" . ucfirst ( $module_name ) . "_Module";
            $object = new $class ();
            $object->check ();
            
            $mappings = $object->get_module_config('routers');
            if($mappings){
                YZE_Router::get_Instance()->set_Routers($module_name,$mappings);
            }
            
            \yangzie\YZE_Object::set_loaded_modules ( $module_name, array (
                    "is_phar" => $phar_wrap ? true : false
            ) );
        }
        YZE_Hook::include_hooks($module_name, "{$phar_wrap}{$module}/hooks");
    }
}

/**
 * yangzie入口
 * 开始处理请求，如果没有指定uri，默认处理当前的uri请求, 
 * 如果没有指定method，则以请求的方法为主（get post put delete）
 *
 * @param string $uri            
 * @param string $method            
 * @param bool $return true则return，false直接输出
 * @return string
 */
function yze_go($uri = null, $method = null, $return = null, $request_method=null) {

    $output_view = function($request, $controller, $response, $return) {
        $layout = new YZE_Layout($controller->get_layout(), $response, $controller);
        $output = $layout->get_output();
    
        $request->remove();
        $controller->cleanup();
        if($return){
            return $output;
        }
    
        echo $output;
        exit();
    };
    
    $output_header = function($request, $controller, $response, $return){
        $output = $response->output($return);
        if($return){
            $controller->cleanup();
            $request->remove();
            return $output;
        }
        $controller->cleanup();
        $request->remove();
        if ($output)header("Location: {$output}");
        exit();
    };
    
    try {
        $request = YZE_Request::get_instance ();
        $session = YZE_Session_Context::get_instance ();
        $dba     = YZE_DBAImpl::getDBA ();
        $format  = null;
        
        //之前已经有请求了，则copy一个新请求
        if ( $request->has_request() ){
            $old_uri = $request->the_uri();
            $format  = $request->get_output_format ();
            $request = $request->copy();
        }

        $request->init ( $uri, $method, $format , $request_method); // 初始化请求上下文环境,请求入栈
        
        $controller = $request->controller ();

        // 如果yze_go 是从一个控制器的处理中再次调用的，则为新的控制器copy一个上下文环境
        if (@$old_uri) {
            $session->copy ( $old_uri, $request->the_uri() );
        }
        
        $action = "YZE_ACTION_BEFORE_" .  ( $request->is_get() ? "GET" : "POST");
        \yangzie\YZE_Hook::do_hook ( constant ( $action ), $controller );

        $request->auth ();
        $dba->beginTransaction();
		
        $response = $request->dispatch();
        $dba->commit();
        

        // content output
        if(is_a($response,"\\yangzie\\YZE_View_Adapter")){
            return $output_view($request, $controller, $response, $return);
        }
        
        //header output
        return $output_header($request, $controller, $response, $return);
    }catch(\Exception $e){
        $controller = $request->controller ();
        //嵌套调用的，把异常往外层抛
        //是请求的控制器自己处理异常好，还是把异常一直抛出到顶级请求来处理好？
        if( ! $request->is_top_request() ) {
            $request->remove();
            throw $e;
        }
        
        try{
            $dba->rollback();
            if( ! @$controller || is_a($e, "\\yangzie\\YZE_Suspend_Exception")){
                $controller = new YZE_Exception_Controller();
            }
            
            $response = $controller->do_exception($e);

            if( ! $response){
            	$response = (new YZE_Exception_Controller())->do_exception($e);
            }
            
            $filter_data = \yangzie\YZE_Hook::do_hook(YZE_FILTER_YZE_EXCEPTION,  
                    array("exception"=>$e, "controller"=>$controller, "response"=>$response));
            $response = $filter_data['response'];

            // content output
            if(is_a($response,"\\yangzie\\YZE_View_Adapter")){
                return $output_view($request, $controller, $response, $return);
            }
            
            //header output
            return $output_header($request, $controller, $response, $return);
        }catch (\Exception $notCatch){
            $controller = new YZE_Exception_Controller();
            $controller->do_exception(new YZE_RuntimeException($notCatch->getMessage()))->output();
            $request->remove();
        }
    }
}
?>