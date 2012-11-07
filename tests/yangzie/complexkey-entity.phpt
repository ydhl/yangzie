--TEST--
complexkey of entity Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


#find()
$entity = TestOrderEntity::find(array('qo_item_id'=>24860,'part_item_id'=>24861),"TestSourceEntity");
var_dump(get_class($entity));
var_dump($entity->getFieldValue("qo_item_id"));
var_dump($entity->getFieldValue("part_item_id"));
?>
--EXPECT--
string(16) "TestSourceEntity"
string(5) "24860"
string(5) "24861"