<?php
/**
 *
 * @version $Id$
 * @package default
 */
class Dbaexception_Controller extends Resource_Controller {
		
	public function get(){
		$this->set_View_Data("page_title", __("DB ERROR"));
		$this->set_view_data('exception', $this->exception);
	}
			
	public function post(){
		//TODO
		
	}
			
	public function delete(){
		//TODO
		
	}
			
	public function put(){
		//TODO
		
	}
	
    protected $module_name = "default";
    protected $models = array();
    private $exception;
	public function set_exception(Exception $e){
		$this->exception = $e;
	}
}
?>