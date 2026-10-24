<?php
namespace app\test;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
/**
 *
 * @version $Id$
 * @package Test
 */
class Test_Module extends YZE_Base_Module{
    /**
     * 需要登录认证的资源。控制器名 => action 匹配规则（支持 * / 正则）。
     *
     * ai@2026-09-21 演示登录保护：/test/dashboard/* 全部需要登录；
     * 访问未登录用户将被重定向到 /test/signin/login（见 app/hooks/auth.php 中
     * YZE_HOOK_NEED_SIGNIN 的实现）。
     *
     * @var array<string,string>
     */
    public array $auths = [
        'dashboard' => '*',
        // ai@2026-09-22 ACL 演示：acl 控制器下所有 action 都要登录才能触发 ACL 检查，
        // 这样能在受认证保护的前提下展示 ACO/ARO 匹配规则。
        // 切换演示角色的入口 /test/acl/switch_role 由 yze_get_ignore_acos() 跳过 ACL，
        // 但仍然受本条 $auths 保护（必须登录）。
        'acl'       => '*',
    ];

    /**
     * 不需要登录认证的资源，优先级高于 auths（同 controller / method 名匹配时
     * 由 no_auths 放行，见 yangzie/request.php:630-643 的 need_auth 判断顺序）。
     *
     * - signin：登录页自身要被排除掉，否则会形成"未登录→跳登录页→登录页也被拦"
     *   的死循环。
     * - dashboard：默认 dashboard/* 在上方 $auths 里全部要求登录，但
     *   '^public_' 用于演示"局部豁免"：以 public_ 命名的 action 即便属于 dashboard
     *   控制器，也能免登录访问。新增 dashboard 下不需要登录的 action 时，
     *   沿用 public_ 前缀即可，不要随便放宽整个控制器。
     *
     * @var array<string,string>
     */
    public array $no_auths = [
        'signin'    => '*',
        'dashboard' => '^public_',
    ];

    protected function config(): array{
        return [
        'name'=>'Test',
        /**
         * 自定义路由映射。
         *
         * ai@2026-09-22 演示自定义映射路径 + 两种参数：
         *   1. 动态 path 参数：uri 里的 (?P<id>\d+) 是命名正则捕获组，
         *      匹配到的值会以 id 为键放进 path 参数，控制器里用 $request->get_var('id') 获取；
         *   2. 静态 args 参数：args 里写死的键值对，同样通过 $request->get_var('键名') 获取，
         *      常用于多条 uri 映射到同一个 action 时区分来源。
         *
         * 注意：uri 不能以 / 开头或结尾，且要写完整路径（含模块名），
         *      因为框架是用 ^/{uri}/?$ 去匹配请求 path 的。
         */
        'routers' => [
            // 示例一：带动态 path 参数 + 静态 args
            // 访问 /test/demo/order/1234 → Route_Controller::order()
            'test/demo/order/(?P<id>\d+)' => [
                'controller' => 'route',
                'action'     => 'order',
                'args'       => [
                    'source'  => 'custom-router', // 静态参数：写死在配置里
                    'channel' => 'demo',
                ],
            ],
            // 示例二：只有静态 args，没有动态参数
            // 访问 /test/demo/about → Route_Controller::about()
            'test/demo/about' => [
                'controller' => 'route',
                'action'     => 'about',
                'args'       => [
                    'name'    => 'Yangzie',
                    'version' => '1.0',
                ],
            ],
        ]
        ];
    }
    public function js_bundle(string $bundle): array
    {
        // TODO: Implement js_bundle() method.
        return [];
    }

    public function css_bundle(string $bundle): array
    {
        // TODO: Implement css_bundle() method.
        return [];
    }
}
?>