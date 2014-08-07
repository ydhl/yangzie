<?php
namespace app\order;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Order
 */
class Order_Module extends YZE_Base_Module{
    public $auths = array();
    public $no_auths = array();
    const TEST_HOOK = "order_test";
    protected function _config(){
        return array(
        'name'=>'Order',
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