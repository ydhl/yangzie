<?php

namespace yangzie;

use \app\App_Module;


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
    @include_once YZE_APP_INC . '__hooks__.php';
    $app_module = new App_Module ();
    
    $module_include_files = $app_module->get_module_config ( 'include_files' );
    foreach ( ( array ) $module_include_files as $path ) {
        include_once $path;
    }
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
            
            \yangzie\YZE_Object::set_loaded_modules ( $module_name, array (
                    "is_phar" => $phar_wrap ? true : false 
            ) );
        }
        if (@file_exists ( "{$phar_wrap}{$module}/__hooks__.php" )) {
            include_once "{$phar_wrap}{$module}/__hooks__.php";
        }
    }
}

/**
 * yangzie入口
 * 开始处理请求，如果没有指定uri，默认处理当前的uri请求, 如果没有指定method，则以请求的方法为主（get post put delete）
 *
 * @param string $uri            
 * @param string $method            
 * @param bool $return
 *            true则return，false直接输出
 */
function yze_go($uri = null, $method = null, $return = null) {
    global $yze_request_stack;
    try {
        
        $request = YZE_Request::get_instance ();
        $session = YZE_Session_Context::get_instance ();
        $dba     = YZE_DBAImpl::getDBA ();
        $format  = null;
        
        $oldController = $request->controller ();
        if ($oldController) {
            $format = $request->get_output_format ();
        }
        
        //yze_go被嵌套调用，这时要复制一个request，新的yze_go不能污染之前的request
        if ( count( $yze_request_stack ) ){
            $request = clone $request;
        }
        $request->init ( $uri, $method, $format ); // 初始化请求上下文环境
        array_push($yze_request_stack, $request);
        $controller = $request->controller ();
        
        // 如果yze_go 是从一个控制器的处理中再次调用的，则为新的控制器copy一个上下文环境
        // 比如内部重定向
        if ($oldController) {
            $session->copy ( get_class ( $oldController ), get_class ( $controller ) );
        }
        
        $action = "YZE_ACTION_BEFORE_" . strtoupper ( $request->the_method () );
        do_action ( constant ( $action ), $controller );
        
        $request->auth ()->validate ();
        $dba->beginTransaction();
		
        $response = $request->dispatch();
        $dba->commit();
    }catch(YZE_RuntimeException $e){
        if( count($yze_request_stack) > 1) {
            array_pop($yze_request_stack);
            throw $e;
        }
        
        
        $dba->rollback();
        if( ! @$controller){
            $controller = new YZE_Exception_Controller();
        }
        //验证出现异常的，先保存现场；便于后面回复
        if(is_a($e, "\\yangzie\\YZE_Request_Validate_Failed")){
            $session->save_controller_validates(get_class($controller), $e->get_validater()->get_validates());
        }
		
        $session->save_controller_exception(get_class($controller), $e);
        if($request->is_get()){
            $response = $controller->do_exception($e);
        }else{
            $response = new YZE_Redirect($request->the_full_uri(), $controller, $controller->get_datas());
        }
		
        $filter_data = do_filter(YZE_FILTER_YZE_EXCEPTION,  array("exception"=>$e, "controller"=>$controller, "response"=>$response));
        $response = $filter_data['response'];
    }
    
    $controller->cleanup();
    
    if(is_a($response,"\\yangzie\\YZE_View_Adapter")){
        $layout = new YZE_Layout($controller->get_layout(), $response, $controller);
        $output = $layout->get_output();
        if(($guid = $controller->get_response_guid()) && !file_exists(YZE_APP_CACHES_PATH.$guid)){
            file_put_contents(YZE_APP_CACHES_PATH.$guid, $output);
        }
        array_pop($yze_request_stack);
        if($return){
            return $output;
        }else{
            echo $output;
        }
    }else{//header output
        array_pop($yze_request_stack);
        return $response->output($return);
    }	
}
?>