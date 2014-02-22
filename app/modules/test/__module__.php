<?php
namespace app\test;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Test
 */
class Test_Module extends YZE_Base_Module{
    public $auths = array();
    public $no_auths = array();
	protected function _config(){
		return array(
			'name'	=> 'Test',
			'routers'	=> array(
				'test'	=> array(
					'controller'	=> 'admin',
					'args'	=> array(
					),
				),
			),
		);
	}
}
?>