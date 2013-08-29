<?php
/**
 *
 * @version $Id$
 * @package welcome
 */
class Form_Validate extends YZEValidate{
	
	public function init_get_validates(){
		
		//Written Get Validate Rules Code in Here. such as
		//$this->set_validate_rule('get', 'params name in url', 'validate method name', '', 'error message');
	}
	
	public function init_post_validates(){
		//Written Get Validate Rules Code in Here. such as
		$this->set_validate_rule('post', 'email', YZEValidate::IS_EMAIL, '', '登录名必须是email');
		$this->set_validate_rule('post', 'psw', YZEValidate::NOT_EMPTY, '', '密码不能为空');
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