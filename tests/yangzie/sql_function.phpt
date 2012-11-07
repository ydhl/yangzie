--TEST--
sum,count,max,min functions Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


#find()
$sql = new SQL();

$sql->sum('item', 'qo_item_id','total')->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 

$sql->clean()->sum('item', 'qo_item_id','total')->count('item', 'qo_item_id','amount')->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 

$sql->clean()->count('item', '*','total')->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 

$sql->clean()->count('item', '*','total')->select('item',array('qo_item_id'))->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 
?>
--EXPECTREGEX--
SELECT sum\(item\.qo_item_id\) AS item_total 
FROM quote_order_items AS item
SELECT count\(item.qo_item_id\) AS item_amount,sum\(item\.qo_item_id\) AS item_total 
FROM quote_order_items AS item
SELECT count\(\*\) AS item_total 
FROM quote_order_items AS item
SELECT item\.qo_item_id AS item_qo_item_id,count\(\*\) AS item_total 
FROM quote_order_items AS item