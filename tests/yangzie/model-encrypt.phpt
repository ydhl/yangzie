--TEST--
scripts/yze.php parse_encrypt_fields 与 generate-model.php encrypt_fields 默认值（不连库）
--FILE--
<?php
namespace yangzie;
ini_set("display_errors",0);
$ROOT = dirname(dirname(dirname(__FILE__)));
// ai@2026-09-13 提取 yze.php 中 get_colored_text 之后的真实函数定义（含 is_validate_column/parse_encrypt_fields），避免复制副本与源码不同步
$yze = file_get_contents($ROOT."/scripts/yze.php");
$pos = strpos($yze, "function get_colored_text");
$tail = substr($yze, $pos);
$tail = substr($tail, 0, strrpos($tail, "?>")); // 去掉文件尾部的 PHP 结束标签
// ai@2026-09-13 eval 字符串内必须显式声明命名空间：eval 在运行时编译，函数/类定义默认进入全局命名空间
eval("namespace yangzie;" . $tail);
include $ROOT."/scripts/generate-model.php";

$fail = 0;
function check($cond, $label){ global $fail; echo ($cond ? "PASS: " : "FAIL: ").$label."\n"; if(!$cond) $fail++; }

// ai@2026-09-13 parse_encrypt_fields：没有有效字段名时不会访问数据库，直接返回空数组
check(parse_encrypt_fields("db", "tb", "") === array(), "empty string -> empty fields");
check(parse_encrypt_fields("db", "tb", " , ,, ") === array(), "separators only -> empty fields");
check(parse_encrypt_fields("db", "tb", array()) === array(), "empty array -> empty fields");
check(parse_encrypt_fields("db", "tb", array("", " ")) === array(), "array of blanks -> empty fields");

// ai@2026-09-13 Generate_Model_Script：未传入 encrypt_fields 时默认为空数组，关联表递归生成的 Model 因此不会带入加密标记
class T_Encrypt_Model extends Generate_Model_Script {
    public function encrypt_fields_of(){ return $this->encrypt_fields; }
}
$m = new T_Encrypt_Model(array("module_name"=>"admin", "class_name"=>"acl", "table_name"=>"acl", "db_name"=>"x", "base"=>"table"));
check($m->encrypt_fields_of() === array(), "default encrypt_fields is empty");

echo $fail === 0 ? "ALL PASS\n" : "SOME FAIL\n";
--EXPECT--
PASS: empty string -> empty fields
PASS: separators only -> empty fields
PASS: empty array -> empty fields
PASS: array of blanks -> empty fields
PASS: default encrypt_fields is empty
ALL PASS
