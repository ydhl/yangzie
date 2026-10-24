<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * /test/acl/dynamic 视图 —— 动态权限演示首页。
 *
 * 三个演示模式（Cookie demo_perm_mode，由 set_perm_mode 写入）：
 *   none  模块 hooks/acl_dynamic.php 不定义任何函数 → 纯静态配置
 *   user  定义 get_user_permissions() → 用户级 deny/allow 优先于角色和静态表
 *   role  定义 get_permissions($aro)  → 角色级配置整体接管静态表的角色判定
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据（来自 Acl_Controller::dynamic）：
 *   - perm_mode   string  当前模式（'' / 'user' / 'role'）
 *   - aro         string  当前演示 ARO
 */
?>
<?php
/** @var string $perm_mode */
/** @var string $aro */
$perm_mode = (string)($this->get_Data('perm_mode') ?: '');
$aro       = (string)($this->get_Data('aro') ?: '/');

$mode_names = [
    ''     => 'none（纯静态配置）',
    'user' => 'user（get_user_permissions）',
    'role' => 'role（get_permissions）',
];

// 三个对照组 action：每种模式下预期的访问结果
$cases = [
    [
        'url'   => '/test/acl/user_grant',
        'name'  => 'user_grant',
        'fn'    => 'get_user_permissions().allow',
        'base'  => '拒（静态只 allow /admin，/consumer 进不去）',
        'user'  => '过（用户级 allow 不看 ARO，/consumer 也放行）',
        'role'  => '过（用户函数未定义，但不是落回静态：get_permissions 接管，它的 allow /test/acl 补 /* 后覆盖本页）',
    ],
    [
        'url'   => '/test/acl/user_block',
        'name'  => 'user_block',
        'fn'    => 'get_user_permissions().deny',
        'base'  => '过（静态 allow "*"，人人能进）',
        'user'  => '拒（用户级 deny 优先于一切 allow）',
        'role'  => '过（角色 allow /test/acl 覆盖）',
    ],
    [
        'url'   => '/test/acl/role_grant',
        'name'  => 'role_grant',
        'fn'    => 'get_permissions("/consumer").allow',
        'base'  => '拒（静态只 allow /admin）',
        'user'  => '拒（用户函数返回 -1，落回静态仍拒）',
        'role'  => '过（角色级动态 allow，/consumer 放行）',
    ],
];
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .d { background:#fff; width:820px; padding:32px; border-radius:8px; margin:auto 0;
         box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .d h1 { margin:0 0 4px; font-size:22px; }
    .d .sub { color:#888; font-size:13px; margin-bottom:16px; }
    .d .status { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                 padding:12px 16px; font-size:13px; margin-bottom:18px; display:flex;
                 gap:16px; align-items:center; flex-wrap:wrap; }
    .d .status .mode { padding:3px 10px; border-radius:4px; font-size:12px;
                       font-family:Menlo, Consolas, monospace; background:#ecf5ff; color:#409eff; }
    .d .status .aro  { padding:3px 10px; border-radius:4px; font-size:12px;
                       font-family:Menlo, Consolas, monospace; background:#f0f9eb; color:#67c23a; }
    .d .mode-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
    .d .mode-btn { padding:8px 16px; border-radius:4px; font-size:13px; cursor:pointer;
                   text-decoration:none; border:1px solid #dcdfe6; background:#fafbfc;
                   color:#606266; transition:all .15s; }
    .d .mode-btn:hover { border-color:#409eff; color:#409eff; }
    .d .mode-btn.active { background:#409eff; color:#fff; border-color:#409eff; }
    .d table { border-collapse:collapse; width:100%; font-size:12px; }
    .d th, .d td { border:1px solid #ebeef5; padding:8px 10px; text-align:left;
                   vertical-align:top; line-height:1.6; }
    .d th { background:#fafbfc; color:#606266; font-weight:600; white-space:nowrap; }
    .d td.name { font-family:Menlo, Consolas, monospace; white-space:nowrap; }
    .d td.pass { color:#67c23a; }
    .d td.fail { color:#f56c6c; }
    .d td .fn { color:#888; font-size:11px; display:block; }
    .d .enter { color:#409eff; text-decoration:none; white-space:nowrap; }
    .d .expl { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
               padding:12px 14px; font-size:12px; color:#606266;
               font-family:Menlo, Consolas, monospace; line-height:1.7;
               margin-top:18px; white-space:pre-wrap; }
    .d .expl .c { color:#888; }
    .d .expl .k { color:#c45656; }
    .d .expl .v { color:#228b22; }
    .d .expl h4 { margin:0 0 8px; font-family:inherit; font-size:13px; color:#444; }
    .d .note { background:#fdf6ec; border:1px solid #faecd8; color:#e6a23c;
               border-radius:6px; padding:10px 14px; font-size:12px; margin-bottom:18px;
               line-height:1.7; }
</style>

<div class="d">
    <h1>动态权限演示</h1>
    <div class="sub">定义 get_user_permissions() / get_permissions($aro_name) 后，优先于静态配置生效</div>

    <div class="status">
        当前模式：<span class="mode"><?= htmlspecialchars($mode_names[$perm_mode] ?? $perm_mode, ENT_QUOTES, 'UTF-8') ?></span>
        当前 ARO：<span class="aro"><?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?></span>
        <span style="color:#888">建议先在 ACL 首页把 ARO 切到 /consumer，再切换下面的模式对比结果</span>
    </div>

    <div class="mode-row">
        <a class="mode-btn <?= $perm_mode === '' ? 'active' : '' ?>" href="/test/acl/set_perm_mode?mode=none">none（纯静态）</a>
        <a class="mode-btn <?= $perm_mode === 'user' ? 'active' : '' ?>" href="/test/acl/set_perm_mode?mode=user">user（get_user_permissions）</a>
        <a class="mode-btn <?= $perm_mode === 'role' ? 'active' : '' ?>" href="/test/acl/set_perm_mode?mode=role">role（get_permissions）</a>
    </div>

    <div class="note">
        表格里的"过/拒"都以 <b>ARO = /consumer</b> 为准（这是静态配置下被拒的角色，对比最明显）。
        user_block 那行在 user 模式下对<b>任何</b> ARO 都是拒绝 —— 用户级 deny 不看角色。
        role 模式下 get_permissions 给每个演示角色都配了 allow /test/acl（否则演示页之间无法互相跳转），
        补 /* 后覆盖 /test/acl/*，所以那一列三行都是"过"。
    </div>

    <table>
        <tr>
            <th>action</th>
            <th>none（静态基线）</th>
            <th>user 模式</th>
            <th>role 模式</th>
            <th></th>
        </tr>
        <?php foreach ($cases as $c): ?>
            <tr>
                <td class="name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="fn"><?= htmlspecialchars($c['fn'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td class="<?= strpos($c['base'], '过') === 0 ? 'pass' : 'fail' ?>"><?= htmlspecialchars($c['base'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="<?= strpos($c['user'], '过') === 0 ? 'pass' : 'fail' ?>"><?= htmlspecialchars($c['user'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="<?= strpos($c['role'], '过') === 0 ? 'pass' : 'fail' ?>"><?= htmlspecialchars($c['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="enter" href="<?= htmlspecialchars($c['url'], ENT_QUOTES, 'UTF-8') ?>">进入</a></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="expl">
<h4>三级优先级（yangzie/acl.php）</h4>
<span class="c">// check_byname() acl.php:266-285</span>
<span class="k">if</span> (function_exists(<span class="v">"get_user_permissions"</span>)) {      <span class="c">// ① 用户级，最高</span>
    <span class="v">$rst</span> = check_user_permission(<span class="v">$aconame</span>);      <span class="c">// 命中 deny/allow 即返回；未命中返回 -1 落回 ②</span>
}
<span class="k">return</span> check_role_permission(<span class="v">$aroname</span>, <span class="v">$aconame</span>); <span class="c">// ② 角色级（若定义了 get_permissions）否则 ③ 静态表</span>

<span class="c">// ① get_user_permissions() —— deny/allow 是 ACO 列表，挂在"当前用户"身上，不看 ARO；</span>
<span class="c">//    deny 优先（acl.php:64-69），用户级 deny 连静态 allow "*" 都拦得住。</span>
<span class="c">// ② get_permissions($aro_name) —— deny/allow 同样是 ACO 列表；⚠ 一旦定义，静态表</span>
<span class="c">//    的角色判定分支整体不执行（acl.php:95），漏配的角色会被全拒。</span>
<span class="c">//    且动态分支只对 ACO 向上递归（acl.php:117-122），不做 ARO 向上递归 ——</span>
<span class="c">//    这和静态分支（acl.php:148-153 array_pop ARO）是两个不同的递归方向。</span>
<span class="c">// ③ yze_get_acos_aros() 静态配置 —— need_controll() 定位受控 ACO 始终用它。</span>

<h4>动态函数定义在哪里：test 模块 hooks/acl_dynamic.php</h4>
<span class="c">// 该文件被框架在模块加载阶段自动 require（router.php:104），顶层按 Cookie</span>
<span class="c">// demo_perm_mode 条件定义上面两个函数 —— 必须在这个时机，因为 ACL 检查发生在</span>
<span class="c">// startup.php:131 的 auth()，比任何 hook 都早，函数得在请求进来时就存在。</span>
    </div>

    <div style="margin-top:18px">
        <a class="mode-btn" href="/test/acl">回到 ACL 演示首页</a>
        <a class="mode-btn" href="/test">回 test 模块首页</a>
    </div>
</div>
