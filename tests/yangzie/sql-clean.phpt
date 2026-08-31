--TEST--
YZE_SQL 清理方法：clean_where/clean/clean_groupby/clean_limit/clean_select
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

// ai@2026-08-28 结构化 where 通过反射注入
function inject_wheres($sql, $wheres){
    $rp = new ReflectionProperty('yangzie\YZE_SQL','where');
    $rp->setAccessible(true);
    $rp->setValue($sql, $wheres);
    return $sql;
}

// 1. clean_where 清空全部（原生 where）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where("m.name='admin'")->clean_where();
echo s($sql),"\n";

// 2. clean_where 按 alias 清理（结构化）
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1),
        array('alias'=>'o','field'=>'id','op'=>YZE_SQL::GT,'value'=>1),
    ));
$sql->clean_where('o');
echo s($sql),"\n";

// 3. clean_where 按 alias+column 清理
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1),
        array('alias'=>'m','field'=>'name','op'=>YZE_SQL::NE,'value'=>2),
    ));
$sql->clean_where('m','name');
echo s($sql),"\n";

// 4. clean_groupby
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('role'))
    ->group_by('m','role')->clean_groupby();
echo s($sql),"\n";

// 5. clean_limit
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->limit(10)->clean_limit();
echo s($sql),"\n";

// 6. clean_select 后回到展开全部列（聚合、select 均清空）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->count('m','*','c')->clean_select();
echo s($sql),"\n";

// 7. clean 全清（from 也被清空，仅剩 SELECT * FROM）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')
    ->select('m',array('id'))->where("m.name='admin'")
    ->order_by('m','id','desc')->limit(10)->group_by('m','role');
$sql->clean();
echo s($sql),"\n";

// 8. clean 后 from 同样被清空
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'));
$sql->clean();
echo s($sql),"\n";

// 9. 同一对象链式复用：构造->执行->清理->重新构造
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->where("m.id=1");
echo s($sql),"\n";
$sql->clean_where()->where("m.id=2");
echo s($sql),"\n";
$sql->clean()->from(T_Order::class,'o')->select('o',array('amount'))->where("o.amount>100");
echo s($sql),"\n";
?>
--EXPECT--
SELECT `m`.`id` AS `m_id` FROM `users` AS `m`
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1
SELECT `m`.`role` AS `m_role` FROM `users` AS `m`
SELECT `m`.`id` AS `m_id` FROM `users` AS `m`
SELECT `m`.`id` AS `m_id`,`m`.`name` AS `m_name`,`m`.`role` AS `m_role`,`m`.`price` AS `m_price`,`m`.`created_on` AS `m_created_on` FROM `users` AS `m`
SELECT * FROM 
SELECT * FROM 
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.id=1
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.id=2
SELECT `o`.`amount` AS `o_amount` FROM `orders` AS `o` WHERE o.amount>100
