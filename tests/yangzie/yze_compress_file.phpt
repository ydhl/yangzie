--TEST--
yze_compress_file unit test
--FILE--
<?php
ini_set("display_errors",1);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include "load.php";
//write you test code here

//创建测试文件
$file_a = dirname(__FILE__)."/a.css";
$file_b = dirname(__FILE__)."/b.css";
$file_c = dirname(__FILE__)."/c.css";
file_put_contents($file_a, "this is css file a");
file_put_contents($file_b, "this is css file b");
file_put_contents($file_c,"this is css file c");

$expected_file_name = yze_remove_abs_path(APP_CACHES_PATH."compressed/".md5($file_a.$file_b.$file_c)."-".md5(filemtime($file_a).filemtime($file_b).filemtime($file_c)).".css");

$compress_file_name = yze_compress_file($file_a, $file_b, $file_c);

echo $expected_file_name==$compress_file_name ? "file name correct\n" : "file name not correct\n";
echo file_get_contents(yze_get_abs_path($compress_file_name))=="this is css file a
this is css file b
this is css file c
" ? "file content correct" : "file content not correct";

unlink($file_a);
unlink($file_b);
unlink($file_c);
unlink(yze_get_abs_path($compress_file_name));
?>
--EXPECT--
file name correct
file content correct