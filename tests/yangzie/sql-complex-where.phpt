--TEST--
SQL Class Unit Test complex where sql
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/helper.inc.php');#包含测试数据
require_once(RelativePath.'/tests/dba/dba-inc.php');

$select = new SQL();
#(o.status='completed' and o.order_time='2010-9-6') or (o.po='PO' and o.customer_id=20002)
$select->whereGroup(array(
			new Where('o','status',SQL::EQ,'completed'),
			new Where('o','order_time',SQL::EQ,'2010-9-6')
		))->orWhereGroup(array(
			new Where('o','po',SQL::EQ,'PO'),
			new Where('o','customer_id',SQL::EQ,20002))
		)->from("TestOrderEntity","o");
_nl_echo($select->__toString());

$select->clean();
#o.status='completed' and (o.order_time='2010-9-6' or o.po='PO') and o.customer_id=20002
$select->where('o','status',SQL::EQ,'completed')
	->whereGroup(array(
		new Where('o','order_time',SQL::EQ,'2010-9-6'),
		new Where('o','po',SQL::EQ,'PO',"OR")
	))->where('o','customer_id',SQL::EQ,20002)->from("TestOrderEntity","o");
_nl_echo($select);

$select->clean();
#o.status='completed' and o.order_time='2010-9-6' or o.po='PO' and o.customer_id=20002
$select->where('o','status',SQL::EQ,'completed')
	->where('o','order_time',SQL::EQ,'2010-9-6')
	->orwhere('o','po',SQL::EQ,'PO',"OR")
	->where('o','customer_id',SQL::EQ,20002)->from("TestOrderEntity","o");
_nl_echo($select);
?>
--EXPECTREGEX--
SELECT o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added 
FROM orders AS o 
WHERE \( o.status = 'completed' AND o.order_time = '2010-9-6'\) OR \( o.po = 'PO' AND o.customer_id = 20002\)
SELECT o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added 
FROM orders AS o 
WHERE o.status = 'completed' AND o.customer_id = 20002 AND \( o.order_time = '2010-9-6' OR o.po = 'PO'\)
SELECT o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added 
FROM orders AS o 
WHERE o.status = 'completed' AND o.order_time = '2010-9-6' OR o.po = 'PO' AND o.customer_id = 20002