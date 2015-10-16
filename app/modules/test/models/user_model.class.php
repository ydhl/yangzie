<?php

namespace app\test;

use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;

/**
 *
 * @version $Id$
 * @package test
 *         
 */
class User_Model extends YZE_Model {
    const TABLE = "users";
    const VERSION = 'modified_on';
    const MODULE_NAME = "test";
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
       'level'      => array('type' => 'integer', 'null' => false,'length' => '4','default'	=> '0',),
       'email'      => array('type' => 'string', 'null' => true,'length' => '200','default'	=> '',),
       'point'      => array('type' => 'integer', 'null' => false,'length' => '10','default'	=> '0',),
       'avatar'     => array('type' => 'string', 'null' => true,'length' => '501','default'	=> '',),
       'enabled'    => array('type' => 'integer', 'null' => false,'length' => '4','default'	=> '1',),
       'department_id' => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
       'phone'      => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'wxid'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'address'    => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'wxopenid'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),

    );
    
    /**
     * fdfdfd
     */
    public function test(){
        
    }
}
?>