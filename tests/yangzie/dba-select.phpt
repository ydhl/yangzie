--TEST--
DBA Class Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');

$dba = DBAImpl::getDBA();
$sql = new SQL(); 

$sql->select("*")->from("TestOrderEntity","o")
	->where("o","order_status",SQL::EQ,"pending")
	->where("o","order_id",SQL::ISNOTNULL);
$entities = $dba->select($sql);
var_dump(count($entities));
var_dump($entities[0]->getFieldValue("order_status"));

$sql->clean();
$sql->select("*")->from("TestOrderEntity","o")->join("TestCustomerEntity","c","c.customer_id=o.customer_id")
	->where("o","order_status",SQL::EQ,"pending")
	->where("o","order_id",SQL::ISNOTNULL)
	->limit(3);
$entities = $dba->select($sql);
var_dump(count($entities));
var_dump(count($entities[0]));
var_dump(get_class($entities[0]['o']));
var_dump(get_class($entities[0]['c']));
foreach($entities as $entity){
	var_dump($entity['o']->getFieldValue("order_id"));
	var_dump($entity['c']->getFieldValue("customer_id"));
}

$sql->clean();
$sql->select("*")->from("TestOrderEntity","o")->join("TestCustomerEntity","c","c.customer_id=o.customer_id")
	->where("o","order_status",SQL::EQ,"pending")
	->where("o","order_id",SQL::EQ,404281550)
	->limit(1);
$entities = $dba->select($sql);
var_dump($entities[0]['o']->getFieldValue("order_id"));
var_dump($entities[0]['o']->getFieldValue("last_modified"));
var_dump($entities[0]['c']->getFieldValue("customer_id"));
var_dump($entities[0]['c']->getFieldValue("last_modified"));
?>
--EXPECTREGEX--
int\(\d+\)
string\(7\) "pending"
int\(3\)
int\(2\)
string\(15\) "TestOrderEntity"
string\(18\) "TestCustomerEntity"
string\((9|10)\) "\d{9,10}"
string\(5\) "\d{5}"
string\((9|10)\) "\d{9,10}"
string\(5\) "\d{5}"
string\((9|10)\) "\d{9,10}"
string\(5\) "\d{5}"
string\(9\) "404281550"
string\(19\) "2009-09-22 08:58:53"
string\(5\) "20144"
string\(19\) "2010-02-08 15:35:00"