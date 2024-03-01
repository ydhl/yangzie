--TEST--
数据库事务测试（单数据库）
--FILE--
<?php
namespace  yangzie;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";


$sql = 'CREATE TABLE IF NOT EXISTS `tests_rollback` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `created_on` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `modified_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `title` VARCHAR(45) NOT NULL,
          PRIMARY KEY (`id`))';
$dba = YZE_DBAImpl::get_instance();
$dba->exec($sql);

$title = uniqid().time();

// 测试回滚
$dba->begin_Transaction();
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
$dba->rollBack();
var_dump($dba->in_transaction());

$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."-\r\n";

// 测试提交
$title = 'newtitle';
$dba->begin_Transaction();
$dba->exec("delete from tests_rollback where title='{$title}'");
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
$dba->commit();
var_dump($dba->in_transaction());

$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";


// 没有开启事务
$title = 'newtitle2';
$dba->exec("delete from tests_rollback where title='{$title}'");
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
var_dump($dba->in_transaction());

$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n"
?>
--EXPECT--
bool(true)
bool(false)
-
bool(true)
bool(false)
newtitle
bool(false)
bool(false)
newtitle2
