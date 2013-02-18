--TEST--
groupby order by Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


#find()
$sql = new SQL();

$sql->sum('item', 'qo_item_id','total')->from("TestLineItemEntity",'item')
	->orderBy('item', 'qo_item_id', SQL::ASC)->orderBy('item', 'part_no', SQL::DESC);
echo $sql->__toString()."\r\n\r\n"; 

$sql->clean()->sum('item', 'qo_item_id','total')->count('item', 'qo_item_id','amount')
	->from("TestLineItemEntity",'item')->groupBy('item', 'qo_item_id');
echo $sql->__toString()."\r\n\r\n"; 

$sql->clean()->count('item', '*','total')->from("TestLineItemEntity",'item')
	->orderBy('item', 'qo_item_id', SQL::ASC)
	->orderBy('item', 'part_no', SQL::DESC)
	->groupBy('item', 'qo_item_id')
	->groupBy('item', 'part_no');
echo $sql->__toString()."\r\n\r\n"; 
?>
--EXPECTREGEX--
SELECT sum\(item\.qo_item_id\) AS item_total 
FROM quote_order_items AS item ORDER BY item.qo_item_id ASC,item.part_no DESC

SELECT count\(item.qo_item_id\) AS item_amount,sum\(item\.qo_item_id\) AS item_total 
FROM quote_order_items AS item GROUP BY item\.qo_item_id

SELECT count\(\*\) AS item_total 
FROM quote_order_items AS item GROUP BY item\.qo_item_id,item\.part_no ORDER BY item.qo_item_id ASC,item\.part_no DESC