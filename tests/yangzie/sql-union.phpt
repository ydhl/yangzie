--TEST--
SQL Class Unit Test complex select sql
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/test_case_data/dba/select.php');#包含测试数据
require_once(RelativePath.'/tests/helper.inc.php');#包含测试数据
require_once(RelativePath.'/tests/dba/dba-inc.php');

$select = new SQL();

$select->select('*')->from('TestLineItemEntity','item')->leftjoin('TestOrderEntity','o','o.order_id=item.order_id')
	->where("item","order_id",SQL::EQ,123);
_nl_echo($select->__toString());

$select->clean();
$select->select('*')->from('TestLineItemEntity','item')->join('TestOrderEntity','o','o.order_id=item.order_id')
	->where("item","order_id",SQL::EQ,123);
_nl_echo($select->__toString());



$select->clean();
$select->select('*')->from('TestLineItemEntity','item')
	->leftjoin('TestOrderEntity','o','o.order_id=item.order_id')
	->leftjoin('TestCustomerEntity','c','c.customer_id=o.customer_id')
	->where("item","order_id",SQL::EQ,123);
_nl_echo($select->__toString());
?>
--EXPECTREGEX--
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no,o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added 
FROM quote_order_items AS item  LEFT JOIN orders AS o ON o.order_id=item.order_id 
WHERE item.order_id = 123
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no,o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added 
FROM quote_order_items AS item  INNER JOIN orders AS o ON o.order_id=item.order_id 
WHERE item.order_id = 123
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no,o.order_id AS o_order_id,o.order_time AS o_order_time,o.order_status AS o_order_status,o.last_modified AS o_last_modified,o.date_added AS o_date_added,c.customer_id AS c_customer_id,c.first_name AS c_first_name,c.email AS c_email,c.last_modified AS c_last_modified,c.date_added AS c_date_added 
FROM quote_order_items AS item  LEFT JOIN orders AS o ON o.order_id=item.order_id  LEFT JOIN customers AS c ON c.customer_id=o.customer_id 
WHERE item.order_id = 123