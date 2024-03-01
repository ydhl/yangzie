--TEST--
YZE_SQL 测试（各种联合查询）
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
class TestItemModel extends YZE_Model{
	const TABLE= "test_item";
	const VERSION = 'modified_on';
	const MODULE_NAME = "test";
	const KEY_NAME = "id";
	const F_ID = "id";
	const CLASS_NAME = 'yangzie\TestItemModel';

	const F_TITLE = "title";
	const F_CREATED_ON = "created_on";
	const F_MODIFIED_ON = "modified_on";

	public static $columns = array(
			'id'         => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'title'      => array('type' => 'string', 'null' => false,'length' => '201','default'	=> '',),
			'test_id'      => array('type' => 'integer', 'null' => false,'length' => '11','default'	=> '',),
			'created_on' => array('type' => 'date', 'null' => false,'length' => '','default'	=> '',),
			'modified_on' => array('type' => 'TIMESTAMP', 'null' => false,'length' => '','default'	=> 'CURRENT_TIMESTAMP',),
	);
    protected $unique_key = array (
      'id' => 'PRIMARY',
      'test_id' => 'fk_test1_idx'
    );
}

$sql = new \yangzie\YZE_SQL();
$sql->clean()->from(TestModel::class, 'a')->left_join(TestItemModel::class, 'b', 'a.id = b.test_id');
echo $sql,"\r\n";

$sql->clean()->from(TestModel::class, 'a')->right_join(TestItemModel::class, 'b', 'a.id = b.test_id');
echo $sql,"\r\n";

$sql->clean()->from(TestModel::class, 'a')->join(TestItemModel::class, 'b', 'a.id = b.test_id');
echo $sql,"\r\n";

$sql->clean()->from(TestModel::class, 'a', '_2012')->join(TestItemModel::class, 'b', 'a.id = b.test_id', '_2013');
echo $sql,"\r\n";

?>
--EXPECT--
SELECT a.id AS a_id,a.title AS a_title,a.created_on AS a_created_on,a.modified_on AS a_modified_on,b.id AS b_id,b.title AS b_title,b.test_id AS b_test_id,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests` AS a LEFT JOIN `test_item` AS b ON a.id = b.test_id
SELECT a.id AS a_id,a.title AS a_title,a.created_on AS a_created_on,a.modified_on AS a_modified_on,b.id AS b_id,b.title AS b_title,b.test_id AS b_test_id,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests` AS a RIGHT JOIN `test_item` AS b ON a.id = b.test_id
SELECT a.id AS a_id,a.title AS a_title,a.created_on AS a_created_on,a.modified_on AS a_modified_on,b.id AS b_id,b.title AS b_title,b.test_id AS b_test_id,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests` AS a INNER JOIN `test_item` AS b ON a.id = b.test_id
SELECT a.id AS a_id,a.title AS a_title,a.created_on AS a_created_on,a.modified_on AS a_modified_on,b.id AS b_id,b.title AS b_title,b.test_id AS b_test_id,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests_2012` AS a INNER JOIN `test_item_2013` AS b ON a.id = b.test_id
