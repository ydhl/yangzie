<?php
namespace app\hello;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Hello
 */
class Hello_Module extends YZE_Base_Module{
    public $auths = array();
    public $no_auths = array();
    protected function _config(){
        return array(
        'name'=>'Hello',
        'routers' => array(
        	/*'uri'	=> array(
			'controller'	=> 'controller name',
        		'args'	=> array(
        		),
        	),*/
        )
        );
    }
}
?>