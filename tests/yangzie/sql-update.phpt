--TEST--
SQL Class Unit Test update,delete,insert sql
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/helper.inc.php');#包含测试数据
require_once(RelativePath.'/tests/dba/dba-inc.php');

$select = new SQL();
$select->update("o",array('order_status'=>'pending'))->from("TestOrderEntity","o");
_nl_echo($select);

$select->clean();
$select->update("o",array('order_status'=>'pending'))->from("TestOrderEntity","o")->where('o','order_id',SQL::END_LIKE,6658);
_nl_echo($select);

$select->clean();
$select->delete()->from("TestOrderEntity","o")->where('o','order_id',SQL::END_LIKE,6658);
_nl_echo($select);

$select->clean();
$select->insert("o",array('order_status'=>'pending','order_date'=>'2010-9-2'))->from("TestOrderEntity");
_nl_echo($select);
?>
--EXPECTREGEX--
UPDATE orders AS o 
SET o.order_status='pending'
UPDATE orders AS o 
SET o.order_status='pending' 
WHERE o.order_id LIKE '6658%'
DELETE FROM orders 
WHERE `order_id` LIKE '6658%'
INSERT INTO orders \(`order_status`,`order_date`\) 
VALUES\('pending','2010-9-2'\)