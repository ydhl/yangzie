<?php
/**
 *
 * @version $Id$
 * @package Test
 */
class Test_Module extends Base_Module{
	protected function _config(){
		return array(
			'name'	=> 'Test',
			'include_path'	=> array(
			),
			'include_files'	=> array(
			),
			'routers'	=> array(
				'/orders/(?P<id>\d+)/?'	=> array(
					'controller'	=> 'order',
					'args'	=> array(
						0	=> 'r:id',
					),
				),
			),
		);
	}
}
?>