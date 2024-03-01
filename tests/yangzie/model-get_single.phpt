--TEST--
YZE_SQL 测试（只实例化）
--FILE--
<?php
namespace  yangzie;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

$sql = 'CREATE TABLE IF NOT EXISTS `tests` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `modified_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `title` VARCHAR(45) NOT NULL,
          PRIMARY KEY (`id`))';
$dba = YZE_DBAImpl::get_instance();
$dba->exec($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `tests_2022` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `modified_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `title` VARCHAR(45) NOT NULL,
          PRIMARY KEY (`id`))';
$dba = YZE_DBAImpl::get_instance();
$dba->exec($sql);

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

$query = TestModel::from();

$model = $query->get_single();
$sql = $query->get_sql();
echo $sql,"-\r\n";

$model = $query->clean()->suffix('_2022')->get_single();
$sql = $query->get_sql();
echo $sql,"-\r\n";

$query = TestModel::from('a')->left_join(TestModel::class, 'b', 'b.id=a.id', '_2022')
->where('a.id=:id');
$query->get_single([':id'=>1]);
$sql = $query->get_sql();
echo $sql,"-\r\n";

$query = TestModel::from('a')->left_join(TestModel::class, 'b', 'b.id=a.id', '_2022')
->where('a.id=:id');
$query->get_single([':id'=>1],'b');
$sql = $query->get_sql();
echo $sql,"-\r\n";
?>
--EXPECT--
SELECT m.id AS m_id,m.title AS m_title,m.created_on AS m_created_on,m.modified_on AS m_modified_on FROM `tests` AS m LIMIT 1-
SELECT m.id AS m_id,m.title AS m_title,m.created_on AS m_created_on,m.modified_on AS m_modified_on FROM `tests_2022` AS m LIMIT 1-
SELECT a.id AS a_id,a.title AS a_title,a.created_on AS a_created_on,a.modified_on AS a_modified_on,b.id AS b_id,b.title AS b_title,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests` AS a LEFT JOIN `tests_2022` AS b ON b.id=a.id WHERE a.id=:id LIMIT 1-
SELECT b.id AS b_id,b.title AS b_title,b.created_on AS b_created_on,b.modified_on AS b_modified_on FROM `tests` AS a LEFT JOIN `tests_2022` AS b ON b.id=a.id WHERE a.id=:id LIMIT 1-
