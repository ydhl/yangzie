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
 * @throws YZE_Unresume_Exception
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


function yze_run($controller = null){
	try{
		/**
		 * 取得一次请求的请求对象，对于一次请求来说，该对象是单例的，
		 * 包含一次请求的所有请求信息，封装了一些请求处理
		 * @var YZE_Request
		 */
		$request = YZE_Request::get_instance();
		/**
		 * 一个用户的会话对象，对于用户的一个会话过程来说是唯一，
		 * 用于处理用户跨请求处理的一些数据问题
		 * @var YZE_Session
		 */
		$session = YZE_Session::get_instance();
		//检查系统配置
	
		$dispatch = YZE_Dispatch::get_instance();
		yze_system_check();
		$dispatch->init($controller);
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

	}catch (YZE_Unresume_Exception $e){
		$request->rollback();
		
		$dispatch = YZE_Dispatch::get_instance();
		
		$controller = $dispatch->controller_obj();
		$response = $controller->do_exception($e);
		
// 		$error_controller = new YZE_Exception_Controller();
// 		$error_controller->set_exception($e);
		
// 		if( YZE_Hook::the_hook()->has_hook(YZE_HOOK_UNRESUME_EXCEPTION) && $request->get_output_format()=="json" ){
// 			$response = YZE_Hook::the_hook()->do_filter(YZE_HOOK_UNRESUME_EXCEPTION,
// 					array("exception"=>$e, "controller"=> ($dispatch ? $dispatch->controller_obj() : new YZE_Exception_Controller())));
			
// 		}else{
// 			$response = $error_controller->do_get();
// 		}

	}catch (YZE_Resume_Exception $e){
		$request->rollback();
		$controller = $dispatch->controller_obj();
		/**
		 * 这里表示请求的处理过程中出现了可恢复的异常
		 * 可恢复的异常表示可以通过重新请求来重试的异常，比如post后数据验证出现的可恢复的异常
		 * ，可以通过回到之前的get页面，把数据重新修正后重新post来解决
		 *
		 * yangzie认为只有get请求会返回视图，可恢复的异常总是回到发现其它请求之前的get请求中
		 * post请求验证失败通过get回到当前uri，并把错误异常带过去
		 * get请求验证失败设置错误异常，继续往下走
		 * get视图显示错误信息
		 */

		/**
		 * 如果URI A post请求到URI B，带上该post参数，出错时将返回referer_uri，其值通常就是URI A。
		 * 要不然YZE认为一个请求默认post到自己，出错时将get回自己，也就是说URI A post到URI B，出错时将get URI B
		 */
		$referer_uri = YZE_Object::the_val(urldecode($request->get_from_post("referer_uri")), urldecode($request->the_full_uri()));//the_uri
		$no_query_uri = parse_url($referer_uri, PHP_URL_PATH);

		/**
		 * 把当前uri中产生的异常保存下来，以便恢复后显示它
		 */
		$session->save_uri_exception($no_query_uri, $e);
		/**
		 * 非get请求的话，需要重定向成get请求；
		 * 如果是get请求的话，则直接在本次请求的处理结果中显示，也就是要重新dispatch一下
		 * 在具体的action，需要首先判断当前请求的uri是否有异常，有则需要把异常消息取出来，
		 * 并决定其显示在什么地方，判断后再进行后面的业务处理
		 */
		if(!$request->is_get()){
			$response = new YZE_Redirect($referer_uri,$dispatch->controller_obj());
		}else{
			$response = $controller->do_exception($e);
		}
		
	}catch(Exception $e){
		$request->rollback();
		
		$controller = $dispatch->controller_obj();
		
		if( ! $controller){
			$controller = new YZE_Exception_Controller();
		}
		$response = $controller->do_exception($e);
		
// 		if( YZE_Hook::the_hook()->has_hook(YZE_HOOK_UNRESUME_EXCEPTION) && $request->get_output_format()=="json" ){
// 			$response = YZE_Hook::the_hook()->do_filter(YZE_HOOK_UNRESUME_EXCEPTION,
// 					array("exception"=>$e, "controller"=> ($dispatch ? $dispatch->controller_obj() : new YZE_Exception_Controller())));
				
// 		}else{
// 			$response = $error_controller->do_get();
// 		}
// 		echo return_json_result(1, 1, $e->getMessage(), array());die;
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
		
		//界面显示后把一些数据清空
		YZE_Session::get_instance()->clear_uri_exception($request->the_uri());
		YZE_Session::get_instance()->clear_uri_datas($request->the_uri());
	}else{
		//其它非视图的响应输出，比如说重定向等只有header的http输出
		$response->output();
	}
}
?>