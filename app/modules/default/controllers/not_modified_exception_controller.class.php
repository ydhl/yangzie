<?php
/**
 *
 * @version $Id$
 * @package default
 */
class Not_Modified_Exception_Controller extends Resource_Controller {
		
	public function get(){
		return new Response_304_NotModified(array('Cache-Control'=>'','Expires'=>''), $this);
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