<?php
/**
 *
 * @version $Id$
 * @package default
 */
class Resource_Not_Found_Exception_Controller extends Resource_Controller {

	public function get(){
		$this->set_View_Data("page_title", __("您所访问的资源不存在"));
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

	private $exception;
	protected $module_name = "default";
	protected $models = array();
	public function set_exception(Exception $e){
		$this->exception = $e;
	}
}
?>