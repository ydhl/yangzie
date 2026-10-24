<?php
namespace app\test;

use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;

/**
 * 自定义路由映射演示控制器。
 *
 * ai@2026-09-22 演示 Test_Module::config() 中 'routers' 配置的两个要点：
 *   1. 动态 path 参数：uri 里的 (?P<id>\d+) 命名捕获组 → $request->get_var('id')
 *   2. 静态 args 参数：args 里写死的键值对 → $request->get_var('source') 等
 *
 * 两者的区别：
 *   - path 参数由**用户访问的地址**决定，每次请求可以不同；
 *   - args 参数由**配置写死**，有多条 uri 映射到同一个 action 时用它区分来源。
 * 在框架内部它们实现上没有区别：都会合并进 YZE_Request::$vars，
 * 所以都用 get_var() 取值（见 yangzie/request.php 的 parse_url()）。
 *
 * 路由对应关系（见 app/modules/test/__config__.php）：
 *   GET /test/demo/order/(?P<id>\d+) → Route_Controller::order()
 *   GET /test/demo/about             → Route_Controller::about()
 */
class Route_Controller extends YZE_Resource_Controller {

    /**
     * GET /test/demo/order/1234 —— 展示「动态 path 参数 + 静态 args 参数」
     *
     * @return void
     */
    public function order() {
        $request = $this->request;

        // 动态 path 参数：来自 uri 中 (?P<id>\d+) 的捕获
        $id = $request->get_var('id');

        // 静态 args 参数：来自模块配置里写死的 args
        $source  = $request->get_var('source');
        $channel = $request->get_var('channel');

        // 对比：查询字符串参数属于 GET 参数，用 get_from_get（不是 get_var）
        $from = $request->get_from_get('from', '（未传 ?from=）');

        $this->set_view_data('yze_page_title', '自定义路由 - 订单详情');
        $this->set_view_data('uri', $request->the_uri());
        $this->set_view_data('id', $id);
        $this->set_view_data('source', $source);
        $this->set_view_data('channel', $channel);
        $this->set_view_data('from', $from);
    }

    /**
     * GET /test/demo/about —— 展示「只有静态 args 参数」的情况
     *
     * @return void
     */
    public function about() {
        $request = $this->request;

        // 该路由没有动态参数，get_var('name') 取到的是 args 里配置的值
        $name    = $request->get_var('name');
        $version = $request->get_var('version');

        $this->set_view_data('yze_page_title', '自定义路由 - 关于');
        $this->set_view_data('uri', $request->the_uri());
        $this->set_view_data('name', $name);
        $this->set_view_data('version', $version);
    }
}
?>