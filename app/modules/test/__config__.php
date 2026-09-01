<?php
namespace app\test;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Test
 */
class Test_Module extends YZE_Base_Module{
    public array $auths = array();
    public array $no_auths = array();
    protected function config(): array{
        return [
        'name'=>'Test',
        'routers' => [
        	//'uri'	=> [
			//	'controller' => 'controller name',
			//	'action' => 'action name',
			// //通过$request->get_var('foo')获取
        	//	'args'	=> [
        	//		"foo" =>  "bar"
        	//	],
        	//],
        	]
        ];
    }
    public function js_bundle(string $bundle): array
    {
        // TODO: Implement js_bundle() method.
    }

    public function css_bundle(string $bundle): array
    {
        // TODO: Implement css_bundle() method.
    }
}
?>