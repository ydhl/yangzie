<?php
/**
 *
 * @version $Id$
 * @package Order
 */
class Order_Module extends Base_Module{
    public $auths = array();
    public $no_auths = array();
	protected function _config(){
		return array(
			'name'	=> 'Order',
			'include_path'	=> array(
			),
			'include_files'	=> array(
			),
			'routers'	=> array(
				'/orders/?'	=> array(
					'controller'	=> 'orders',
					'args'	=> array(
					),
				),
			),
		);
	}
}
?>