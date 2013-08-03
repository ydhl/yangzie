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
		/**
		 * 取得一次请求的请求对象，对于一次请求来说，该对象是单例的，
		 * 包含一次请求的所有请求信息，封装了一些请求处理
		 * @var YZE_Request
		 */
		$request = YZE_Request::get_instance();
		$request->init($uri);
		
		/**
		 * 一个用户的会话对象，对于用户的一个会话过程来说是唯一，
		 * 用于处理用户跨请求处理的一些数据问题
		 * @var YZE_Session_Context
		 */
		$session = YZE_Session_Context::get_instance();
		
		$dispatch = YZE_Dispatch::get_instance();
		
		//检查系统配置
		yze_system_check();
		
		$dispatch->init($uri);
		/**
		 * 登录认证请求，开发者需要实现系统的认证处理逻辑，
		 * 认证实现在App_Auth中实现
		 *
		 * 每个模块需要定义自己模块中的请求url哪些需要进行认证，需要认证的url将会通过YZE_IAuth
		 * 进行认证，开发者在YZE_IAuth的实现中处理具体的逻辑
		 *
		 * 配置模块中的那些url需要认证可在__module__.php::$auths配置
		 */
		$request->auth();

		/**
		 * 验证请求的数据。对请求中所带的数据进行数据格式要求定义。
		 * 请求映射到具体模块的具体控制器后，将会通过validates中的验证配置类对请求进行数据验证
		 * 验证类位于validates/控制器名下，类名为 请求方法_action_validate.class.php
		 */
		$request->validate();

		/**
		 * 如果请求是非get请求，则开启数据库的事务，yangzie约定认为get请求是读取操作
		 * 不会对数据库进行一些事务性质的读处理，其它请求会对数据库进行事务性质的写处理，
		 * 比如插入新数据，更新数据，删除数据等
		 */
		$request->begin_transaction();
		
		/**
		 * 把控制权交给映射的控制器的action中去。并在成功处理后返回YZE_IResponse，它表示一次请求的影响
		 * 如果初始化请求处理没有问题（找到了映射的controller，action），认证、数据验证都没有问题
		 * 则controller将开始具体的请求所要求的业务处理
		 * @var IRespose
		 */
		$response = $request->dispatch();

		//一切都ok，将把非get请求的处理进行事务提交
		$request->commit();

	}catch(Exception $e){
		
		// 出现异常 进入当前处理控制器的exception处理中去
		// 如果请求是post 则redirect 到当前处理控制器的exception处理中去
		
		$request->rollback();
		$controller = $dispatch->controller_obj();
		if( ! $controller){
			$controller = new YZE_Exception_Controller();
		}
		if( ! $request->is_get()){
			$session->save_controller_exception($controller, $e);
			$response = new YZE_Redirect($request->the_full_uri(), $controller);
		}else{
			$response = $controller->do_exception($e);
		}
	}

	/**
	 * 如果处理返回的视图是YZE_View_Adapter，表示响应是用于展示的视图内容，
	 * 这时将把用户定义的视图layout设置上，返回的视图将在layout中进行显示，layout定义
	 * 界面布局效果
	 */
	if(is_a($response,"YZE_View_Adapter")){
		$controller_obj	= @$error_controller ? $error_controller : $dispatch->controller_obj();
		$layout = new YZE_Layout($controller_obj->get_layout(), $response, $controller_obj);
		 //输出最终的视图 
		$output = $layout->get_output();
		if(($guid = $controller_obj->get_response_guid()) && !file_exists(YZE_APP_CACHES_PATH.$guid)){
			file_put_contents(YZE_APP_CACHES_PATH.$guid, $output);
		}
		echo $output;
		
	}else{
		//其它非视图的响应输出，比如说重定向等只有header的http输出
		$response->output();
	}
}
?>