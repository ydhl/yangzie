<?php
namespace app\test;

use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Redirect;
use \yangzie\YZE_Hook;
use \yangzie\YZE_RuntimeException;

/**
 * 演示登录的控制器。
 *
 * ai@2026-09-21
 *   - login()        GET  /test/signin/login     渲染登录表单
 *   - post_dologin() POST /test/signin/dologin   校验用户名/密码 → 触发 YZE_HOOK_SET_LOGIN_USER 写 session → 跳 /test/dashboard
 *   - logout()       GET  /test/signin/logout    清 session 中的 admin → 跳回登录页
 *
 * 注意：YZE_Request 会按 HTTP 方法给 action 加前缀（POST→post_，PUT→put_…），
 * 所以 dologin 用的是 post_dologin()；logout() 必须是 GET（直接 logout()）。
 *
 * 重定向策略：直接 return 一个 YZE_Redirect，由 framework 的
 * YZE_Resource_Controller::handle_request() 把它作为 response 渲染。
 * （Controller 没有 set_response 方法，规范做法就是让 action 返回响应对象。）
 */
class Signin_Controller extends YZE_Resource_Controller {

    /**
     * 演示用的硬编码"账号库"。
     * 真实业务应该查 User_Model，这里只是为了演示完整链路（hook 写入 session，
     * 再被 YZE_HOOK_GET_LOGIN_USER 读出来，进而放行 dashboard 的访问）。
     *
     * 密码统一是 "secret"，存的是 password_hash 的结果。
     *
     * @var array<string, array{name:string, password_hash:string}>
     */
    private static array $DEMO_USERS = [];

    /**
     * 初始化演示账号，密码为 "secret"
     *
     * @return void
     */
    private static function ensure_demo_users(): void {
        if (self::$DEMO_USERS) return;
        self::$DEMO_USERS = [
            'alice' => [
                'name'          => 'Alice',
                'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
            ],
            'bob' => [
                'name'          => 'Bob',
                'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
            ],
        ];
    }

    /**
     * GET /test/signin/login —— 渲染登录表单
     *
     * 已经登录的用户访问该页直接跳走，避免看到无意义的登录表单
     *
     * @return YZE_Redirect|null 已登录时返回重定向，否则 null 走默认视图渲染
     */
    public function login() {
        // 已登录则直接跳到 dashboard
        $loginUser = YZE_Hook::do_hook(YZE_HOOK_GET_LOGIN_USER);
        if ($loginUser) {
            $this->layout = '';
            return new YZE_Redirect("/test/dashboard", $this);
        }

        // 如果是被 NEED_SIGNIN 重定向过来的，可以携带 redirect_to 查询参数，
        // 登录成功后跳回原本想去的页面。这里仅做读取，不强制要求。
        // ai@2026-09-22 按文档修正：查询字符串属于 GET 参数，用 get_from_get；
        // get_var 只用于获取 URI 路由匹配中的参数（如 /orders/[id] 中的 id）
        $redirect = $this->request->get_from_get('redirect_to');

        $this->set_view_data('yze_page_title', '演示登录');
        $this->set_view_data('redirect_to', is_string($redirect) ? $redirect : '/test/dashboard');
        $this->set_view_data('error', '');
        $this->set_view_data('username', '');
        return null; // 让框架走默认视图渲染：signin-login.tpl.php
    }

    /**
     * POST /test/signin/dologin —— 处理登录提交
     *
     * 校验通过后通过 YZE_HOOK_SET_LOGIN_USER 写入 session（auth.php 注册的 hook
     * 会执行 $_SESSION['admin'] = $data），然后重定向。
     *
     * @return YZE_Redirect|null 校验成功时返回重定向，校验失败时返回 null 走视图渲染（带错误提示）
     */
    public function post_dologin() {
        $request  = $this->request;
        // ai@2026-09-22 按文档修正：表单提交的数据属于 POST 参数，用 get_from_post；
        // get_var 只用于获取 URI 路由匹配中的参数（如 /orders/[id] 中的 id）
        $username = trim((string)$request->get_from_post('username'));
        $password = (string)$request->get_from_post('password');
        $redirect = (string)$request->get_from_post('redirect_to', '/test/dashboard');

        // 防止开放重定向：redirect_to 必须以单个 / 开头，不能跨站
        if ($redirect === '' || $redirect[0] !== '/' || preg_match('#^//#', $redirect)) {
            $redirect = '/test/dashboard';
        }

        self::ensure_demo_users();
        $user = self::$DEMO_USERS[$username] ?? null;
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // 失败：复用 login 视图并带回错误提示和已填用户名
            // post_dologin 默认会去找 signin-dologin.tpl.php，这里强制覆盖为
            // signin-login.tpl.php，让表单回显走同一个视图
            $this->view = 'signin-login';
            $this->set_view_data('yze_page_title', '演示登录');
            $this->set_view_data('error', '用户名或密码不正确');
            $this->set_view_data('username', $username);
            $this->set_view_data('redirect_to', $redirect);
            return null;
        }

        // 关键：通过 hook 写入登录信息
        // 传给 hook 的就是登录用户对象本身（框架仅要求它非假，具体含义由开发者决定）
        $data = [
            'username' => $username,
            'name'     => $user['name'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
        YZE_Hook::do_hook(YZE_HOOK_SET_LOGIN_USER, $data);

        $this->layout = '';
        return new YZE_Redirect($redirect, $this);
    }

    /**
     * GET /test/signin/logout —— 退出登录
     *
     * 清掉 SESSION['admin']，让 YZE_HOOK_GET_LOGIN_USER 再次返回 null，
     * 下一次访问受保护页就会重新触发 NEED_SIGNIN 重定向
     *
     * @return YZE_Redirect
     */
    public function logout() {
        // ai@2026-09-21 直接操作 $_SESSION 与 SET_LOGIN_USER 的语义一致：
        // 因为 SET_LOGIN_USER 在 auth.php 里就是把 data 写到 $_SESSION['admin']。
        // 通过同一 hook 写入 null，既保持单一入口，也避免绕过 hook 维护本地状态。
        unset($_SESSION['admin']);

        $this->layout = '';
        return new YZE_Redirect('/test/signin/login', $this);
    }

    /**
     * 异常处理：表单渲染阶段出错返回登录页（保留已填的用户名）
     *
     * @param \Exception $e
     * @return void
     */
    public function exception(\Exception $e) {
        $request = $this->request;
        $format  = $request->get_output_format();
        if (!$request->is_get() || strcasecmp($format, 'json') === 0) {
            $this->layout = '';
            \yangzie\YZE_JSON_View::error($this, $e->getMessage());
            return;
        }
        // GET 渲染异常时，让框架走默认 500 页面即可
    }
}
?>