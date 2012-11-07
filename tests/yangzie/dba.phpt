--TEST--
DBA Class Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


$dba = DBAImpl::getDBA();
$sql = new SQL(); 

#find()
try{
	$entity = $dba->find(1234,"NotExistClass");
}catch(Exception $e){
	var_dump($e->getMessage());
}

$entity = $dba->find(123456789900,"TestOrderEntity");
var_dump($entity);

$entity = $dba->find(20031079,"TestOrderEntity");
var_dump($entity->getFieldValue("order_id"));

$entities = $dba->findAll("TestOrderEntity");
var_dump(count($entities));
var_dump($entities[0]->getFieldValue("order_id"));
?>
--EXPECTREGEX--
string\(36\) "Entity Class NotExistClass not found"
NULL
string\(8\) "20031079"
int\(\d+\)
string\((9|10)\) "\d{9,10}"