--TEST--
DBA Class Unit Test
--FILE--
<?php
define('RelativePath','..');
require_once(RelativePath.'/Common.php');
require_once(RelativePath.'/tests/dba/dba-inc.php');


$dba = DBAImpl::getDBA();
$sql = new SQL(); 

$sql->select('*')->from('TestLineItemEntity','item')
	->leftjoin('TestOrderEntity','o','o.order_id=item.order_id')
	->where("item","order_id",SQL::IN,array(20041110,402091210))
	->limit(2);
$wrapper = $dba->nativeQuery($sql);
var_dump(get_class($wrapper));
while($wrapper->next_record()){
	var_dump($wrapper->f("o","order_id"));
	var_dump($wrapper->f("item","part_no"));
}

$sql->select('item',array('order_id','req'))->from('TestLineItemEntity','item')
	->where("item","order_id",SQL::IN,array(20041110,402091210))
	->limit(2);
$wrapper = $dba->nativeQuery($sql);
var_dump(get_class($wrapper));
while($wrapper->next_record()){
	var_dump($wrapper->f("item","order_id"));
	var_dump($wrapper->f("item","req"));
}
?>
--EXPECTREGEX--
string\(14\) "DBMySQLWrapper"
string\(8\) "20041110"
string\(7\) "PCBIO2P"
string\(9\) "402091210"
string\(14\) "2641revA\(2567\)"
string\(14\) "DBMySQLWrapper"
string\(8\) "20041110"
string\(3\) "fab"
string\(9\) "402091210"
string\(8\) "assemble"