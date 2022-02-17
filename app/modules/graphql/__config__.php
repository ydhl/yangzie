<?php
namespace app\graphql;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Graphql
 */
class Graphql_Module extends YZE_Base_Module{
    public $auths = [];
    public $no_auths = array();
    protected function _config(){
        return [
        'name'=>'Graphql',
        'routers' => []
        ];
    }
    public function js_bundle($bundle)
    {
        // TODO: Implement js_bundle() method.
    }

    public function css_bundle($bundle)
    {
        // TODO: Implement css_bundle() method.
    }
}
?>
