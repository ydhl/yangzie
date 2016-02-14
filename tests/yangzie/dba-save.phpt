--TEST--
DBA new save Function Tester 
--FILE--
<?php
namespace  yangzie;
ini_set("display_errors",1);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

//this test only work for me 

$db   = \yangzie\YZE_DBAImpl::getDBA();
$user = new \app\common\User_Model();

$user->set("name", "aa");
$user->set("email", "333");
$user->set("register_time", "2015-12-17 17:50:30");


$sql = new \yangzie\YZE_SQL();
$sql->clean()->insert('t',$user->get_records())->from(get_class($user),"t");
echo $sql,"\r\n";
$sql->clean()->insert('t',$user->get_records(), YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE)->from(get_class($user),"t");
echo $sql,"\r\n";
$sql->clean()->insert('t',$user->get_records(), YZE_SQL::INSERT_ON_DUPLICATE_KEY_REPLACE)->from(get_class($user),"t");
echo $sql,"\r\n";
$sql->clean()->insert('t',$user->get_records(), YZE_SQL::INSERT_EXIST)->from(get_class($user),"t")->where("t","id","=",1);
echo $sql,"\r\n";
$sql->clean()->insert('t',$user->get_records(), YZE_SQL::INSERT_NOT_EXIST)->from(get_class($user),"t")->where("t","id","=",1);
echo $sql,"\r\n";
$sql->clean()->insert('t',$user->get_records(), YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE, array("email"))->from(get_class($user),"t")->where("t","id","=",1);
echo $sql,"\r\n";
?>
--EXPECT--
INSERT INTO users (`name`,`email`,`register_time`) VALUES('aa','333','2015-12-17 17:50:30')
INSERT IGNORE INTO users (`name`,`email`,`register_time`) VALUES('aa','333','2015-12-17 17:50:30')
REPLACE INTO users SET `name`='aa',`email`='333',`register_time`='2015-12-17 17:50:30'
INSERT INTO users (`name`,`email`,`register_time`) SELECT 'aa','333','2015-12-17 17:50:30' FROM dual WHERE EXISTS (SELECT id FROM users WHERE `id` = 1)
INSERT INTO users (`name`,`email`,`register_time`) SELECT 'aa','333','2015-12-17 17:50:30' FROM dual WHERE NOT EXISTS (SELECT id FROM users WHERE `id` = 1)
INSERT INTO users (`name`,`email`,`register_time`) VALUES('aa','333','2015-12-17 17:50:30')  ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), `name`=VALUES(`name`),`register_time`=VALUES(`register_time`)