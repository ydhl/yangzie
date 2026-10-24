<?php
namespace app\test;

use \yangzie\YZE_View_Component;

/**
 * 模块级视图组件（方式二 · YZE_View_Component）—— 用户信息卡片，OOP 版。
 *
 * 和方式一的 user-card.tpl.php 渲染同样的界面，但调用方通过本类的公开方法
 * 就能明确知道"组件解决什么问题、要传什么数据"（IDE 有代码提示），
 * 还能对入参做校验、提供更多处理方式 —— 这是 OOP 重用的收益（yangzie.md:749）。
 *
 * 命名 / autoload 约定（yangzie/startup.php:42-43，无需手工 include）：
 *   类名  ：User_Card_View（驼峰、下划线分词、_View 后缀）
 *   文件名：user_card.view.php（组件名全小写）
 *   放在模块 views 中时 namespace 用模块命名空间（本组件在 test 模块 → app\test），
 *   autoload 主路径直接命中 modules/test/views/user_card.view.php；
 *   也可以放 app/vendor/views 等任何地方，但 namespace 要与路径对应
 *   （如 app\vendor\views），此时走 startup.php:45 的路径回退查找。
 *
 * 注意方式二的代价（yangzie.md:760）：视图格式的支持需要组件在
 * output_component() 中自行判断处理 —— 本组件支持 tpl（html）和 json 两种。
 */
class User_Card_View extends YZE_View_Component {

    /**
     * 级别定义：key → [标签文案, 配色]。
     * set_level() 只接受这里的 key —— 数据契约用常量固化，调用方看得见。
     */
    public const LEVELS = [
        'admin'  => ['管理员', 'danger'],
        'editor' => ['编辑',   'warn'],
        'viewer' => ['观察者', 'info'],
        'guest'  => ['访客',   'info'],
    ];

    /**
     * build() 工厂方法继承自 YZE_View_Component（view.php 中）。
     *
     * 设置用户信息。
     *
     * 方式一里这些字段叫什么、是数组还是对象，只能打开模板看注释；
     * 现在方法签名就是契约，IDE 逐参数提示。
     */
    public function set_user(string $username, string $name = '', string $email = ''): self {
        $this->data['user'] = [
            'username' => $username,
            'name'     => $name,
            'email'    => $email,
        ];
        return $this;
    }

    /**
     * 设置级别。必须是 LEVELS 定义的 key；
     * 拼错立刻抛异常 —— 方式一里数组 key 拼错只会静默不显示，问题拖到页面上才发现。
     */
    public function set_level(string $level): self {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException(
                '未知的级别 "' . $level . '"，可用：' . implode(' / ', array_keys(self::LEVELS))
            );
        }
        $this->data['level'] = $level;
        return $this;
    }

    /**
     * 组件的具体输出：output() / get_output() 时经 display_self() 调到这里（view.php:539-541）。
     * 视图格式在这里自行判断处理（方式二的代价）。
     */
    protected function output_component() {
        $user  = $this->data['user'] ?? [];
        $level = $this->data['level'] ?? 'guest';
        [$label, $color] = self::LEVELS[$level];

        // json 格式：组件自己判断输出结构（调用方用 URI 尾部 .json 后缀指定请求格式，
        // 由 request.php:731 解析；?format= 查询参数不受支持）
        if (strcasecmp($this->format, 'json') === 0) {
            echo json_encode([
                'component' => __CLASS__,
                'user'      => $user,
                'level'     => $level,
                'label'     => $label,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        // tpl（html）格式：与方式一 user-card.tpl.php 相同的输出
        $username = (string)($user['username'] ?? '');
        $name     = (string)($user['name'] ?? '');
        $email    = (string)($user['email'] ?? '');
        $src      = $username !== '' ? $username : '?';
        $initial  = function_exists('mb_substr')
            ? strtoupper(mb_substr($src, 0, 1))
            : strtoupper(substr($src, 0, 1));
        ?>
<style>
    .ucard { display:flex; align-items:center; gap:14px; background:#fafbfc;
             border:1px solid #ebeef5; border-radius:8px; padding:14px 18px; margin:0 0 10px; }
    .ucard .avatar { width:44px; height:44px; border-radius:50%; color:#fff;
                     display:flex; align-items:center; justify-content:center; font-size:18px;
                     font-weight:600; flex:none; }
    .ucard .avatar.danger { background:#f56c6c; }
    .ucard .avatar.warn   { background:#e6a23c; }
    .ucard .avatar.info   { background:#409eff; }
    .ucard .info { flex:1; min-width:0; }
    .ucard .info .uname { font-size:14px; font-weight:600; color:#222; }
    .ucard .info .uname small { color:#888; font-weight:400; margin-left:6px; }
    .ucard .info .mail { font-size:12px; color:#888; font-family:Menlo, Consolas, monospace;
                         overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .ucard .tag { flex:none; font-size:11px; padding:3px 10px; border-radius:10px; }
    .ucard .tag.danger { background:#fef0f0; color:#f56c6c; border:1px solid #fde2e2; }
    .ucard .tag.warn   { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8; }
    .ucard .tag.info   { background:#ecf5ff; color:#409eff; border:1px solid #d9ecff; }
</style>
<div class="ucard">
    <div class="avatar <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="info">
        <div class="uname"><?= htmlspecialchars($name !== '' ? $name : $username, ENT_QUOTES, 'UTF-8') ?>
            <small>@<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <div class="mail"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <span class="tag <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
</div>
        <?php
    }
}
