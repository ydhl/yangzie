--TEST--
abstract class Entity Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


#find()
$entity = TestOrderEntity::find(20031079,"TestOrderEntity");
var_dump(get_class($entity));
var_dump($entity->getFieldValue("order_id"));
var_dump($entity->getFieldValue("order_status"));

#save
$entity->save();
?>
--EXPECT--
string(15) "TestOrderEntity"
string(8) "20031079"
string(13) "In-Processing"