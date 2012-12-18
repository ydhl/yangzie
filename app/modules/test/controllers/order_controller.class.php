<?php
/**
*
* @version $Id$
* @package test
*/
class Order_Controller extends Resource_Controller {
		
	public function get(){
		$name	= $this->request->get_from_get('name');

		$id	= $this->request->get_var('id');

		//Your Code Written in Here.
				
		$this->set_view_data(Yangzie_Const::PAGE_TITLE, "set page title");
	}
			
	public function post(){
		$name	= $this->request->get_from_post('name');

		$id	= $this->request->get_var('id');

		//Your Code Written in Here.
				
		
	}
			
	public function delete(){
		
		$id	= $this->request->get_var('id');

		//Your Code Written in Here.
				
		
	}
			
	public function put(){
		
		$id	= $this->request->get_var('id');

		//Your Code Written in Here.
				
		
	}
	
	protected $module_name = "test";
	protected $models = array();
}
?>