<?php
namespace app\test;

use \yangzie\YZE_Resource_Controller;
use \yangzie\YZE_Request;
use \yangzie\YZE_Simple_View;
use \yangzie\YZE_Notpl_View;
use \yangzie\YZE_JSON_View;
use \app\test\User_Card_View;

/**
 * 演示：在控制器中选择 / 构造响应视图的几种方式。
 *
 * 视图定位机制一览（核心代码在 yangzie/controller.php:71-98 的 get_Response()
 * 和 yangzie/view.php 的 YZE_Simple_View::__construct）：
 *
 *   ① 默认命名约定
 *      action 返回 null（或没返回值）时，框架按"控制器名-方法名"自动定位模板
 *      例：Acl_Controller::admin_tools → acl-admin_tools.tpl.php
 *      模板全路径：{module_view_path}/{controller}-{action}.tpl.php
 *
 *   ② $this->view 属性覆盖
 *      在 action 里赋值 $this->view = "其他模板名"; 同样让 action 返回 null，
 *      get_Response() 会走 controller.php:83-87 的覆盖分支而**跳过默认命名**。
 *      模板路径仍是 {module_view_path}/{this->view}.tpl.php，
 *      所以这里赋的值通常以"控制器名前缀"开头方便归类。
 *
 *   ③ 完全控制：action 直接 return 一个 YZE_IResponse
 *      handle_request() 看到非空返回值就直接拿它当响应对象（controller.php:184-187），
 *      完全跳过 get_Response()。可以返回：
 *        - new YZE_Simple_View($tpl_name, $data, $controller)
 *          走标准 .tpl.php 渲染，但模板名/路径完全自己挑（甚至可以脱离模块 views 目录）
 *        - new YZE_Notpl_View($html, $controller)
 *          不要模板文件，控制器自己生成最终字符串（view.php:547-583）
 *        - YZE_JSON_View::success($controller, $data)
 *          输出 {success:true, data:...} 的 JSON
 *
 * 4 种"选视图"方式对比：
 *   ┌─────────────────┬─────────────┬────────────────────────┐
 *   │ 方式             │ 是否要模板    │ 模板路径               │
 *   ├─────────────────┼─────────────┼────────────────────────┤
 *   │ 默认约定         │ 要          │ 模块 views/{c}-{a}.tpl │
 *   │ $this->view 覆盖 │ 要          │ 模块 views/{this->view}│
 *   │ return Simple   │ 要          │ 自己传任意路径          │
 *   │ return Notpl    │ 不要         │ 直接 echo 字符串        │
 *   └─────────────────┴─────────────┴────────────────────────┘
 */
class View_Controller extends YZE_Resource_Controller {

    /**
     * GET /test/view —— 演示首页：列出 6 种选视图的方式及对应入口。
     */
    public function index() {
        $this->set_view_data('yze_page_title', '视图选择演示');
    }

    /**
     * 方式 ① —— 默认命名约定。
     *
     * action 不返回任何东西；框架走 controller.php:186 的 get_Response()，
     * 用"控制器名-方法名"拼模板路径，命中 view-default_view.tpl.php。
     */
    public function default_view() {
        $this->set_view_data('yze_page_title', '方式① 默认命名约定');
        $this->set_view_data('hit',  'view-default_view.tpl.php');
        $this->set_view_data('note',
            'action 没 return；get_Response() 按"view-default_view"在模块 views 目录下找到本模板'
        );
    }

    /**
     * 方式 ② —— $this->view 属性覆盖。
     *
     * 给 $this->view 赋值后，get_Response() 走 controller.php:83-87 的覆盖分支，
     * 用 view-property_override_alt 而非 view-property_override 命名。
     */
    public function property_override() {
        $this->set_view_data('yze_page_title', '方式② $this->view 属性覆盖');
        $this->view = 'view-property_override_alt';
        $this->set_view_data('note',
            'action 里 $this->view = "view-property_override_alt" → 模板名被覆盖，'
            . '同一个 action 用哪个模板完全由控制器决定（适合"一个 action 多种呈现"的场景）'
        );
    }

    /**
     * 方式 ③ —— 运行时按请求参数挑模板。
     *
     * 和方式②本质相同（都是赋值 $this->view），但选择依据是请求参数：
     *   ?tpl=full    → view-runtime_select_full.tpl.php
     *   ?tpl=compact → view-runtime_select_compact.tpl.php
     */
    public function runtime_select() {
        $tpl = (string)$this->request->get_from_get('tpl', 'full');
        if (!in_array($tpl, ['full', 'compact'], true)) {
            $tpl = 'full';
        }
        $this->view = "view-runtime_select_{$tpl}";
        $this->set_view_data('yze_page_title', "方式③ 运行时按 ?tpl={$tpl} 选择模板");
        $this->set_view_data('chosen', $tpl);
        $this->set_view_data('note',
            '同一个 action 根据 GET 参数挑不同模板，业务逻辑共用，只换呈现层'
        );
    }

    /**
     * 方式 ④ —— return new YZE_Simple_View()，完全控制模板路径。
     *
     * handle_request() 看到非空返回值就不再调 get_Response()（controller.php:184-187），
     * 这里直接把模板路径拼出来交给 YZE_Simple_View，模板甚至可以脱离模块 views 目录。
     */
    public function return_full() {
        $this->set_view_data('yze_page_title', '方式④ 直接 return YZE_Simple_View');
        $this->set_view_data('built_at', date('H:i:s'));
        $this->set_view_data('note',
            'action return YZE_Simple_View → 模板路径完全自定，可来自任意目录'
        );

        return new YZE_Simple_View(
            $this->request->view_path() . '/view-return_full',
            $this->view_data,
            $this
        );
    }

    /**
     * 方式 ⑤ —— return new YZE_Notpl_View()，跳过模板文件直接输出字符串。
     *
     * 没有 .tpl.php 文件；view.php:571-573 的 Notpl_View::display_self 直接 echo 入参。
     * 适合"简单文本 / 片段 HTML / 调试输出"的场景。
     *
     * 注意：startup.php:111-115 仍然会把任何 YZE_View_Adapter 包一层 YZE_Layout，
     * 所以要真正"裸输出"还得 $this->layout = '' 把布局关掉（与方式 ⑥ 同理）。
     */
    public function return_raw() {
        $this->layout = '';  // 关掉 tpl 布局，否则 Notpl_View 也会被 html/body 包裹
        $html = '<div class="view-raw">'
              . '<h3>这是 action 直接 return 的裸 HTML</h3>'
              . '<p>YZE_Notpl_View 没有模板文件，控制器自己生成最终输出（view.php:547-583）。</p>'
              . '<p class="path">rendered at ' . htmlspecialchars(date('H:i:s'), ENT_QUOTES, 'UTF-8') . '</p>'
              . '</div>';
        return new YZE_Notpl_View($html, $this);
    }

    /**
     * 方式 ⑥ —— return YZE_JSON_View::success()，输出 JSON 数据。
     *
     * handle_request() 在 json 格式（URI 尾部 .json 后缀，如 /test/view/return_json.json；
     * 由 request.php:731 的正则解析，?format= 查询参数不受支持）下自动把 layout 置空
     * （controller.php:191-193）；这里显式置空也对普通 tpl 访问生效，让返回始终是裸 JSON。
     */
    public function return_json() {
        $this->layout = '';
        return YZE_JSON_View::success($this, [
            'controller_class' => __CLASS__,
            'method'           => __FUNCTION__,
            'tpl_kind'         => 'YZE_JSON_View',
            'tpl_file'         => '（无 .tpl.php）',
            'rendered_at'      => date('H:i:s'),
        ]);
    }

    /**
     * 方式 ⑦ —— 视图组件重用 · 简单文件包含（docs/yangzie.md:733-745）。
     *
     * 组件 user-card.tpl.php 放在模块 views 中，用 YZE_Simple_View 包含：
     * 第一个参数指向"模块 views 目录（$this->request->view_path()）+ 组件名"；
     * 系统级重用时把组件拷贝到 app/vendor/views，换成 YZE_APP_VIEWS_INC."user-card" 即可。
     * 第 4 个参数 $format 不传时按请求的输出格式自动找 user-card.{format}.php。
     *
     * 本页演示 get_output()：拿到组件渲染结果的字符串，嵌入本 action 自己的视图
     * （同一个组件在方式 ⑧ 里用 output() 直接作为整个响应输出）。
     */
    public function simple_view() {
        $this->set_view_data('yze_page_title', '视图组件 · 简单文件包含');

        $users = [
            ['username' => 'alice', 'name' => '爱丽丝', 'email' => 'alice@example.com'],
            ['username' => 'bob',   'name' => '小博',   'email' => 'bob@example.com'],
        ];
        $tags  = ['管理员', '编辑'];
        $cards = [];
        foreach ($users as $i => $u) {
            $view = new YZE_Simple_View(
                $this->request->view_path() . "/user-card",  // → modules/test/views/user-card.tpl.php
                ['user' => $u, 'tag' => $tags[$i]],
                $this
            );
            $cards[] = $view->get_output();             // 只要结果字符串，不直接输出
        }
        $this->set_view_data('cards', $cards);
    }

    /**
     * 方式 ⑧ —— 同一个模块 views 组件直接当整页响应（output()）。
     *
     * action return YZE_Simple_View（同方式④），但模板指向模块 views 里的组件；
     * $this->layout = '' 关掉 tpl 布局，浏览器看到的就只有组件自身的输出。
     */
    public function component_output() {
        $this->layout = '';
        return new YZE_Simple_View(
            $this->request->view_path() . "/user-card",
            [
                'user' => ['username' => 'charlie', 'name' => '查理', 'email' => 'charlie@example.com'],
                'tag'  => '访客',
            ],
            $this
        );
    }

    /**
     * 方式 ⑨ —— 视图组件重用：YZE_View_Component（docs/yangzie.md:747-760）。
     *
     * 组件是继承 YZE_View_Component 的 class：modules/test/views/user_card.view.php
     * （类 User_Card_View，namespace app\test）。按命名约定被框架 autoload
     * （startup.php:42-43 主路径 modules/{模块}/views/），使用处不 include。
     * 公开的 build/set_user/set_level 方法就是数据契约，IDE 有代码提示；
     * set_level 传错 key 立刻抛异常 —— 方式一数组 key 拼错只会静默不显示。
     */
    public function component_class() {
        $this->set_view_data('yze_page_title', '视图组件 · YZE_View_Component');

        // 链式调用，每个方法 IDE 都有提示
        $cards = [];
        foreach ([
            ['alice', '爱丽丝', 'alice@example.com', 'admin'],
            ['bob',   '小博',   'bob@example.com',   'editor'],
        ] as [$username, $name, $email, $level]) {
            $cards[] = User_Card_View::build($this)
                ->set_user($username, $name, $email)
                ->set_level($level)
                ->get_output();
        }
        $this->set_view_data('cards', $cards);

        // 同一个组件固定 json 格式渲染：格式在 output_component() 里自行判断（方式二的代价）
        $this->set_view_data('card_json', User_Card_View::build($this, 'json')
            ->set_user('charlie', '查理', 'charlie@example.com')
            ->set_level('viewer')
            ->get_output());

        // 演示"传错立刻报错"：拼错级别 key 被组件当场拦下
        try {
            User_Card_View::build($this)->set_user('oops', '', '')->set_level('admim'); // 故意拼错
        } catch (\InvalidArgumentException $e) {
            $this->set_view_data('typo_msg', $e->getMessage());
        }
    }

    /**
     * 方式 ⑩ —— 组件对象直接当整页响应，且自带格式自适应。
     *
     * 组件本身就是 YZE_View_Adapter，可以像方式⑧一样被 action return；
     * URI 尾部 .json 后缀（request.php:731 解析）时构造函数 $format 取请求格式，
     * output_component() 里输出 JSON —— 同一个组件两种格式，组件自己判断处理。
     */
    public function component_class_output() {
        $this->layout = '';
        return User_Card_View::build($this)
            ->set_user('dave', '大卫', 'dave@example.com')
            ->set_level('viewer');
    }

    /**
     * 方式 ⑪ —— Master View 三级嵌套（docs/yangzie.md:850-863）。
     *
     * 整条链靠字符串形式的 master_view 声明（view.php:458-494 解析）：
     *   content.tpl.php        顶部  $this->master_view = "master/project-page";
     *   project-page.tpl.php   顶部  $this->master_view = "master/site-shell";
     *   site-shell.tpl.php                  （最外层，不再声明）
     *
     * 框架输出顺序（view.php:300-306）：
     *   1. display_self() 渲染 content → data
     *   2. check_master() 找到 master/project-page → output_master(data)
     *   3. 新 view 的 display_self 渲染 project-page（其中 $this->content_of_view() = content 输出）
     *   4. 它再 declare master_view = "master/site-shell" → 再 output_master 一层
     *   5. 渲染 site-shell（其中 $this->content_of_view() = project-page 输出）
     *
     * 整个 3 层的 wiring 只在三个 tpl 的顶部 $this->master_view = ... 声明里，
     * action 只负责构造最内层 + 关掉全局 layout。
     */
    public function master_three_level() {
        $this->layout = '';
        $inner = new YZE_Simple_View(
            $this->request->view_path() . "/master/content",
            ['username' => '爱丽丝', 'role' => '管理员', 'email' => 'alice@example.com'],
            $this
        );
        // tpl 顶部已自己声明 master_view = "master/project-page"，此处不重复设
        return $inner;
    }

    /**
     * 方式 ⑫ —— 共享 master view：另一个 action 共用 project-page 这个"公共部分A"
     * （docs/yangzie.md:854："Action1 和 Action2 两个 URI 请求的响应中都有相同的界面部分公共部分A"）。
     *
     * 与 ⑪ 的 wiring 完全相同，只是 inner 的数据不同 —— 证明 project-page 这个壳
     * 真正被多个 action 共用，差异只在 content 部分。
     */
    public function master_action2() {
        $this->layout = '';
        $inner = new YZE_Simple_View(
            $this->request->view_path() . "/master/content",
            ['username' => '小博', 'role' => '编辑', 'email' => 'bob@example.com'],
            $this
        );
        return $inner;
    }

    /**
     * 方式 ⑬ —— Master View 三级嵌套 · 解释页（docs/yangzie.md:850-863）。
     *
     * 返回 null（走默认渲染 → view-master_nesting.tpl.php），页内通过
     * get_output() 把 ⑪ 同样的三级链渲染好嵌在 A 区，并附完整 wiring 分解。
     * 本 action 不设 layout=''：保留全局 tpl 布局，让解释页看起来像普通页。
     */
    public function master_nesting() {
        $this->set_view_data('yze_page_title', 'Master View 三级嵌套');

        // 把⑪同样的三级链 get_output() 出来，嵌进解释页 A 区
        $inner = new YZE_Simple_View(
            $this->request->view_path() . "/master/content",
            ['username' => '爱丽丝', 'role' => '管理员', 'email' => 'alice@example.com'],
            $this
        );
        $this->set_view_data('rendered_html', $inner->get_output());
    }

    /**
     * 方式 ⑭ —— Section 演示 · 解释页（docs/yangzie.md:865-877）。
     *
     * 默认渲染 → view-section_demo.tpl.php。A 区把 ⑮⑯ 同样的链 get_output 嵌进来，
     * B 区放 section API + 三层 wiring 分解，C 区对比 master view 和 layout 两种容器。
     */
    public function section_demo() {
        $this->set_view_data('yze_page_title', 'Section 演示');

        // ⑯ 同款：默认视图（view-section_layout.tpl.php）+ 自定义 layout 'section-demo'
        // —— 但在解释页里直接构造一条同款链并 get_output：
        //   content view (带 3 个 section) → YZE_Layout('section-demo')
        $content_for_layout_demo = new YZE_Simple_View(
            $this->request->view_path() . "/view-section_layout",
            ['title' => 'iPhone Air 保护壳', 'price' => 199.0, 'stock' => 42, 'desc' => '超薄透明壳 · 军用级抗摔 · 透明带磁吸。'],
            $this
        );
        $layout = new \yangzie\YZE_Layout('section-demo', $content_for_layout_demo, $this);
        $this->set_view_data('layout_demo_html', $layout->get_output());

        // ⑮ 同款：内层 view（带 3 个 section） + master view 'master/section-shell'
        $inner = new YZE_Simple_View(
            $this->request->view_path() . "/master/section-product",
            ['title' => 'iPhone Air 保护壳', 'price' => 199.0, 'stock' => 42, 'desc' => '超薄透明壳 · 军用级抗摔 · 透明带磁吸。'],
            $this
        );
        $inner->master_view = "master/section-shell";
        $this->set_view_data('master_demo_html', $inner->get_output());
    }

    /**
     * 方式 ⑮ —— Section · master view 输出（live demo，对应 yangzie.md:876）。
     *
     * 内层 view master/section-product 定义 3 个 section（breadcrumb / sidebar / og_meta）；
     * master view master/section-shell 在 <head> / <header> / <main> / <aside> 4 处分别
     * echo og_meta / breadcrumb / content_of_view / sidebar —— 这就是容器在指定位置
     * 输出 section 的 wiring。
     *
     * $this->layout = ''：本 master view 是完整 <html> 响应，跳过默认 tpl.layout.php 的
     * 再一层 <html> 包裹（避免双层 <html>）。
     */
    public function section_master() {
        $this->layout = '';
        $inner = new YZE_Simple_View(
            $this->request->view_path() . "/master/section-product",
            ['title' => 'iPhone Air 保护壳', 'price' => 199.0, 'stock' => 42, 'desc' => '超薄透明壳 · 军用级抗摔 · 透明带磁吸。'],
            $this
        );
        $inner->master_view = "master/section-shell";
        return $inner;
    }

    /**
     * 方式 ⑯ —— Section · 自定义 layout 输出（live demo，对应 yangzie.md:876）。
     *
     * 不使用 master view，只用自定义 layout 'section-demo'（app/vendor/layouts/）。
     * 默认渲染 → view-section_layout.tpl.php → 该 tpl 定义 3 个 section →
     * section-demo.layout.php 在 3 处 echo（对比默认 tpl.layout.php 只 echo 'section1'）。
     */
    public function section_layout() {
        $this->layout = 'section-demo';
        $this->set_view_data('yze_page_title', '产品详情');
        $this->set_view_data('title',  'iPhone Air 保护壳');
        $this->set_view_data('price',  199.0);
        $this->set_view_data('stock',  42);
        $this->set_view_data('desc',   '超薄透明壳 · 军用级抗摔 · 透明带磁吸。');
    }

    /**
     * 方式 ⑰ —— 响应格式演示（docs/yangzie.md:878-885）。
     *
     * 同一个 Action，三种响应格式（tpl / json / jpg）共享同一份 set_view_data('product')。
     * URI 末尾的 .json / .jpg 后缀由 request.php:731 解析成 __yze_resp_format__，
     * controller.php:109-110 自动把 layout 设成对应名，view.php:496-507 在模块 views 下
     * 找带对应后缀的视图文件，最后由 app/vendor/layouts/{format}.layout.php 包起来。
     *
     * 本 action 不 return，默认渲染 → view-formats_demo.{format}.php。
     *   /test/view/formats_demo         → view-formats_demo.tpl.php  （PC 端默认格式）
     *   /test/view/formats_demo.json    → view-formats_demo.json.php
     *   /test/view/formats_demo.jpg     → view-formats_demo.jpg.php
     */
    public function formats_demo() {
        $this->set_view_data('yze_page_title', '响应格式演示 · tpl/json/jpg');
        $this->set_view_data('product', [
            'id'       => 'P-1001',
            'name'     => 'iPhone Air 保护壳',
            'price'    => 199.0,
            'category' => 'accessory',
            'stock'    => 42,
        ]);
    }
}