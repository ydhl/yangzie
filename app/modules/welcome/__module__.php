<?php
/**
 *
 * @version $Id$
 * @package Welcome
 */
class Welcome_Module extends YZE_Base_Module{
	public $auths = array();
	public $no_auths = array();
	protected function _config(){
		return array(
				'name'=>'Welcome',
				'include_path'=>array(),#加载模块时设置自动包含的路径
				'include_files'=>array(),#加载模块时要自动包含的文件
				'routers' => array(
				)
		);
	}
}
?>