<?php
/**
*
* @version $Id$
* @package welcome
*/
class Index_Controller extends YZE_Resource_Controller {
	
	public function get(){
		
		$this->set_View_Data("yze_page_title", __("Yangzie 简单的PHP开发框架"));
	}
	
	
	public function post(){
		$request 		= YZE_Request::get_instance();
		$name 			= $request->get_from_post("name");
		$unresumable_exception 	= $request->get_from_post("unresumable_exception");
		$resumable_exception 	= $request->get_from_post("resumable_exception");
		$this->set_View_Data("name", $name);
		if($unresumable_exception){
			throw new YZE_Resource_Not_Found_Exception("出现了不可回复的异常");
		}
		if($resumable_exception){
			throw new YZE_RuntimeException("出现了可回复的异常了. 重新刷新，异常消失");
		}
	}
	
	
	public function delete(){
		$request = YZE_Request::get_instance();
	}
	
	
	public function put(){
		$request = YZE_Request::get_instance();
	}
	
	
	public function exception(YZE_RuntimeException $e){
		$request = YZE_Request::get_instance();
		if ($e->isResumeable()){
			return $this->get();
		}
	
		$error_controller = new YZE_Exception_Controller();
		return $error_controller->do_exception($e);
	}
	
	public function get_response_guid(){
		//如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
		return null;
	}

	protected $module_name = "welcome";
	
}
?>