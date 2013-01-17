<?php
/**
 *
 * @version $Id$
 * @package order
 */
class User{
	private $model;
	
	public function User($key){
		$this->model = Model::find($key, 'User_Model');
	}
	
	
}?>