--TEST--
phar 打包模块：scripts/yze.php --phar 产出的 phar 能被框架按 phar 模块加载（is_phar 标记/路由注册/类自动加载/phar:// 视图与静态资源）
--SKIPIF--
<?php
if (!extension_loaded("phar")) die("skip phar extension not available");
if (!extension_loaded("zlib")) die("skip zlib extension not available");
if (!function_exists("exec")) die("skip exec() not available");
?>
--FILE--
<?php
ini_set("display_errors",0);

$root     = dirname(dirname(dirname(__FILE__)));
$module   = "phartest";
$srcDir   = $root."/app/modules/".$module;
$pharFile = $root."/app/modules/".$module.".phar";
$tmpPhar  = $root."/tmp/".$module.".phar";
$checks   = array();

// ai@2026-09-13 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if (is_bool($v)) return $v ? 'true' : 'false';
    if (is_null($v)) return 'null';
    return var_export($v, true);
}

// ai@2026-09-13 递归删除目录，用于清理测试用的模块源码目录
function t_rmdir($dir){
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f){
        if ($f=='.'||$f=='..') continue;
        $p = $dir.DIRECTORY_SEPARATOR.$f;
        is_dir($p) ? t_rmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

function t_unlink_phar($pharFile){
    if (!file_exists($pharFile)) return;
    try { @Phar::unlinkArchive($pharFile); } catch (\Throwable $e) { @unlink($pharFile); }
}

// ai@2026-09-13 兜底清理：测试中断（超时/致命错误）时不把 phar 与临时模块留在项目里
register_shutdown_function(function() use ($pharFile, $tmpPhar, $srcDir){
    t_unlink_phar($pharFile);
    t_unlink_phar($tmpPhar);
    t_rmdir($srcDir);
});

// ai@2026-09-13 1. 清理可能的残留，保证可重复运行
t_unlink_phar($pharFile);
t_unlink_phar($tmpPhar);
t_rmdir($srcDir);

// ai@2026-09-13 2. 准备模块源码：目录结构与 yze.php --mvc 生成的一致，另加 hooks 与 public_html 静态资源
mkdir($srcDir."/controllers", 0777, true);
mkdir($srcDir."/views", 0777, true);
mkdir($srcDir."/hooks", 0777, true);
mkdir($srcDir."/public_html/js", 0777, true);
file_put_contents($srcDir."/__config__.php", <<<'CONF'
<?php
namespace app\phartest;
use \yangzie\YZE_Base_Module as YZE_Base_Module;
class Phartest_Module extends YZE_Base_Module{
	public array $auths = array();
	public array $no_auths = array();
	protected function config(): array{
		return [
			"name" => "Phartest",
			"routers" => [
				"phartest/index" => ["controller"=>"index", "action"=>"index"],
			],
		];
	}
	public function js_bundle(string $bundle): array{ return ["/js/app.js"]; }
	public function css_bundle(string $bundle): array{ return []; }
}
CONF
);
file_put_contents($srcDir."/controllers/index.controller.php", '<?php
namespace app\phartest;
class Index_Controller{
	public function index(){ return "phar index"; }
}');
file_put_contents($srcDir."/views/index-index.tpl.php", 'PHAR_VIEW_CONTENT');
file_put_contents($srcDir."/public_html/js/app.js", 'PHAR_ASSET');
file_put_contents($srcDir."/hooks/phar_hook.php", '<?php define("PHAR_HOOK_LOADED", true);');

// ai@2026-09-13 3. 用真实的打包命令生成 phar（不复制打包实现）；phar.readonly=On 时打包会被禁用，故子进程加 -d 关闭
chdir($root);
exec(escapeshellarg(PHP_BINARY)." -d phar.readonly=0 scripts/yze.php --phar --module={$module} 2>&1", $lines, $ret);
$cliOutput = preg_replace('/\033\[[0-9;]*[A-Za-z]/', '', str_replace("\r", "", implode("\n", $lines)));

$checks["cli_saved"]   = strpos($cliOutput, "saved at modules/") !== false;
$checks["phar_created"] = file_exists($pharFile);
$checks["phar_config"]  = file_exists("phar://".$pharFile."/__config__.php");

$entries = scandir("phar://".$pharFile);
sort($entries);
$checks["phar_entries"] = $entries;
// ai@2026-09-13 hooks 目录已打包进 phar（注意：YZE_Hook::include_hooks 用 glob() 遍历，
// 而 glob() 不支持 phar:// 流，因此 phar 模块的 hooks 目前不会被 require，属框架既有行为）
$checks["phar_hooks"] = file_exists("phar://".$pharFile."/hooks/phar_hook.php");

// ai@2026-09-13 4. 删除模块源码目录：框架只能从 phar 加载，验证打包产物自洽
t_rmdir($srcDir);
$checks["src_removed"] = !file_exists($srcDir) && file_exists($pharFile);

// ai@2026-09-13 5. 启动应用：yze_load_app() -> YZE_Router::load_routers() 扫描 modules 目录，
// 对文件使用 phar:// 前缀加载，并把模块登记为 is_phar（先不外显中间结果，避免 session_start 报 headers already sent）
ob_start();
chdir($root."/app/public_html");
include "init.php";
$appOutput = ob_get_clean();

$checks["app_output"] = $appOutput;

$info = \yangzie\YZE_Object::loaded_module($module);
$checks["loaded_module"] = $info;
$checks["is_phar"]       = is_array($info) && ($info['is_phar'] ?? null) === true;

// ai@2026-09-13 6. 路由注册：load_routers() 中 phar 模块的模块名经 ucfirst 处理（router.php:91），故键名为 Phartest
$routers = \yangzie\YZE_Router::get_Instance()->get_Routers();
$checks["router_keys"]   = array_keys($routers);
$checks["router_module"] = $routers[ucfirst($module)] ?? null;

// ai@2026-09-13 7. 类自动加载：yze_autoload 按 is_phar 拼出 phar:// 路径加载控制器
$checks["autoload_class"]  = class_exists("\\app\\".$module."\\Index_Controller");
$checks["autoload_method"] = (new \app\phartest\Index_Controller())->index();

// ai@2026-09-13 8. 视图目录：复刻 YZE_Request::view_path() 的 phar 分支拼接方式，输出时把项目根替换为 {ROOT}
$viewPath = "phar://".\YZE_APP_PATH."modules/".$module.".phar/views";
$checks["view_path"]    = str_replace($root, "{ROOT}", $viewPath);
$checks["view_file"]    = file_exists($viewPath."/index-index.tpl.php");
$checks["view_content"] = file_get_contents($viewPath."/index-index.tpl.php");

// ai@2026-09-13 9. 静态资源：复刻 public_html/load.php 中 t=asset 分支的 phar 路径拼接（phar:// 前缀 + 模块 public_html）
$assetPath = "phar://".\YZE_APP_MODULES_INC.$module.".phar/public_html/js/app.js";
$checks["asset_path"]    = str_replace($root, "{ROOT}", $assetPath);
$checks["asset_content"] = file_get_contents($assetPath);

// ai@2026-09-13 10. 清理并确认残留已删除
$checks["phar_unlinked"] = @Phar::unlinkArchive($pharFile);
$checks["phar_removed"]  = !file_exists($pharFile);
$checks["src_absent"]    = !file_exists($srcDir);

foreach ($checks as $key => $value){
    echo $key,":",s($value),"\n";
}
?>
--EXPECT--
cli_saved:true
phar_created:true
phar_config:true
phar_entries:["__config__.php","controllers","hooks","public_html","views"]
phar_hooks:true
src_removed:true
app_output:''
loaded_module:{"is_phar":true}
is_phar:true
router_keys:["Phartest"]
router_module:{"phartest/index":{"controller":"index","action":"index"}}
autoload_class:true
autoload_method:'phar index'
view_path:'phar://{ROOT}/app/modules/phartest.phar/views'
view_file:true
view_content:'PHAR_VIEW_CONTENT'
asset_path:'phar://{ROOT}/app/modules/phartest.phar/public_html/js/app.js'
asset_content:'PHAR_ASSET'
phar_unlinked:true
phar_removed:true
src_absent:true
