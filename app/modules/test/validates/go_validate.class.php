<?php
namespace app\test;
use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Session_Context;
use \yangzie\YZE_RuntimeException;
use \yangzie\YZEValidate;
/**
 *
 * @version $Id$
 * @package test
 */
class Go_Validate extends YZEValidate{
	
	public function init_get_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->assert('params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_post_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->assert('params name in post', 'validate method name', '', 'error message');
	    $this->assert('name', YZEValidate::NOT_EMPTY, '', 'error message219的');
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