<?php
namespace app\welcome;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;
use \yangzie\YZEValidate;
/**
 *
 * @version $Id$
 * @package welcome
 */
class Index_Validate extends YZEValidate{
	
	public function init_get_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		$this->assert('name', 'test_name', '', 'miss arg name');
	}
	protected function test_name($method,$name,$rule){
		$data = $this->get_datas($method);
		if( @$data[$name]!="test"){
			$this->set_error_message($name, $this->validates[$name]['message']);
			return false;
		}
		return true;
	}
	public function init_post_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->assert('params name in post', 'validate method name', '', 'error message');
	}
	
	public function init_put_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->assert('params name in post', 'validate method name', '', 'error message');
	}
	
	public function init_delete_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->assert('params name in post', 'validate method name', '', 'error message');
	}
}?>