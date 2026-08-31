--TEST--
YZE_Object 框架基础类：the_val/format_class_name/模块注册/filter/defilter/env
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

use yangzie\YZE_Object;

// 输出函数：数组用 json 紧凑输出，其余用 var_export 保留类型
function s($v){
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return var_export($v, true);
}

// 1. the_val 默认值处理：真值返回原值，假值返回默认值
echo s(YZE_Object::the_val('abc','default')),"\n";
echo s(YZE_Object::the_val('','default')),"\n";
echo s(YZE_Object::the_val(0,'default')),"\n";
echo s(YZE_Object::the_val(false,'default')),"\n";
echo s(YZE_Object::the_val(null,'default')),"\n";
echo s(YZE_Object::the_val(array(),'default')),"\n";
echo s(YZE_Object::the_val(array(1),'default')),"\n";
echo s(YZE_Object::the_val(1,'default')),"\n";

// 2. format_class_name 下划线类名格式化（含大小写归一、去空白、后缀）
echo s(YZE_Object::format_class_name("user_profile","Model")),"\n";
echo s(YZE_Object::format_class_name("user_profile","")),"\n";
echo s(YZE_Object::format_class_name("aa_bb_cc","Test")),"\n";
echo s(YZE_Object::format_class_name("  AA_BB  ","X")),"\n";
echo s(YZE_Object::format_class_name("UserProfile","")),"\n";
echo s(YZE_Object::format_class_name("","")),"\n";

// 3. set_loaded_modules / loaded_module 模块注册与查询（大小写不敏感，未注册返回 null）
YZE_Object::set_loaded_modules("TestModule", array("name"=>"test","ver"=>"1.0"));
echo s(YZE_Object::loaded_module("TestModule")),"\n";
echo s(YZE_Object::loaded_module("testmodule")),"\n";
echo s(YZE_Object::loaded_module("not_loaded")),"\n";
// 重复注册覆盖
YZE_Object::set_loaded_modules("TestModule", "v2");
echo s(YZE_Object::loaded_module("testmodule")),"\n";

// 4. filter_var / defilter_var 单值转义与反转义（默认 ENT_COMPAT：单引号不转义）
echo s(YZE_Object::filter_var('<b>&"\'')),"\n";
echo s(YZE_Object::defilter_var('&lt;b&gt;&amp;&quot;&#039;')),"\n";
echo s(YZE_Object::defilter_var(YZE_Object::filter_var('<b>&"'))),"\n";

// 5. filter_vars 数组批量转义
echo s(YZE_Object::filter_vars(array('a'=>'<b>', 'b'=>'"&'))),"\n";

// 6. filter_special_chars 无输入来源时返回 NULL
echo s(YZE_Object::filter_special_chars(array())),"\n";

// 7. env 环境配置读取（实例方法）：未配置默认值/系统环境变量优先/.env 文件配置
$obj = new YZE_Object();
echo s($obj->env('YZE_UNIQUE_TEST_NOT_EXIST_KEY_ABC','default')),"\n";
putenv('YZE_UNIQUE_TEST_ENV_KEY=from_env');
echo s($obj->env('YZE_UNIQUE_TEST_ENV_KEY','default')),"\n";
echo s($obj->env('yangai_20260821.db_type','default')),"\n";

// 8. output 空方法
$obj->output();
echo "output_ok\n";
?>
--EXPECT--
'abc'
'default'
'default'
'default'
'default'
'default'
[1]
1
'User_Profile_Model'
'User_Profile'
'Aa_Bb_Cc_Test'
'Aa_Bb_X'
'Userprofile'
''
{"name":"test","ver":"1.0"}
{"name":"test","ver":"1.0"}
NULL
'v2'
'&lt;b&gt;&amp;&quot;&#039;'
'<b>&"\''
'<b>&"'
{"a":"&lt;b&gt;","b":"&quot;&amp;"}
NULL
'default'
'from_env'
'mysql'
output_ok
