--TEST--
yangzie/view.php Simple_View/master/section/View_Component/Notpl_View 与异常
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_Request;
use yangzie\YZE_Resource_Controller;
use yangzie\YZE_Notpl_View;
use yangzie\YZE_Simple_View;
use yangzie\YZE_View_Component;
use yangzie\YZE_Resource_Not_Found_Exception;

// ai@2026-08-29 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// ai@2026-08-29 初始化请求与测试控制器
$req = YZE_Request::get_instance();
$req->init('/test/index', null, null, 'GET');
class YZE_View_Test_Controller extends YZE_Resource_Controller {}
$c = new YZE_View_Test_Controller();

// ===== YZE_Notpl_View：无模板直接输出 =====
$v = new YZE_Notpl_View("hello", $c);
echo s($v->return_html()),"\n";        // 原始字符串
echo s($v->get_output()),"\n";         // 渲染输出
echo s($v->get_datas()),"\n";          // 默认 data 为空数组
echo s($v->get_data("x")),"\n";        // 不存在返回 null
$v->set_data("a", 1);
echo s($v->get_data("a")),"\n";        // 单条设置
echo s($v->set_datas(array("b"=>2)) === $v),"\n"; // 批量设置返回自身（链式）
echo s($v->get_datas()),"\n";          // 批量覆盖后 data
echo s($v->get_controller() === $c),"\n"; // 控制器引用一致
echo s($v->check_view()),"\n";         // 基类默认 true

// ===== section 收集（继承自 YZE_View_Adapter） =====
echo s($v->view_sections()),"\n";      // 未收集时 null
echo s($v->content_of_section("s1")),"\n"; // 未收集时 null
echo s($v->content_of_view()),"\n";    // 未设置时 null
$v->begin_section();
echo "sec-content";
$v->end_section("s1");
echo s($v->view_sections()),"\n";      // 收集结果 {"s1":"sec-content"}
echo s($v->content_of_section("s1")),"\n"; // sec-content
echo s($v->content_of_section("no_such")),"\n"; // null

// ===== YZE_View_Component：子类实现 output_component =====
class YZE_Test_Component extends YZE_View_Component {
    protected function output_component(){
        echo "comp:".$this->get_data("v");
    }
}
$comp = new YZE_Test_Component(array("v"=>5), $c);
echo s($comp->get_output()),"\n";      // comp:5
echo s($comp->format),"\n";            // 默认取请求输出格式 tpl

// ===== YZE_Simple_View：模板渲染 =====
// 临时模板目录：Simple_View 用相对路径查找模板，chdir 到临时目录
$tmp = sys_get_temp_dir()."/yze_view_test_".uniqid();
mkdir($tmp, 0777, true);
chdir($tmp);
file_put_contents("hello.tpl.php", "<?php echo \"hello \" . \$this->get_data(\"name\"); ?>");
$sv = new YZE_Simple_View("hello", array("name"=>"world"), $c);
echo s($sv->format),"\n";              // 默认格式 tpl
echo s($sv->get_output()),"\n";        // hello world
// 指定格式不存在时降级为 tpl
$sv2 = new YZE_Simple_View("hello", array("name"=>"yze"), $c, "json");
echo s($sv2->get_output()),"\n";       // hello yze（降级 tpl）
// check_view 校验
echo s($sv->check_view()),"\n";        // 模板存在返回 null

// master view：当前目录下 master.tpl.php 包裹子视图，section 传递给 master
file_put_contents("sub.tpl.php", "<?php echo \"sub-content\"; \$this->begin_section(); echo \"SECTION\"; \$this->end_section(\"sec\"); ?>");
file_put_contents("master.tpl.php", "<?php echo \"[\" . \$this->content_of_view() . \"|\" . \$this->content_of_section(\"sec\") . \"]\"; ?>");
$mv = new YZE_Simple_View("sub", array(), $c);
$mv->master_view = "master";
echo s($mv->get_output()),"\n";        // [sub-content|SECTION]

// 模板不存在抛异常
try {
    $bad = new YZE_Simple_View("no_such_tpl", array(), $c);
    $bad->get_output();
    echo "NO_EXCEPTION\n";
} catch (YZE_Resource_Not_Found_Exception $e) {
    echo "TPL_EX:",s($e->getMessage()),"\n";
}
// master 不存在抛异常
try {
    $mv2 = new YZE_Simple_View("sub", array(), $c);
    $mv2->master_view = "no_such_master";
    $mv2->get_output();
    echo "NO_EXCEPTION\n";
} catch (YZE_Resource_Not_Found_Exception $e) {
    echo "MST_EX:",s(substr($e->getMessage(), 0, 40)),"\n";
}

// 清理临时模板目录
array_map("unlink", glob($tmp."/*"));
rmdir($tmp);
?>
--EXPECT--
'hello'
'hello'
[]
NULL
1
true
{"b":2}
true
true
NULL
NULL
NULL
{"s1":"sec-content"}
'sec-content'
NULL
'comp:5'
'tpl'
'tpl'
'hello world'
'hello yze'
NULL
'[sub-content|SECTION]'
TPL_EX:' 界面 no_such_tpl.tpl.php 不存在'
MST_EX:' master view no_such_master.tpl.php not '
