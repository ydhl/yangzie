--TEST--
Yangzie RPC Test 
--FILE--
<?php
namespace  yangzie;
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

//test invoke local 
$rpc = new YangzieRPC();

echo $rpc->invoke(__NAMESPACE__ .'\YZE_Object::the_val', array(false, "true"))."\r\n";

echo $rpc->invoke(__NAMESPACE__ .'\YZE_Object::the_val', array(false, "true"),"http://l.yangzie.net");
?>
--EXPECT--
true
true