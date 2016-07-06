<?php
namespace app\test;
use \yangzie\YZE_Model;
use \yangzie\YZE_SQL;
use \yangzie\YZE_DBAException;
use \yangzie\YZE_DBAImpl;

/**
*
*
* @version $Id$
* @package test
*/
class User_Model_Model extends YZE_Model{
    
    const TABLE= "users";
    const VERSION = 'modified_on';
    const MODULE_NAME = "test";
    const KEY_NAME = "id";
    const CLASS_NAME = 'app\test\User_Model_Model';
    
    /**
     * 
     * @var integer
     */
    const F_ID = "id";
    /**
     * 
     * @var string
     */
    const F_NAME = "name";
    /**
     * 
     * @var string
     */
    const F_PSW = "psw";
    /**
     * 
     * @var string
     */
    const F_CODE_KEY = "code_key";
    /**
     * 
     * @var string
     */
    const F_BUG_KEY = "bug_key";
    /**
     * 
     * @var string
     */
    const F_MAIL_KEY = "mail_key";
    /**
     * 
     * @var string
     */
    const F_NICKNAME = "nickname";
    /**
     * 
     * @var integer
     */
    const F_LEVEL = "level";
    /**
     * 
     * @var string
     */
    const F_ROLE = "role";
    /**
     * 
     * @var string
     */
    const F_EMAIL = "email";
    /**
     * 积分
     * @var integer
     */
    const F_POINT = "point";
    /**
     * 
     * @var integer
     */
    const F_ENABLED = "enabled";
    /**
     * 
     * @var string
     */
    const F_AVATAR = "avatar";
    /**
     * 
     * @var integer
     */
    const F_DEPARTMENT_ID = "department_id";
    /**
     * 
     * @var string
     */
    const F_PHONE = "phone";
    /**
     * 
     * @var string
     */
    const F_WXID = "wxid";
    /**
     * 
     * @var string
     */
    const F_ADDRESS = "address";
    /**
     * 
     * @var string
     */
    const F_WXOPENID = "wxopenid";
    /**
     * 
     * @var string
     */
    const F_QQ = "qq";
    /**
     * 
     * @var string
     */
    const F_SKYPE = "skype";
    /**
     * 
     * @var string
     */
    const F_JOB = "job";
    /**
     * 
     * @var string
     */
    const F_CODE = "code";
    /**
     * 
     * @var string
     */
    const F_GENDER = "gender";
    /**
     * 
     * @var integer
     */
    const F_IS_CUSTOMER = "is_customer";
    /**
     * 
     * @var date
     */
    const F_BIRTHDAY = "birthday";
    /**
     * 
     * @var date
     */
    const F_CREATED_ON = "created_on";
    /**
     * 
     * @var date
     */
    const F_MODIFIED_ON = "modified_on";
    public static $columns = array(
               'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
       'name'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'psw'        => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'code_key'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'bug_key'    => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'mail_key'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'nickname'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'level'      => array('type' => 'integer', 'null' => false,'length' => '4','default'	=> '0',),
       'role'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'email'      => array('type' => 'string', 'null' => true,'length' => '200','default'	=> '',),
       'point'      => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '0',),
       'enabled'    => array('type' => 'integer', 'null' => false,'length' => '1','default'	=> '1',),
       'avatar'     => array('type' => 'string', 'null' => false,'length' => '129','default'	=> '',),
       'department_id' => array('type' => 'integer', 'null' => true,'length' => '11','default'	=> '',),
       'phone'      => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'wxid'       => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'address'    => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'wxopenid'   => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'qq'         => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'skype'      => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'job'        => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'code'       => array('type' => 'string', 'null' => true,'length' => '129','default'	=> '',),
       'gender'     => array('type' => 'string', 'null' => true,'length' => '45','default'	=> '',),
       'is_customer' => array('type' => 'integer', 'null' => false,'length' => '4','default'	=> '0',),
       'birthday'   => array('type' => 'date', 'null' => true,'length' => '','default'	=> '',),
       'created_on' => array('type' => 'date', 'null' => true,'length' => '','default'	=> '',),
       'modified_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),

    );
    //array('attr'=>array('from'=>'id','to'=>'id','class'=>'','type'=>'one-one||one-many') )
    //$this->attr
    protected $objects = array();
    /**
     * @see YZE_Model::$unique_key
     */
    protected $unique_key = array (
  'id' => 'PRIMARY',
  'department_id' => 'fk_users_customer_departments1_idx',
);
}?>