--TEST--
数据库事务测试（多数据库）
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

$dba2 = YZE_DBAImpl::get_instance('test2');
$dba2->exec($sql);

$title = uniqid().time();

echo "测试两个库同时回滚\r\n";
$dba->begin_Transaction();
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
$dba->rollBack();
var_dump($dba->in_transaction());
$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."-\r\n";

$dba2->begin_Transaction();
$dba2->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba2->in_transaction());
$dba2->rollBack();
var_dump($dba2->in_transaction());
$rst = $dba2->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."-\r\n";

echo "测试同时提交\r\n";
$title = 'both commit';
$dba->begin_Transaction();
$dba->exec("delete from tests_rollback where title='{$title}'");
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
$dba->commit();
var_dump($dba->in_transaction());
$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";

$dba2->begin_Transaction();
$dba2->exec("delete from tests_rollback where title='{$title}'");
$dba2->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba2->in_transaction());
$dba2->commit();
var_dump($dba2->in_transaction());
$rst = $dba2->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";


echo "都没有开启事务\r\n";
$title = 'both no transaction';
$dba->exec("delete from tests_rollback where title='{$title}'");
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
var_dump($dba->in_transaction());

$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";

$dba2->exec("delete from tests_rollback where title='{$title}'");
$dba2->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba2->in_transaction());
var_dump($dba2->in_transaction());

$rst = $dba2->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";


echo "测试一个提交一个回滚\r\n";
$title = 'one commit one rollback';
$dba->begin_Transaction();
$dba->exec("delete from tests_rollback where title='{$title}'");
$dba->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba->in_transaction());
$dba->commit();
var_dump($dba->in_transaction());
$rst = $dba->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."\r\n";

$dba2->begin_Transaction();
$dba2->exec("delete from tests_rollback where title='{$title}'");
$dba2->exec("insert into tests_rollback(title) values('{$title}')");
var_dump($dba2->in_transaction());
$dba2->rollBack();
var_dump($dba2->in_transaction());
$rst = $dba2->native_Query("select title from tests_rollback where title='{$title}'");
$rst->next();
echo $rst->f('title')."-\r\n";
?>
--EXPECT--
测试两个库同时回滚
bool(true)
bool(false)
-
bool(true)
bool(false)
-
测试同时提交
bool(true)
bool(false)
both commit
bool(true)
bool(false)
both commit
都没有开启事务
bool(false)
bool(false)
both no transaction
bool(false)
bool(false)
both no transaction
测试一个提交一个回滚
bool(true)
bool(false)
one commit one rollback
bool(true)
bool(false)
-
