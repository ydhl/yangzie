--TEST--
distinct sql Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


#find()
$sql = new SQL();

$sql->distinct('item', 'qo_item_id')->select('item',array('part_no'))->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 

$sql->clean()->select('item',array('part_no'))->distinct('item', 'qo_item_id')->from("TestLineItemEntity",'item');
echo $sql->__toString()."\r\n"; 

?>
--EXPECTREGEX--
SELECT distinct item\.qo_item_id AS item_qo_item_id,item\.part_no AS item_part_no 
FROM quote_order_items AS item
SELECT distinct item\.qo_item_id AS item_qo_item_id,item\.part_no AS item_part_no 
FROM quote_order_items AS item