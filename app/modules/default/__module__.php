<?php
/**
 *
 * @version $Id$
 * @package Default
 */
class Default_Module extends Base_Module{
    public $auths = array();
    public $no_auths = array();
	protected function _config(){
		return array(
			'name'	=> 'Default',
			'include_path'	=> array(
			),
			'include_files'	=> array(
			),
			'routers'	=> array(
				'/'	=> array(
					'controller'	=> 'default',
					'args'	=> array(
					),
				),
			),
		);
	}
}
?>