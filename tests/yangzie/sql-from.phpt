--TEST--
YZE_SQL 测试（单表FROM）
--FILE--
<?php
namespace  yangzie;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";


class TestModel extends YZE_Model{
	const TABLE= "tests";
	const VERSION = 'modified_on';
	const MODULE_NAME = "test";
	const KEY_NAME = "id";
	const F_ID = "id";
	const CLASS_NAME = 'yangzie\TestModel';

	const F_TITLE = "title";
	const F_CREATED_ON = "created_on";
	const F_MODIFIED_ON = "modified_on";

	public static $columns = array(
			'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'title'      => array('type' => 'string', 'null' => false,'length' => '201','default'	=> '',),
			'created_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> '',),
			'modified_on' => array('type' => 'TIMESTAMP', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),
	);
}

$sql = new \yangzie\YZE_SQL();
$sql->from(TestModel::class);
echo $sql,"\r\n";

$sql->clean()->from(TestModel::class, 'test');
echo $sql,"\r\n";

?>
--EXPECT--
SELECT m.id AS m_id,m.title AS m_title,m.created_on AS m_created_on,m.modified_on AS m_modified_on FROM `tests` AS m
SELECT test.id AS test_id,test.title AS test_title,test.created_on AS test_created_on,test.modified_on AS test_modified_on FROM `tests` AS test
