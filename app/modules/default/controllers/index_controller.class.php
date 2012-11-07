<?php

class Index_Controller extends Resource_Controller {
	/**
	 * 主页
	 */
	public function get(){
		$this->set_View_Data("page_title", __("Yangzie 简单的PHP开发框架"));
	}
	
	public function post()
	{
		$request = Request::get_instance();
		$name = $request->get_from_post("name");
		$this->set_View_Data("name", $name);
	}
	
	protected $module_name = "default";
	
	protected $models = array(

	);
}
?>
