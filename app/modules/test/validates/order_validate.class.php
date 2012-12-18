<?php
/**
 *
 * @version $Id$
 * @package test
 */
class Order_Validate extends YZEValidate{
	
	public function init_get_validates(){
		$this->set_validate_rule('get', 'name', VALIDATE::REGEX, '/\d{5}/', '出错的消息');

		//Written Get Validate Rules Code in Here. such as
		//$this->set_validate_rule('get', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_post_validates(){
		$this->set_validate_rule('post', 'name', VALIDATE::REGEX, '/\d{5}/', '出错的消息');

		//Written Get Validate Rules Code in Here. such as
		//$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_put_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_delete_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->set_validate_rule('post', 'params name in url', 'validate method name', '', 'error message');
	}
}?>