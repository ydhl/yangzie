<?php
namespace app\test;

use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Hook;

/**
 * 演示"必须登录"的受保护资源。
 *
 * ai@2026-09-21
 *   - index() GET /test/dashboard   展示当前登录用户的信息
 *
 * 这个控制器在 Test_Module::$auths 中声明为需要登录（'dashboard' => '*'），
 * 因此当 YZE_HOOK_GET_LOGIN_USER 返回 null 时框架会自动抛 YZE_Need_Signin_Exception，
 * 然后由 YZE_HOOK_NEED_SIGNIN 重定向到 /test/signin/login。
 *
 * 只要走到 index()，就证明登录态校验链路（auths 配置 → GET_LOGIN_USER hook →
 * NEED_SIGNIN hook → 跳转 → SET_LOGIN_USER → 再次访问放行）已经完整跑通。
 */
class Dashboard_Controller extends YZE_Resource_Controller {

    /**
     * GET /test/dashboard —— 显示登录用户信息
     *
     * @return void
     */
    public function index() {
        $loginUser = YZE_Hook::do_hook(YZE_HOOK_GET_LOGIN_USER);

        // 防御性断言：理论上 auth 配置已经挡住未登录请求，正常情况一定拿得到用户。
        // 这里抛运行时异常，便于排查配置错误。
        if (!$loginUser) {
            throw new \yangzie\YZE_RuntimeException(
                '登录态丢失：模块已声明 dashboard 需要登录，但 GET_LOGIN_USER 返回空'
            );
        }

        $this->set_view_data('yze_page_title', 'Dashboard - 已登录');
        $this->set_view_data('user', $loginUser);
    }

    /**
     * GET /test/dashboard/public_demo —— 演示"不受登陆控制"的 action。
     *
     * 同一个控制器下：
     *   - index() 在 Test_Module::$auths 里声明为需要登录（dashboard => '*'），
     *     未登录访问会被 YZE_Request::need_auth() 拦截并抛出 NEED_SIGNIN；
     *   - public_demo() 通过 no_auths['dashboard'] = '^public_' 局部豁免，
     *     即便属于 dashboard 控制器也能免登录访问，方法名以 public_ 前缀
     *     是约定的"免登录 action" 命名规则。
     *
     * 这就是"auths 锁住整个控制器、no_auths 精确豁免个别 action"的标准用法。
     * 比直接把整个 dashboard 控制器从 auths 里拿掉更安全：
     *   新增的 dashboard action 默认仍然受 auths 保护，不会因为一时疏忽
     *   误开放敏感资源；想开新动作显式用 public_ 前缀即可。
     *
     * 本方法内不需要任何防御性断言，相反要演示的就是"没登录也能渲染"：
     * 视图里同时展示未登录 / 已登录两种状态，让读者直观对比两者的入口体验。
     *
     * @return void
     */
    public function public_demo() {
        $loginUser = YZE_Hook::do_hook(YZE_HOOK_GET_LOGIN_USER);

        $this->set_view_data('yze_page_title', 'Dashboard - 免登录入口');
        // 显式会传 null，让视图逻辑直接走"未登录"分支，不需要在视图里再判真假
        $this->set_view_data('user', $loginUser ?: null);
        // 把"对照入口"地址传给视图，方便页面里挂上跳转链接做对比
        $this->set_view_data('protected_action_url', '/test/dashboard');
        $this->set_view_data('login_url',           '/test/signin/login');
        $this->set_view_data('logout_url',          '/test/signin/logout');
    }
}
?>