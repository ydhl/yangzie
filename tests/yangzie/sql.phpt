--TEST--
SQL Class Unit Test simple select sql
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/test_case_data/dba/select.php');#包含测试数据
require_once(RelativePath.'/tests/helper.inc.php');#包含测试数据
require_once(RelativePath.'/tests/dba/dba-inc.php');


$select = new SQL();

$select->select('*')->from('TestLineItemEntity','item');
_nl_echo($select->__toString());

$select->select('item',array('order_id','quote_id'))->from('TestLineItemEntity','item');
_nl_echo($select->__toString());

$select->clean();
$select->from('TestLineItemEntity','item');
_nl_echo($select->__toString());

$select->clean();
$select->from('TestLineItemEntity','item')->where('item','order_id',SQL::LIKE,"'1");
_nl_echo($select->__toString());

//DELETE
$select->clean();
$select->delete()->from('TestLineItemEntity','item')->where('item','order_id',SQL::LIKE,"'1");
_nl_echo($select->__toString());

//UPDATE
$select->clean();
$select->update('item',array('part_no'=>'new part no','order_id'=>123455,'comment'=>"'%_\aaa3"))
	->from('TestLineItemEntity','item')->where('item','order_id',SQL::BEFORE_LIKE,"'1");
_nl_echo($select->__toString());

//INSERT
$select->clean();
$select->insert('item',array('part_no'=>'new part no','order_id'=>123455,'comment'=>"'%_\aaa3"))
	->from('TestLineItemEntity','item');
_nl_echo($select->__toString());

#LIKE
$select->clean();
$select->from('TestLineItemEntity','item')->select("*")
	->where('item','order_id',SQL::LIKE,"'1")
	->where('item','part_no',SQL::BEFORE_LIKE,"a")
	->where('item','order_time',SQL::BETWEEN,array('2010-06-01','2010-06-02'));
_nl_echo($select->__toString());

#LIKE1
$select->clean();
$select->from('TestLineItemEntity','item')->select("*")
	->where('item','order_id',SQL::ISNOTNULL)
	->where('item','order_id',SQL::ISNULL)
	->where('item','order_id',SQL::LT,123)
	->where('item','order_id',SQL::LEQ,123)
	->where('item','order_id',SQL::NE,123)
	->where('item','order_id',SQL::NOTIN,123);
_nl_echo($select->__toString());

#LIKE2
$select->clean();
$select->from('TestLineItemEntity','item')->select("*")
	->where('item','order_id',SQL::EQ,"123456")
	->where('item','part_no',SQL::END_LIKE,"a")
	->where('item','order_id',SQL::GT,123)
	->where('item','order_id',SQL::GEQ,123)
	->where('item','order_id',SQL::IN,123)
	->where('item','order_id',SQL::IN,array(123,456));
_nl_echo($select->__toString());

$select->clean();
$select->from('TestLineItemEntity','item')
	->select("item",array('part_no'))->where("item","status",SQL::IN,array('pending'))
	->limit(0,10);
_nl_echo($select->__toString());

$select->clean();
$select->from('TestLineItemEntity','item')
	->select("item",array('part_no'))->where("item","status",SQL::IN,array('pending'))
	->limit(10);
_nl_echo($select->__toString());

$select->clean();
$select->from('TestLineItemEntity','item')
	->select("item",array('part_no'))->where("item","status",SQL::IN,array('pending'))
	->orWhere("item","status",SQL::IN,array('pending'))
	->orWhere("item","status",SQL::IN,array('pending'))
	->where("item","status",SQL::IN,array('pending'))
	->limit(10);
_nl_echo($select->__toString());
?>
--EXPECT--
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id 
FROM quote_order_items AS item
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.order_id LIKE '%\'1%'
DELETE FROM quote_order_items 
WHERE `order_id` LIKE '%\'1%'
UPDATE quote_order_items AS item 
SET item.part_no='new part no',item.order_id=123455,item.comment='\'%_\\aaa3' 
WHERE item.order_id LIKE '%\'1'
INSERT INTO quote_order_items (`part_no`,`order_id`,`comment`) 
VALUES('new part no',123455,'\'%_\\aaa3')
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.order_id LIKE '%\'1%' AND item.part_no LIKE '%a' AND item.order_time BETWEEN '2010-06-01' AND '2010-06-02'
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.order_id IS NOT NULL AND item.order_id IS NULL AND item.order_id < 123 AND item.order_id <= 123 AND item.order_id != 123 AND item.order_id NOT IN (123)
SELECT item.order_id AS item_order_id,item.quote_id AS item_quote_id,item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.order_id = 123456 AND item.part_no LIKE 'a%' AND item.order_id > 123 AND item.order_id >= 123 AND item.order_id IN (123) AND item.order_id IN (123,456)
SELECT item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.status IN ('pending') LIMIT 0 , 10
SELECT item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.status IN ('pending') LIMIT 10
SELECT item.part_no AS item_part_no 
FROM quote_order_items AS item 
WHERE item.status IN ('pending') OR item.status IN ('pending') OR item.status IN ('pending') AND item.status IN ('pending') LIMIT 10