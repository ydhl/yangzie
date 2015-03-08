<?php
namespace app\user;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package User
 */
class User_Module extends YZE_Base_Module{
    public $auths = array();
    public $no_auths = array();
    protected function _config(){
        return array(
        'name'=>'User',
        'routers' => array(
        	'user/all'	=> array(
			'controller'	=> 'index',
        		'args'	=> array(
        		   'action'=>"all"
        		),
        	),
            'user/add2'	=> array(
                    'controller'	=> 'add',
                    'args'	=> array(
                            'action'=>"add2"
                    ),
            ),
        )
        );
    }
}
?>