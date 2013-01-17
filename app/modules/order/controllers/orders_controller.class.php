<?php
/**
*
* @version $Id$
* @package order
*/
class Orders_Controller extends Resource_Controller {
		
	public function get(){
		//Your Code Written in Here.
		new User(1);
		$this->set_view_data(Yangzie_Const::PAGE_TITLE, "this is controller orders");
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
	
	protected $module_name = "order";
	protected $models = array(
		'User',
	);
}
?>