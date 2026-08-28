<?php
namespace app\graphql;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * 该模块是框架自带graphql接口的访问入口
 * @version $Id$
 * @package Graphql
 */
class Graphql_Module extends YZE_Base_Module{
    public array $auths = [];
    public array $no_auths = [];
    protected function config(): array{
        return [
            'name'=>'Graphql',
            'routers' => []
        ];
    }
    public function js_bundle(string $bundle): array
    {
        return [];
    }

    public function css_bundle(string $bundle): array
    {
        return [];
    }
}
?>
