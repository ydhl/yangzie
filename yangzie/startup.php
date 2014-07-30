<?php
namespace yangzie;
use \app\App_Module;

/**
 * 加载所有的模块，设置其配置
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
		$phar_wrap = "";
		if(is_file($module)){//phar
			$phar_wrap = "phar://";
		}

		if(@file_exists("{$phar_wrap}{$module}/__module__.php")){
			require_once "{$phar_wrap}{$module}/__module__.php";
			
			$module_name = strtolower(basename($module));
			if($phar_wrap) {
				$module_name = ucfirst(preg_replace('/\.phar$/',"", $module_name));
			}
			$class = "\\app\\{$module_name}\\".ucfirst($module_name)."_Module";
			$object = new $class();
			$object->check();
			
			\yangzie\YZE_Object::set_loaded_modules($module_name, 
					array("is_phar"=>$phar_wrap ? true : false));
		}
		if(@file_exists("{$phar_wrap}{$module}/__hooks__.php")){
			include_once "{$phar_wrap}{$module}/__hooks__.php";
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
 * 开始处理请求，如果没有指定uri，默认处理当前的uri请求, 如果没有指定method，则以请求的方法为主（get post put delete）
 * 
 * @param string $uri
 * @param string $method
 * @param bool $return true则return，false直接输出
 */
function yze_go($uri = null, $method=null, $return=null){
	try{
		
		$request = YZE_Request::get_instance();
		$session = YZE_Session_Context::get_instance();
		$dba     = YZE_DBAImpl::getDBA();
		
		$oldController = $request->controller();
		if($oldController){
			$format = $request->get_output_format();
		}
		
		$request->init($uri, $method, $format);//初始化请求上下文环境

// 		yze_system_check();
		$controller = $request->controller();
		
		//如果yze_go 是从一个控制器的处理中再次调用的，则为新的控制器copy一个上下文环境
		//比如内部重定向
		if($oldController){
			YZE_Session_Context::get_instance()->copy(get_class($oldController), get_class($controller));
		}
		
		$action = "YZE_ACTION_BEFORE_".strtoupper($request->the_method());
		do_action(constant($action), $controller);
		
		$request->auth()->validate();
		$dba->beginTransaction();
		
		$response = $request->dispatch();
		$dba->commit();
	}catch(YZE_RuntimeException $e){

		$dba->rollback();
		if( ! @$controller){
			$controller = new YZE_Exception_Controller();
		}
		//验证出现异常的，先保存现场；便于后面回复
		if(is_a($e, "\\yangzie\\YZE_Request_Validate_Failed")){
			$session->save_controller_validates(get_class($controller), $e->get_validater()->get_validates());
		}
		\app\log4web($e->getMessage(), "startup error");
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

		if($return){
			return $output;
		}else{
			echo $output;
		}
	}else{//header output
		return $response->output($return);
	}
	
}
?>