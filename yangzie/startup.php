<?php
/**
 * 加载所有的模块及其设置其配置
 */
function yze_load_app(){
	#加载app配置
	if(!file_exists(APP_PATH."__config__.php")){
		die(__("app/__config__.php not found"));
	}
	include APP_INC.'__config__.php';
	@include APP_INC.'__aros_acos__.php';
	$app_module = new App_Module();
	ini_set('include_path',get_include_path().PS.APP_INC."components");

	$module_include_files = $app_module->get_module_config('include_files');
	foreach((array)$module_include_files as $path){
		include_once $path;
	}
	foreach(glob(APP_MODULES_INC."*") as $module){
		if(file_exists("{$module}/__module__.php")){
			include_once "{$module}/__module__.php";
		}
		if(file_exists("{$module}/__hooks__.php")){
			include_once "{$module}/__hooks__.php";
		}
	}
}


function yze_run($controller = null){
	try{
		/**
		 * 取得一次请求的请求对象，对于一次请求来说，该对象是单例的，
		 * 包含一次请求的所有请求信息，封装了一些请求处理
		 * @var Request
		 */
		$request = Request::get_instance();
		/**
		 * 一个用户的会话对象，对于用户的一个会话过程来说是唯一，
		 * 用于处理用户跨请求处理的一些数据问题
		 * @var Session
		 */
		$session = Session::get_instance();
		$dispatch = YZE_Dispatch::get_instance();
		$dispatch->init($controller);
		/**
		 * 登录认证请求，开发者需要实现系统的认证处理逻辑，
		 * 认证实现在App_Auth中实现
		 *
		 * 每个模块需要定义自己模块中的请求url哪些需要进行认证，需要认证的url将会通过IAuth
		 * 进行认证，开发者在IAuth的实现中处理具体的逻辑
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
		 * 把控制权交给映射的控制器的action中去。并在成功处理后返回IResponse，它表示一次请求的影响
		 * 如果初始化请求处理没有问题（找到了映射的controller，action），认证、数据验证都没有问题
		 * 则controller将开始具体的请求所要求的业务处理
		 * @var IRespose
		 */
		$response = $request->dispatch();

		//一切都ok，将把非get请求的处理进行事务提交
		$request->commit();

	}catch (YZE_Auth_Failed_Exception $e){
		//FIXME 并不知道要去中个地址
		//把当前uri中产生的异常保存下来，以便恢复后显示它
		$session->save_uri_exception("/users/signin/", $e);
		//身份验证失败，导向登录页面，并把当前的uri带过去，登录成功后又返回
		$response = new Redirect("/users/signin/?back_uri=".urlencode($request->the_uri()),$request->controller_obj());

	}catch (YZE_Unresume_Exception $e){
		/**
		 * 这里表示在请求的处理过程中出现了不可恢复的异常。
		 *
		 * 不可恢复的异常指的是不能通过重新请求来重试的异常
		 */
		$request->rollback();

		$error_controller = new YZE_Exception_Controller();
		$error_controller->set_exception($e);
		$response = $error_controller->do_get();

	}catch (YZE_Resume_Exception $e){
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
			$response = new Redirect($referer_uri,$dispatch->controller_obj());
		}else{
			$response = $request->dispatch();
		}
		$request->rollback();
	}

	/**
	 * 如果处理返回的视图是View_Adapter，表示响应是用于展示的视图内容，
	 * 这时将把用户定义的视图layout设置上，返回的视图将在layout中进行显示，layout定义
	 * 界面布局效果
	 */
	if(is_a($response,"View_Adapter")){
		$controller_obj	= @$error_controller ? $error_controller : $dispatch->controller_obj();
		$layout = new Layout($controller_obj->get_layout(), $response, $controller_obj);
		 //输出最终的视图 
		$output = $layout->get_output();
		if(($guid = $controller_obj->get_response_guid()) && !file_exists(APP_CACHES_PATH.$guid)){
			file_put_contents(APP_CACHES_PATH.$guid, $output);
		}
		echo $output;
		
		//界面显示后把一些数据清空
		Session::get_instance()->clear_uri_exception($request->the_uri());
		Session::get_instance()->clear_uri_datas($request->the_uri());
	}else{
		//其它非视图的响应输出，比如说重定向等只有header的http输出
		$response->output();
	}
}
?>