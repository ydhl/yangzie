<?php
namespace app\user;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;

/**
 * 
 * 
 * @version $Id$
 * @package user
 */
class User_Model extends YZE_Model{
	
	const TABLE= "users";
	const VERSION = 'modified_on';
	const MODULE_NAME = "user";
	const KEY_NAME = "id";
	protected $columns = array(
       'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
       'name'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'psw'        => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'code_key'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'bug_key'    => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'mail_key'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'nickname'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'role'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'email'      => array('type' => 'string', 'null' => true,'length' => '200','default'	=> '',),
       'point'      => array('type' => 'integer', 'null' => false,'length' => '10','default'	=> '0',),

    );
    //array('attr'=>array('from'=>'id','to'=>'id','class'=>'','type'=>'one-one||one-many') )
	//$this->attr
	protected $objects = array();

}?>