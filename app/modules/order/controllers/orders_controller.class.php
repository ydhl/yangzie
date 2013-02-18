<?php
/**
*
* @version $Id$
* @package order
*/
class Orders_Controller extends YZE_Resource_Controller {
		
	public function get(){
		//Your Code Written in Here.
				
		$this->set_view_data("yze_page_title", "this is controller orders");
	}
			
	public function post(){
		//Your Code Written in Here.
				
		
	}
			
	public function delete(){
		//Your Code Written in Here.
				
		
	}
			
	public function put(){
		//Your Code Written in Here.
				
		
	}
	
	public function get_response_guid(){
		//如果该控制器的响应输出需要缓存，这里返回生成缓存文件的唯一id
		//该id根据请求的输入参数生成
		return null;
	}
	protected function post_result_of_ajax(){
		//这里返回该控制器在ajax请求时返回地数据
		return array();
	}
	protected $module_name = "order";
	protected $models = array();
	
}
?>