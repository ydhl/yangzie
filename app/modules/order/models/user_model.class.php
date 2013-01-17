<?php
/**
 * DO NOT MODIFY THIS FILE, THIS FILE IS AUTO GENERATE BY YDHL TOOL.
 * 
 * @version $Id$
 * @package order
 */
class User_Model extends Model{
	
	protected $table= "users";
	protected $version = 'modified_on';
	protected $module_name = "order";
	protected $key_name = "id";
	protected $columns = array(
	'id'=>array(
		'type'		=> 'integer',
		'null'		=> false,
		'length'	=> '11',
		'default'	=> '',
		'comment'	=> ''
	),
	'login'=>array(
		'type'		=> 'string',
		'null'		=> true,
		'length'	=> '45',
		'default'	=> '',
		'comment'	=> ''
	),
	'password'=>array(
		'type'		=> 'string',
		'null'		=> true,
		'length'	=> '45',
		'default'	=> '',
		'comment'	=> ''
	),
	'last_login_on'=>array(
		'type'		=> 'date',
		'null'		=> true,
		'length'	=> '',
		'default'	=> '',
		'comment'	=> ''
	),
	'login_ip'=>array(
		'type'		=> 'string',
		'null'		=> true,
		'length'	=> '45',
		'default'	=> '',
		'comment'	=> ''
	),
	'is_enabled'=>array(
		'type'		=> 'integer',
		'null'		=> false,
		'length'	=> '1',
		'default'	=> '1',
		'comment'	=> '?????'
	),
	'is_root'=>array(
		'type'		=> 'integer',
		'null'		=> false,
		'length'	=> '1',
		'default'	=> '0',
		'comment'	=> '???????'
	),
	'created_on'=>array(
		'type'		=> 'date',
		'null'		=> true,
		'length'	=> '',
		'default'	=> '',
		'comment'	=> ''
	),
	'modified_on'=>array(
		'type'		=> 'date',
		'null'		=> true,
		'length'	=> '',
		'default'	=> 'CURRENT_TIMESTAMP',
		'comment'	=> ''
	),);
	
	private $object;
	
	public function User_Model(){
		$this->object = new User();
	}
}?>