--TEST--
YZE_SQL select 查询构建：from/select/*/distinct/count/sum/max/min/多表
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include dirname(__FILE__)."/sql_test_models.php";

use yangzie\YZE_SQL;
use yangzie\T_User;
use yangzie\T_Order;

// 输出 SQL，规范换行便于比对
function s($sql){ return str_replace("\r\n","\n",(string)$sql); }

// 1. 无 select，展开全部列
$sql = YZE_SQL::new_SQL()->from(T_User::class);
echo s($sql),"\n";

// 2. select 指定字段
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id','name'));
echo s($sql),"\n";

// 3. select '*' 展开指定表全部列
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('*'));
echo s($sql),"\n";

// 4. distinct
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->distinct('m','role');
echo s($sql),"\n";

// 5. count(*)
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->count('m','*','cnt');
echo s($sql),"\n";

// 6. count(distinct field)
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->count('m','id','cnt',true);
echo s($sql),"\n";

// 7. sum
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->sum('m','price','total');
echo s($sql),"\n";

// 8. max + min 混用
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->max('m','price','mx')->min('m','price','mn');
echo s($sql),"\n";

// 9. count + sum 与 select 混用
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('role'))->count('m','*','cnt')->sum('m','price','total');
echo s($sql),"\n";

// 10. 多表 from + join（from 只能调用一次，其他表用 join 加入）
// ai@2026-08-28 from 仅能调用一次，多表场景需配合 join
$sql = YZE_SQL::new_SQL()->from(T_User::class,'u')
    ->join(T_Order::class,'o','o.user_id=u.id');
echo s($sql),"\n";

// 11. 多表 select 指定字段（含 join 表）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'u')
    ->join(T_Order::class,'o','o.user_id=u.id')
    ->select('u',array('id','name'))->select('o',array('user_id','amount'));
echo s($sql),"\n";

// 12. get_select_classes
$sql = YZE_SQL::new_SQL()->from(T_User::class,'u')->select('u',array('id'));
echo json_encode($sql->get_select_classes()),"\n";
echo json_encode($sql->get_select_classes(true)),"\n";
$sql2 = YZE_SQL::new_SQL()->from(T_User::class,'u');
echo json_encode($sql2->get_select_classes()),"\n";
?>
--EXPECT--
SELECT `m`.`id` AS `m_id`,`m`.`name` AS `m_name`,`m`.`role` AS `m_role`,`m`.`price` AS `m_price`,`m`.`created_on` AS `m_created_on` FROM `users` AS `m`
SELECT `m`.`id` AS `m_id`,`m`.`name` AS `m_name` FROM `users` AS `m`
SELECT `m`.`id` AS `m_id`,`m`.`name` AS `m_name`,`m`.`role` AS `m_role`,`m`.`price` AS `m_price`,`m`.`created_on` AS `m_created_on` FROM `users` AS `m`
SELECT distinct `m`.`role` AS `m_role` FROM `users` AS `m`
SELECT count( *) AS `m_cnt` FROM `users` AS `m`
SELECT count(distinct `m`.`id`) AS `m_cnt` FROM `users` AS `m`
SELECT sum(`m`.`price`) AS `m_total` FROM `users` AS `m`
SELECT max(`m`.`price`) AS `m_mx`,min(`m`.`price`) AS `m_mn` FROM `users` AS `m`
SELECT `m`.`role` AS `m_role`,count( *) AS `m_cnt`,sum(`m`.`price`) AS `m_total` FROM `users` AS `m`
SELECT `u`.`id` AS `u_id`,`u`.`name` AS `u_name`,`u`.`role` AS `u_role`,`u`.`price` AS `u_price`,`u`.`created_on` AS `u_created_on`,`o`.`id` AS `o_id`,`o`.`user_id` AS `o_user_id`,`o`.`amount` AS `o_amount`,`o`.`status` AS `o_status` FROM `users` AS `u` INNER JOIN `orders` AS `o` ON o.user_id=u.id
SELECT `u`.`id` AS `u_id`,`u`.`name` AS `u_name`,`o`.`user_id` AS `o_user_id`,`o`.`amount` AS `o_amount` FROM `users` AS `u` INNER JOIN `orders` AS `o` ON o.user_id=u.id
{"u":"yangzie\\T_User"}
{"u":"yangzie\\T_User"}
{"u":"yangzie\\T_User"}
