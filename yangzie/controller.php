<?php
/**
 * 资源控制器抽象基类，提供控制器的处理机制，子类控制器映射到具体的uri，具体处理请求的
 * action在子类中定义，该类为post，get，put，delete的请求做预处理，然后调用到对应的action
 * 子类的action如果没有返回YZE_IResponse则这里默认返回对应的Simple_View
 * 为view提供设置view中要使用的数据的方法。
 * 负责对post请求进行验证处理：多人同时修改，重复提交表单
 * 提供get，post，put，delete的hook
 * 定义视图的layout
 * 定义响应是否可以在浏览器上缓存:YZE_HttpCache
 *
 * @category Framework
 * @package  Yangzie
 * @author   liizii, <libol007@gmail.com>
 * @license  http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link     yangzie.yidianhulian.com
 */
abstract class YZE_Resource_Controller extends YZE_Object{
	protected $view_data = array();
	protected $layout = 'tpl';

	protected $module_name = "";

	/**
	 * @var YZE_HttpCache
	 */
	protected $cache_config;
	protected $session;
	protected $request;

	public function __construct(){
		$this->request = YZE_Request::get_instance();
		$this->session = YZE_Session_Context::get_instance();

		//init layout
		$request = YZE_Request::get_instance();
		if($request->get_output_format()){
			$this->layout = $request->get_output_format();
		}
	}

	public function get_Layout(){
		return $this->layout;
	}
	public function set_View_Data($name,$value){
		$this->view_data[$name] = $value;
		return $this;
	}
	/**
	 * 取得视图数据
	 */
	public function get_View_Data(){
		return $this->view_data;
	}

	/**
	 *
	 * 取得缓存的数据与设置的视图数据
	 *
	 * @return Array array()
	 */
	public function get_datas(){
		$request = YZE_Request::get_instance();
		$cache = YZE_Session_Context::get_instance()->get_controller_datas(get_class($this));
		if( ! $cache){
			return $this->get_View_Data();
		}
		return array_merge($cache, $this->get_view_data());
	}

	public final  function has_response_cache(){
		$cahce_file = YZE_APP_CACHES_PATH.$this->get_response_guid();
		if(file_exists($cahce_file) && $this->get_response_guid()){
			return file_get_contents($cahce_file);
		}
		return null;
	}

	/**
	 * 如果该控制器的输出需要缓存（生成静态文件），该方法返回生成的换成的文件名，该文件名需要唯一，并且是根据所请求
	 * 的信息来生成，保证在形同的请求信息下生成的文件名要一样
	 *
	 * @author leeboo
	 * @return
	 */
	public function get_response_guid(){
		//pass
	}

	protected function getResponse($view_tpl=null, $format=null){
		$request = YZE_Request::get_instance();
		
		$view_data  = $this->get_datas();
		
		$view_tpl = $view_tpl ? $view_tpl : substr(strtolower(get_class($this)), 0, -11);
		$view = $request->view_path()."/". $view_tpl;
		if( ! $format){
			$format = $request->get_output_format();
		}
		
		return new YZE_Simple_View($view, $view_data, $this, $format);
	}
	
	/**
	 * 处理get方法.get方法用于显示界面,给出响应，如果该url有异常，则进入exception处理
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return YZE_IResponse
	 */
	public final function do_Get(){
		//设置请求token
		do_action(YZE_HOOK_BEFORE_GET, $this);
		$request 	= YZE_Request::get_instance();
		$session	= YZE_Session_Context::get_instance();
		
		//该请求有异常
		if(($exception = $session->get_controller_exception(get_class($this)))){
			$response = $this->exception($exception);
		}else{
			YZE_Session_Context::get_instance()->set_request_token(get_class($request->controller()), $request->the_request_token());
			$response = $this->get();
		}

		if (!$response) {
			$response = $this->getResponse();
		}
		if(is_a($response, "YZE_View_Adapter")){
			$response->check_view();
		}
		if (is_a($response, "YZE_Cacheable")) {
			$response->set_cache_config($this->cache_config);//内容协商的缓存控制
		}
		
		if(@$_SERVER['HTTP_X_YZE_NO_CONTENT_LAYOUT'] == "yes"){
			$this->layout = "";
		}
		
// 		//界面显示后把一些数据清空
// 		$session->clear_controller_exception(get_class($this));
// 		$session->clear_controller_datas(get_class($this));
		return $response;
	}

	/**
	 * post方法.用于处理用户数据提交,提交成功后重定向
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return YZE_IResponse
	 */
	public final function do_Post(){
		do_action(YZE_HOOK_BEFORE_POST, $this);
		return $this->_handle_post();
	}

	/**
	 * put方法,更新数据
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return YZE_IResponse
	 */
	public final function do_Put(){
		do_action(YZE_HOOK_BEFORE_PUT,$this);
		$request = YZE_Request::get_instance();
		$session = YZE_Session_Context::get_instance();
		//多人同时提交表单
		$yze_model_id 		= $request->get_from_post("yze_model_id");
		$yze_modify_version = $request->get_from_post("yze_modify_version");
		$yze_model_name		= $request->get_from_post("yze_model_name");
		$yze_module_name	= $request->get_from_post("yze_module_name");

		$model = YZE_Model::find($yze_model_id, $yze_model_name);

		if(!$model) {
			throw new YZE_Resource_Not_Found_Exception(__("您要修改的内容不存在"));
		}
		//         include_once "{$yze_module_name}/models/".strtolower($yze_model_name).".class.php";

		if ($yze_modify_version != $model->get_version_value()) {
			throw new YZE_Model_Update_Conflict_Exception(vsprintf(__("数据已经在%s被更新了, 你编辑的数据是旧的，请刷新后重试"), array($model->get_version_value())));
		}

		return $this->_handle_post();
	}

	/**
	 * 删除资源
	 *
	 * @access public
	 * @author liizii, <libol007@gmail.com>
	 * @return YZE_IResponse
	 */
	public final function do_Delete(){
		do_action(YZE_HOOK_BEFORE_DELETE, $this);
		$request = YZE_Request::get_instance();
		$session = YZE_Session_Context::get_instance();
		return $this->_handle_post();
	}
	
	public final function do_exception(YZE_RuntimeException $e){
		do_action(YZE_HOOK_BEFORE_EXCEPTION, $this);
		$request 	= YZE_Request::get_instance();
		$session	= YZE_Session_Context::get_instance();
		
		$response = $this->exception($e);
		
		if ( ! $response) {
			$response = $this->getResponse(null, "error");
		}
		if(is_a($response, "YZE_View_Adapter")){
			$response->check_view();
		}
		
		if(@$_SERVER['HTTP_X_YZE_NO_CONTENT_LAYOUT'] == "yes"){
			$this->layout = "";
		}
		
		return $response;
	}
	/**
	 * @return YZE_IResponse
	 */
	public function get(){
		//pass
	}
	/**
	 * @return YZE_IResponse
	 */
	public function post(){
		//pass
	}
	/**
	 * @return YZE_IResponse
	 */
	public function delete(){
		//pass
	}
	/**
	 * @return YZE_IResponse
	 */
	public function put(){
		//pass
	}
	
	/**
	 * 根据控制器的处理逻辑，清空控制器在会话上下文中保存的数据，根据情况调用以下的方法
	 * YZE_Session_Context::get_instance()->clear_controller_datas(get_class($this));
	 * YZE_Session_Context::get_instance()->clear_controller_exception(get_class($this));
	 * 
	 */
	public function cleanup(){
		//pass
	}
	
	/**
	 * 出现异常后的处理
	 * 
	 * @author leeboo
	 * 
	 * @param Exception $e
	 * 
	 * @return YZE_IResponse
	 */
	public function exception(YZE_RuntimeException $e){
		
	}


	/**
	 * 处理post请求
	 * @throws YZE_Resource_Not_Found_Exception
	 * @throws YZE_Form_Token_Validate_Exception
	 */
	private function _Handle_Post()
	{
		$session = YZE_Session_Context::get_instance();
		$request = YZE_Request::get_instance();

		$method = $request->the_method();
		if (!method_exists($this, $method)) {
			throw new YZE_Resource_Not_Found_Exception($method);
		}
		//防止表单重复提交

		$this->_check_request_token($request->get_from_post('yze_request_token'));

		$response = $this->$method();
		
		//如果控制器中的方法没有return Redirect，默认通过get转到当前的uri
		if (!$response) {
			$response = new YZE_Redirect($request->the_uri(), $this);
		}

		//成功处理，清除保存的post数据
		$session->clear_post_datas(get_class($this));
		$session->clear_request_token_ext(get_class($this));

		if(@$_SERVER['HTTP_X_YZE_NO_CONTENT_LAYOUT'] == "yes"){
			$this->layout = "";
		}

		return $response;
	}

	private function _check_request_token($post_request_token)
	{
		$session = YZE_Session_Context::get_instance();
		$request = YZE_Request::get_instance();
		$saved_token = $session->get_request_token(get_class($this));

		//uri1中的表单提交到uri2中的情况
		$source_controller = YZE_Session_Context::get_instance()->get(get_class($this)." from:");
		$refer_saved_token = $session->get_request_token($source_controller);
		YZE_Session_Context::get_instance()->destory(get_class($this)." from:");
		
		$filtered_data  = do_filter(YZE_FILTER_BEFORE_CHECK_REQUEST_TOKEN, array("saved_token"=>$saved_token, "post_request_token"=>$post_request_token));
		$saved_token    = $filtered_data['saved_token'];
		$post_request_token = $filtered_data['post_request_token'];

		if (!$post_request_token) {
			throw new YZE_Form_Token_Validate_Exception(__("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试(MISSING_POST_REQUEST_TOKEN)。"));
		}
		//$saved_token：j7ffqj40saoqerojp0pukrbar3_1300802801 $post_request_token：j7ffqj40saoqerojp0pukrbar3_1300802799
		//TODO 为什么会差1-2ms???
		if (strcasecmp($saved_token, $post_request_token)!=0 && strcasecmp($refer_saved_token, $post_request_token)!=0 ) {
			YZE_Object::log("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试。saveed_token: $saved_token post_request_token: $post_request_token");
			throw new YZE_Form_Token_Validate_Exception(__("请求验证失败，出现该提示的原因可能是您点击过快，或者长时间没有操作，请重试(REQUEST_TOKEN_NOT_MATCH)。"));
		}
	}
}
class YZE_Default_Controller extends YZE_Resource_Controller{
	public function get(){
		$this->set_View_Data("yze_page_title", __("Yangzie 简单的PHP开发框架"));
		return new YZE_Simple_View(YANGZIE."/welcome", $this->get_datas(), $this);
	}
}
class YZE_Exception_Controller extends YZE_Resource_Controller{
	public function get(){
		$this->layout = "error";
		$this->output_status_code($this->exception ? $this->exception->getCode() : 0);
		if(defined("YZE_DEVELOP_MODE") && YZE_DEVELOP_MODE ){
			return new YZE_Simple_View(YANGZIE."/exception", array("exception"=>$this->exception), $this);
		}
		
		
		
		if(!$this->exception){
			return new YZE_Simple_View(YZE_APP_VIEWS_INC."500",  array("exception"=>$this->exception), $this);
		}
		
		return new YZE_Simple_View(YZE_APP_VIEWS_INC.$this->exception->getCode(), array("exception"=>$this->exception), $this);
	}
	
	public function exception(YZE_RuntimeException $e){
		$this->exception = $e;
		return $this->get();
	}
	
	private $exception;
	
	private function output_status_code($error_number){
		switch ($error_number){
			case 500: header("HTTP/1.0 500 Internal Server Error"); return;
			case 404: header("HTTP/1.0 404 Not Found");return;
		}
	}
}
?>