<?php
/**
 * 加载所有的模块及其设置其配置
 */
function yze_load_app(){
	#加载app配置
	if(!file_exists(YZE_APP_PATH."__config__.php")){
		die(__("app/__config__.php not found"));
	}
	include_once YZE_APP_INC.'__config__.php';
	@include_once YZE_APP_INC.'__aros_acos__.php';
	@include_once YZE_APP_INC.'__hooks__.php';
	$app_module = new App_Module();

	$module_include_files = $app_module->get_module_config('include_files');
	foreach((array)$module_include_files as $path){
		include_once $path;
	}
	foreach(glob(YZE_APP_MODULES_INC."*") as $module){
		if(@file_exists("{$module}/__module__.php")){
			include_once "{$module}/__module__.php";
			
			$module_name = basename($module);
			$class = ucfirst(strtolower($module_name))."_Module";
			$object = new $class();
			$object->check();
			$include_files = $object->get_module_config('include_files');
			foreach((array)$include_files as $include_file){
				include_once YZE_APP_MODULES_INC.strtolower($object->get_module_config("name"))."/".$include_file;
			}
		}
		if(@file_exists("{$module}/__hooks__.php")){
			include_once "{$module}/__hooks__.php";
		}
	}
}

/**
 * 检查系统的设置，如果一切都ok返回，如果有什么错误throw 异常
 * 
 * @author leeboo
 * 
 * @throws YZE_RuntimeException
 * 
 * @return
 */
function yze_system_check(){
	$app_config = new App_Module();
	$app_config->check();
	$error = array();
	
	if (PHP_VERSION_ID<50300){
		$error[] = __("yangzie需要php 5.3+以上版本");
	}
	if (!extension_loaded("iconv")){
		$error[] = __("iconv扩展未开启");
	}

	if($error){
		throw new YZE_RuntimeException(join(",",  $error));
	}
}

/**
 * 开始处理请求，如果没有指定uri，默认处理当前的uri请求
 * 
 * @param string $uri
 */
function yze_go($uri = null){
	try{
		
		$request = YZE_Request::get_instance();
		$session = YZE_Session_Context::get_instance();
		
		$request->init($uri);
		yze_system_check();
		$controller = $request->controller();
		
		$request->auth()->validate()->begin_transaction();
		
		$response = $request->dispatch();
		$request->commit();
	}catch(YZE_RuntimeException $e){
		$request->rollback();
		
		if(is_a($e, "YZE_Request_Validate_Failed")){
			$session->save_controller_validates($controller, $e->get_validater()->get_validates());
		}
		
		if( ! $e->isResumeable()){//不可回复的异常，直接有yangzie接手
			$controller = new YZE_Exception_Controller();
			$response = $controller->do_exception($e);
		}else{//可恢复的异常，原来的控制器处理
			$session->save_controller_exception($controller, $e);
			$response = new YZE_Redirect($request->the_full_uri(), $controller, $controller->get_datas());
		}
	}

	if(is_a($response,"YZE_View_Adapter")){
		$layout = new YZE_Layout($controller->get_layout(), $response, $controller);
		$output = $layout->get_output();
		if(($guid = $controller->get_response_guid()) && !file_exists(YZE_APP_CACHES_PATH.$guid)){
			file_put_contents(YZE_APP_CACHES_PATH.$guid, $output);
		}
		echo $output;
	}else{
		$response->output();
	}
	
	$controller->cleanup();
}
?>