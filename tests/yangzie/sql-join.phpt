--TEST--
YZE_SQL join/分表suffix/order_by/group_by/limit/辅助方法
--FILE--
<?php
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include dirname(__FILE__)."/sql_test_models.php";

use yangzie\YZE_SQL;
use yangzie\T_User;
use yangzie\T_Order;

function s($sql){ return str_replace("\r\n","\n",(string)$sql); }

// 1. left join
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->left_join(T_Order::class,'o','o.user_id=m.id')
    ->select('m',array('id'))->select('o',array('amount'));
echo s($sql),"\n";

// 2. right join
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->right_join(T_Order::class,'o','o.user_id=m.id')
    ->select('m',array('id'))->select('o',array('amount'));
echo s($sql),"\n";

// 3. inner join
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->join(T_Order::class,'o','o.user_id=m.id')
    ->select('m',array('id'))->select('o',array('amount'));
echo s($sql),"\n";

// 4. 三种 join 混合
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->left_join(T_Order::class,'o','o.user_id=m.id')
    ->right_join(T_User::class,'u','u.id=o.user_id')
    ->select('m',array('id'))->select('o',array('amount'));
echo s($sql),"\n";

// 5. 单表 suffix 分表
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m','2023')->select('m',array('id'));
echo s($sql),"\n";

// 6. join 表 suffix 分表
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m','2023')
    ->left_join(T_Order::class,'o','o.user_id=m.id','2023')
    ->select('m',array('id'))->select('o',array('user_id'));
echo s($sql),"\n";

// 7. get_suffixs
echo json_encode($sql->get_suffixs()),"\n";

// 8. order_by 显式升序（sort 参数必填）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->order_by('m','id','asc');
echo s($sql),"\n";

// 9. order_by 显式 desc
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->order_by('m','id','desc');
echo s($sql),"\n";

// 10. order_by use_alias
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->order_by('m','id','asc',true);
echo s($sql),"\n";

// 11. 多 order_by
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->order_by('m','id','desc')->order_by('m','price','asc');
echo s($sql),"\n";

// 12. group_by
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('role'))->group_by('m','role');
echo s($sql),"\n";

// 13. group_by use_alias
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('role'))->group_by('m','role',true);
echo s($sql),"\n";

// 14. group_by_function
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('created_on'))->group_by_function("DATE(m.created_on)");
echo s($sql),"\n";

// 15. limit(10)
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->limit(10);
echo s($sql),"\n";

// 16. limit(0,10)
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->limit(0,10);
echo s($sql),"\n";

// 17. limit(20,10)
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->limit(20,10);
echo s($sql),"\n";

// 18. limit(0) 不生成 limit
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->limit(0);
echo s($sql),"\n";

// 19. get_alias
// ai@2026-08-28 from 仅能调用一次，多表场景需配合 join
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->join(T_Order::class,'o','o.user_id=m.id');
echo $sql->get_alias('users'),"\n";
var_export($sql->get_alias('not_exists')); echo "\n";

// 20. get_select_table（含 suffix）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m','2023')->left_join(T_Order::class,'o','o.user_id=m.id','2023');
echo json_encode($sql->get_select_table()),"\n";

// 21. has_join / has_from
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->left_join(T_Order::class,'o','o.user_id=m.id');
var_export($sql->has_join()); echo "\n";
var_export($sql->has_from()); echo "\n";
$sql2 = YZE_SQL::new_SQL()->from(T_User::class,'m');
var_export($sql2->has_join()); echo "\n";
var_export($sql2->has_from()); echo "\n";
$sql3 = YZE_SQL::new_SQL();
var_export($sql3->has_from()); echo "\n";
?>
--EXPECT--
SELECT `m`.`id` AS `m_id`,`o`.`amount` AS `o_amount` FROM `users` AS `m` LEFT JOIN `orders` AS `o` ON o.user_id=m.id
SELECT `m`.`id` AS `m_id`,`o`.`amount` AS `o_amount` FROM `users` AS `m` RIGHT JOIN `orders` AS `o` ON o.user_id=m.id
SELECT `m`.`id` AS `m_id`,`o`.`amount` AS `o_amount` FROM `users` AS `m` INNER JOIN `orders` AS `o` ON o.user_id=m.id
SELECT `m`.`id` AS `m_id`,`o`.`amount` AS `o_amount` FROM `users` AS `m` LEFT JOIN `orders` AS `o` ON o.user_id=m.id RIGHT JOIN `users` AS `u` ON u.id=o.user_id
SELECT `m`.`id` AS `m_id` FROM `users2023` AS `m`
SELECT `m`.`id` AS `m_id`,`o`.`user_id` AS `o_user_id` FROM `users2023` AS `m` LEFT JOIN `orders2023` AS `o` ON o.user_id=m.id
{"m":"2023","o":"2023"}
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` ORDER BY `m`.`id` ASC
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` ORDER BY `m`.`id` DESC
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` ORDER BY `m_id` ASC
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` ORDER BY `m`.`id` DESC,`m`.`price` ASC
SELECT `m`.`role` AS `m_role` FROM `users` AS `m` GROUP BY `m`.`role`
SELECT `m`.`role` AS `m_role` FROM `users` AS `m` GROUP BY `m_role`
SELECT `m`.`created_on` AS `m_created_on` FROM `users` AS `m` GROUP BY DATE(m.created_on)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` LIMIT 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` LIMIT 0 , 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` LIMIT 20 , 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m`
m
false
{"m":"`users2023`","o":"`orders2023`"}
true
true
false
true
false
