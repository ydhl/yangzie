--TEST--
YZE_SQL 写操作：insert 各类型/update/delete/isinsert/isdelete
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include dirname(__FILE__)."/sql_test_models.php";

use yangzie\YZE_SQL;
use yangzie\T_User;

function s($sql){ return str_replace("\r\n","\n",(string)$sql); }

// 1. insert normal
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null));
echo s($sql),"\n";

// 2. insert on duplicate key ignore
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE);
echo s($sql),"\n";

// 3. insert on duplicate key update（unique_key 传 'id'）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE, array('id'));
echo s($sql),"\n";

// 4. insert replace
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_ON_DUPLICATE_KEY_REPLACE);
echo s($sql),"\n";

// 5. insert exist（check_sql 子查询）
$check = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->where("m.id=1");
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_EXIST, $check);
echo s($sql),"\n";

// 6. insert not exist（无 check_sql，走 KEY_NAME + where 反查）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->where("`name`='admin'")->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST);
echo s($sql),"\n";

// 7. insert not exist or update
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->where("`name`='admin'")->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE);
echo s($sql),"\n";

// 8. update
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->update('m', array('price'=>99.5,'name'=>null));
echo s($sql),"\n";

// 9. update + where
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->update('m', array('price'=>99.5))->where("m.id=1");
echo s($sql),"\n";

// 10. delete
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->where("id=7")->delete();
echo s($sql),"\n";

// 11. delete 无 where
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->delete();
echo s($sql),"\n";

// 12. isinsert / isdelete
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1));
var_export($sql->isinsert()); echo "\n";
var_export($sql->isdelete()); echo "\n";
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->delete();
var_export($sql->isdelete()); echo "\n";
var_export($sql->isinsert()); echo "\n";
?>
--EXPECT--
INSERT INTO `users` (`id`,`name`) VALUES(1,null)
INSERT IGNORE INTO `users` (`id`,`name`) VALUES(1,null)
INSERT INTO `users` (`id`,`name`) VALUES(1,null)  ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `name`=VALUES(`name`)
REPLACE INTO `users` SET `id`=1,`name`=null
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE EXISTS (SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.id=1)
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `id` FROM `users` WHERE `name`='admin')
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `id` FROM `users` WHERE `name`='admin')
UPDATE `users` AS `m` 
SET `m`.`price`=99.5,`m`.`name`=null
UPDATE `users` AS `m` 
SET `m`.`price`=99.5 
WHERE m.id=1
DELETE FROM `users` 
WHERE id=7
DELETE FROM `users`
true
false
true
false
