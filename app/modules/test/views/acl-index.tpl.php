<?php
namespace app\test;
use \yangzie\YZE_View_Adapter as YZE_View_Adapter;

/**
 * ACL 演示首页视图。
 *
 * 布局：
 *   1. 顶部：当前登录用户 + 当前演示 ARO
 *   2. 角色切换器（4 个按钮）
 *   3. 5 个 action 入口（admin_tools / reports / audit / settings / public_info）
 *   4. "匹配规则说明"代码截图
 *
 * @var YZE_View_Adapter $this 视图
 *
 * 视图数据（来自 Acl_Controller::index）：
 *   - user                       array   登录用户信息
 *   - demo_aros                  string[] 演示可用的 ARO 白名单
 *   - current_aro                string  当前 session 里的 ARO
 */
?>
<?php
/** @var ?array $user */
$user        = $this->get_Data('user');
$demo_aros   = (array)($this->get_Data('demo_aros') ?: []);
$current_aro = (string)($this->get_Data('current_aro') ?: '/');

// 把 4 个 action 的入口集中成一个数组，迭代渲染，避免在视图里写 4 段重复 HTML
$demos = [
    [
        'name'  => 'admin_tools（精确 ACO）',
        'desc'  => 'ACO 字面等于 /test/acl/admin_tools，命中 __aros_acos__.php 里同 key 的那条规则。',
        'url'   => '/test/acl/admin_tools',
        'tag'   => '精确',
    ],
    [
        'name'  => 'reports（ACO 前缀匹配）',
        'desc'  => '__aros_acos__.php 里**没有** /test/acl/reports，但配了 /test/acl。'
                 . 'in_array() 会自动补 "/*"，相当于 /test/acl/*，所以这里命中前缀规则。',
        'url'   => '/test/acl/reports',
        'tag'   => '前缀',
    ],
    [
        'name'  => 'audit（ARO 向上匹配）',
        'desc'  => 'allow 只配了父级 /admin，没配 /admin/exact。当前 ARO 是 /admin 的'
                 . '子级时，靠 in_array() 补 "/\*" 前缀或 array_pop 逐级向上递归命中。',
        'url'   => '/test/acl/audit',
        'tag'   => 'aro-up',
    ],
    [
        'name'  => 'settings（精确 ACO + deny 优先）',
        'desc'  => '精确命中 /test/acl/settings。同 allow 配 /admin 的情况下，'
                 . '/admin/banned 仍会被 deny 拒掉。',
        'url'   => '/test/acl/settings',
        'tag'   => 'deny 优先',
    ],
    [
        'name'  => 'dynamic（动态权限函数）',
        'desc'  => '定义 get_user_permissions() / get_permissions($aro) 后优先于静态配置，'
                 . '三级优先级：用户级 → 角色级 → 静态表。演示页上可切换模式对比。',
        'url'   => '/test/acl/dynamic',
        'tag'   => 'dynamic',
    ],
    [
        'name'  => 'public_info（ignore 列表）',
        'desc'  => '命中 yze_get_ignore_acos()，need_controll() 直接返回 null，'
                 . 'check_byname() return true，任何 ARO 都能进。',
        'url'   => '/test/acl/public_info',
        'tag'   => 'ignore',
    ],
];
?>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
           background:#f5f6f8; margin:0; min-height:100vh;
           display:flex; align-items:flex-start; justify-content:center; padding:32px 0; }
    .acl-card { background:#fff; width:760px; padding:32px; border-radius:8px; margin:auto 0;
                box-shadow:0 4px 16px rgba(0,0,0,.06); }
    .acl-card h1 { margin:0 0 4px; font-size:22px; }
    .acl-card .sub { color:#888; font-size:13px; margin-bottom:20px; }
    .acl-card .who { color:#666; font-size:13px; margin-bottom:16px; }
    .acl-card h2 { font-size:14px; margin:24px 0 10px; color:#444; }
    .acl-card .role-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
    .acl-card .role-btn { padding:8px 14px; border-radius:4px; font-size:13px; cursor:pointer;
                          text-decoration:none; border:1px solid #dcdfe6; background:#fafbfc;
                          color:#606266; transition:all .15s; }
    .acl-card .role-btn:hover { border-color:#409eff; color:#409eff; }
    .acl-card .role-btn.active { background:#409eff; color:#fff; border-color:#409eff; }
    .acl-card .aro-tag { display:inline-block; padding:4px 12px; border-radius:4px;
                         background:#f0f9eb; color:#67c23a; font-size:13px;
                         font-family:Menlo, Consolas, monospace; }
    .acl-card .demo-list .item { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                                  padding:14px 18px; margin-bottom:10px; display:flex;
                                  align-items:center; gap:12px; }
    .acl-card .demo-list .item .info { flex:1; }
    .acl-card .demo-list .item h3 { margin:0 0 4px; font-size:14px; display:flex;
                                     align-items:center; gap:8px; }
    .acl-card .demo-list .item p { margin:0; color:#888; font-size:12px; line-height:1.6; }
    .acl-card .demo-list .item .tag { font-size:11px; padding:2px 8px; border-radius:10px; }
    .acl-card .demo-list .item .tag.exact  { background:#ecf5ff; color:#409eff; border:1px solid #d9ecff; }
    .acl-card .demo-list .item .tag.prefix { background:#fdf6ec; color:#e6a23c; border:1px solid #faecd8; }
    .acl-card .demo-list .item .tag.aro-up { background:#f3e8ff; color:#9333ea; border:1px solid #e9d5ff; }
    .acl-card .demo-list .item .tag.deny   { background:#fef0f0; color:#f56c6c; border:1px solid #fde2e2; }
    .acl-card .demo-list .item .tag.ignore { background:#f0f9eb; color:#67c23a; border:1px solid #e1f3d8; }
    .acl-card .demo-list .item .tag.dynamic { background:#e8f4f8; color:#0e7490; border:1px solid #cffafe; }
    .acl-card .demo-list .item .enter { padding:6px 14px; border-radius:4px; background:#409eff;
                                           color:#fff; text-decoration:none; font-size:13px; white-space:nowrap; }
    .acl-card .demo-list .item .enter:hover { background:#66b1ff; }
    .acl-card .explain { background:#fafbfc; border:1px solid #ebeef5; border-radius:6px;
                         padding:12px 14px; font-size:12px; color:#606266;
                         font-family:Menlo, Consolas, monospace; line-height:1.7;
                         margin-top:18px; white-space:pre-wrap; }
    .acl-card .explain .c { color:#888; }
    .acl-card .explain .k { color:#c45656; }
    .acl-card .explain .v { color:#228b22; }
    .acl-card .explain h4 { margin:0 0 8px; font-family:inherit; font-size:13px; color:#444; }
</style>

<div class="acl-card">
    <h1>ACL 控制演示</h1>
    <div class="sub">ACO 与 ARO 的精确匹配 / 前缀匹配 / 向上匹配 / deny 优先 / ignore 列表 / 动态权限</div>

    <div class="who">
        当前登录：
        <?php if ($user): ?>
            <b><?= htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></b>
            （<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>）
        <?php else: ?>
            未登录（理论不会到这里，acl 控制器被 $auths 保护）
        <?php endif; ?>
        　·　当前 ARO：<span class="aro-tag"><?= htmlspecialchars($current_aro, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <h2>1. 切换演示 ARO（写入 session，hook 读它当作角色）</h2>
    <div class="role-row">
        <?php foreach ($demo_aros as $aro): ?>
            <a class="role-btn <?= $aro === $current_aro ? 'active' : '' ?>"
               href="/test/acl/switch_role?aro=<?= urlencode($aro) ?>">
                <?= htmlspecialchars($aro, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
        <a class="role-btn <?= '/' === $current_aro ? 'active' : '' ?>"
           href="/test/acl/switch_role?aro=">
            /（默认）
        </a>
    </div>

    <h2>2. 选择要演示的 action</h2>
    <div class="demo-list">
        <?php foreach ($demos as $d): ?>
            <div class="item">
                <div class="info">
                    <h3>
                        <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="tag <?= htmlspecialchars($d['tag'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($d['tag'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </h3>
                    <p><?= htmlspecialchars($d['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a class="enter" href="<?= htmlspecialchars($d['url'], ENT_QUOTES, 'UTF-8') ?>">进入</a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="explain">
<h4>关键匹配规则（yangzie/acl.php）</h4>
<span class="c">// need_controll() —— 找静态表里最长的 ACO 前缀（krsort 保证长 key 先匹配）</span>
<span class="k">foreach</span> (<span class="v">acos_aros</span> <span class="k">as</span> <span class="v">$aco</span> => ...) {
    <span class="k">if</span> (preg_match(<span class="v">"{^".$aco."}i"</span>, <span class="v">$aconame</span>)) <span class="k">return</span> <span class="v">$aco</span>;
}
<span class="c">// 命中 ignore 或 acos_aros 为空 → need_controll 返回 null → 默认放行</span>

<span class="c">// in_array() —— 没写 * 结尾自动补 "/*"，所以配 /test/acl 等同 /test/acl/*</span>
<span class="k">if</span> (substr(<span class="v">$k</span>, -1) != <span class="v">"*"</span>) <span class="v">$k</span> .= <span class="v">"/*"</span>;
<span class="v">$k</span> = strtr(<span class="v">$k</span>, [<span class="v">"*"</span> => <span class="v">".*"</span>]);
<span class="k">if</span> (preg_match(<span class="v">"{^".$k."$}i"</span>, <span class="v">$check</span>)) <span class="k">return true</span>;

<span class="c">// check_role_permission() —— ARO 和 ACO 都会逐级向上递归</span>
<span class="v">$aroname</span> = count(<span class="v">$parts</span>) == 1 ? <span class="v">"/"</span> : join(<span class="v">"/"</span>, <span class="v">$parts</span>);
<span class="k">return</span> <span class="v">$this</span>->check_role_permission(<span class="v">$aroname</span>, <span class="v">$aconame</span>);
    </div>
</div>