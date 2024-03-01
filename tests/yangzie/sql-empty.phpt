--TEST--
YZE_SQL 测试（只实例化）
--FILE--
<?php
namespace  yangzie;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

$sql = new \yangzie\YZE_SQL();
echo $sql,"-\r\n";

?>
--EXPECT--
SELECT * FROM -
