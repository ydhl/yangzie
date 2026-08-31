--TEST--
scripts/generate-model.php：get_type_info/getEnumConstant/create_enum_code/create_method_code
--FILE--
<?php
namespace yangzie;
ini_set("display_errors",0);
$ROOT = dirname(dirname(dirname(__FILE__)));
// ai@2026-08-31 从 yze.php 提取 AbstractScript 及辅助函数（get_colored_text/rrmdir 等）真实定义，避免复制副本与源码不同步
$yze = file_get_contents($ROOT."/scripts/yze.php");
$pos = strpos($yze, "function get_colored_text");
$tail = substr($yze, $pos);
$tail = substr($tail, 0, strrpos($tail, "?>")); // 去掉文件尾部的 PHP 结束标签
// ai@2026-08-31 eval 字符串内必须显式声明命名空间：eval 在运行时编译，函数/类定义默认进入全局命名空间
eval("namespace yangzie;" . $tail);
include $ROOT."/scripts/generate-model.php";
// ai@2026-08-31 防御性定义 i18n 函数（不加载 init.php 时 __() 不存在）
function __($s){ return $s; }

$fail = 0;
function check($cond, $label){ global $fail; echo ($cond ? "PASS: " : "FAIL: ").$label."\n"; if(!$cond) $fail++; }

// ai@2026-08-31 子类暴露 protected 方法；module_name/class_name/table_name 仅在 generate()（连库）中赋值，测试经 setter 注入
class T_Model extends Generate_Model_Script {
    public function gti($t){ return $this->get_type_info($t); }
    public function gec($n, $t){ return $this->getEnumConstant($n, $t); }
    public function set_fields($module, $class, $table){
        $this->module_name = $module;
        $this->class_name = $class;
        $this->table_name = $table;
    }
}
$m = new T_Model(array("module_name"=>"admin", "class_name"=>"acl", "table_name"=>"acl", "db_name"=>"x", "base"=>"table"));
$m->set_fields("admin", "acl", "acl");

// get_type_info：整数族 -> int + length
$t = $m->gti("int(11)");
check($t["type"]==="int" && $t["length"]==="11", "int(11) -> int/11");
$t = $m->gti("tinyint(1) unsigned");
check($t["type"]==="int" && $t["length"]==="1", "tinyint(1) unsigned -> int/1");
$t = $m->gti("bigint");
check($t["type"]==="int" && $t["length"]==="", "bigint -> int/''");
// 浮点族 -> float（decimal(10,2) 中 (10,2) 不匹配 /\(\d+\)/，length 为空）
$t = $m->gti("decimal(10,2)");
check($t["type"]==="float" && $t["length"]==="", "decimal(10,2) -> float/''");
$t = $m->gti("float");
check($t["type"]==="float" && $t["length"]==="", "float -> float/''");
// 日期族 -> date
$t = $m->gti("datetime");
check($t["type"]==="date" && $t["length"]==="", "datetime -> date/''");
$t = $m->gti("timestamp");
check($t["type"]==="date", "timestamp -> date");
// enum -> enum
$t = $m->gti("enum('a','b')");
check($t["type"]==="enum", "enum -> enum");
// 其他 -> string
$t = $m->gti("varchar(255)");
check($t["type"]==="string" && $t["length"]==="255", "varchar(255) -> string/255");
$t = $m->gti("text");
check($t["type"]==="string" && $t["length"]==="", "text -> string/''");

// getEnumConstant：enum 类型解析为 大写字段_值 => 原值
$c = $m->gec("status", "enum('draft','in-review','publish')");
check(is_array($c) && $c["STATUS_DRAFT"]==="draft" && $c["STATUS_IN_REVIEW"]==="in-review" && $c["STATUS_PUBLISH"]==="publish", "enum constants parsed");
// 非 enum 类型返回 null
check($m->gec("status", "int(11)") === null, "non-enum returns null");

// create_enum_code：生成 PHP enum 类型代码
$code = $m->create_enum_code("acl_status_enum", array("draft","publish"));
check(strpos($code, "enum acl_status_enum: string{") !== false, "enum code type line");
check(strpos($code, "case DRAFT = 'draft';") !== false && strpos($code, "case PUBLISH = 'publish';") !== false, "enum code cases");
check(strpos($code, "namespace app\\admin;") !== false, "enum code namespace");

// create_method_code：生成 method trait 代码
$code = $m->create_method_code("AclMethod");
check(strpos($code, "trait AclMethod{") !== false, "method code trait");
check(strpos($code, "namespace app\\admin;") !== false, "method code namespace");
check(strpos($code, "return 'acl model';") !== false, "method code description");

echo $fail === 0 ? "ALL PASS\n" : "SOME FAIL\n";
--EXPECT--
PASS: int(11) -> int/11
PASS: tinyint(1) unsigned -> int/1
PASS: bigint -> int/''
PASS: decimal(10,2) -> float/''
PASS: float -> float/''
PASS: datetime -> date/''
PASS: timestamp -> date
PASS: enum -> enum
PASS: varchar(255) -> string/255
PASS: text -> string/''
PASS: enum constants parsed
PASS: non-enum returns null
PASS: enum code type line
PASS: enum code cases
PASS: enum code namespace
PASS: method code trait
PASS: method code namespace
PASS: method code description
ALL PASS
