--TEST--
YZE_SQL where 条件构建：原生 where、结构化条件各操作符、子查询、delete 反引号列
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

// ai@2026-08-28 结构化 where 无公开构造入口（where() 仅收原生串），通过反射注入测试 _buildWhere
function inject_wheres($sql, $wheres){
    $rp = new ReflectionProperty('yangzie\YZE_SQL','where');
    $rp->setAccessible(true);
    $rp->setValue($sql, $wheres);
    return $sql;
}

// 1. 原生 where 单条件
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->where("m.name='admin'");
echo s($sql),"\n";

// 2. 原生 where 多条件（and/or 由调用方书写，框架直接拼接）
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where("m.name='admin'")->where(" and m.id>10")->where(" or m.role='admin'");
echo s($sql),"\n";

// 3. 原生 where 内嵌子查询字符串
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where("m.id in (select o.user_id from orders o where o.amount>100)");
echo s($sql),"\n";

// 4. 结构化 EQ（数值不触发数据库 quote）
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1)));
echo s($sql),"\n";

// 5. 结构化 比较操作符
foreach (array('GT'=>10,'LT'=>10,'GEQ'=>10,'LEQ'=>10,'NE'=>2) as $op => $v){
    $sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
        array(array('alias'=>'m','field'=>'id','op'=>constant('yangzie\YZE_SQL::'.$op),'value'=>$v)));
    echo s($sql),"\n";
}

// 6. ISNULL / ISNOTNULL
foreach (array('ISNULL','ISNOTNULL') as $op){
    $sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
        array(array('alias'=>'m','field'=>'role','op'=>constant('yangzie\YZE_SQL::'.$op),'value'=>null)));
    echo s($sql),"\n";
}

// 7. IN 数值数组
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'id','op'=>YZE_SQL::IN,'value'=>array(1,2,3))));
echo s($sql),"\n";

// 8. NOT IN 数值数组
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'id','op'=>YZE_SQL::NOTIN,'value'=>array(1,2,3))));
echo s($sql),"\n";

// 9. BETWEEN 数值数组
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'id','op'=>YZE_SQL::BETWEEN,'value'=>array(1,5))));
echo s($sql),"\n";

// 10. is_column 列比较
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'role','op'=>YZE_SQL::EQ,'value'=>'name','is_column'=>true)));
echo s($sql),"\n";

// 11. field_func 函数包裹 + is_column
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(array('alias'=>'m','field'=>'created_on','op'=>YZE_SQL::EQ,'value'=>'created_on','is_column'=>true,'field_func'=>'date')));
echo s($sql),"\n";

// 12. 多条件 and 连接
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1),
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::GT,'value'=>10,'andor'=>'and'),
    ));
echo s($sql),"\n";

// 13. 多条件 or 连接
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
    array(
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1),
        array('alias'=>'m','field'=>'id','op'=>YZE_SQL::GT,'value'=>10,'andor'=>'or'),
    ));
echo s($sql),"\n";

// 14-17. 子查询值
$sub = YZE_SQL::new_SQL()->from(T_Order::class,'o')->select('o',array('user_id'))->where("o.amount>100");
foreach (array('IN','NOTIN','EQ','BETWEEN') as $op){
    $sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id')),
        array(array('alias'=>'m','field'=>'id','op'=>constant('yangzie\YZE_SQL::'.$op),'value'=>$sub)));
    echo s($sql),"\n";
}

// 18. delete 时结构化条件列名加反引号（无别名）
$sql = inject_wheres(YZE_SQL::new_SQL()->from(T_User::class,'m'), array(array('alias'=>'m','field'=>'id','op'=>YZE_SQL::EQ,'value'=>1)))->delete();
echo s($sql),"\n";

// 19. delete 原生 where
$sql = YZE_SQL::new_SQL()->from(T_User::class,'m')->where("id=7")->delete();
echo s($sql),"\n";
?>
--EXPECT--
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.name='admin'
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.name='admin' and m.id>10 or m.role='admin'
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.id in (select o.user_id from orders o where o.amount>100)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` < 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` >= 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` <= 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` != 2
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`role` IS NULL
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`role` IS NOT NULL
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` IN (1,2,3)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` NOT IN (1,2,3)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` BETWEEN 1 AND 5
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`role` = `name`
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE date( `m`.`created_on` ) = `created_on`
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 and `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 or `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` IN (SELECT `o`.`user_id` AS `o_user_id` FROM `orders` AS `o` WHERE o.amount>100)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` NOT IN (SELECT `o`.`user_id` AS `o_user_id` FROM `orders` AS `o` WHERE o.amount>100)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = (SELECT `o`.`user_id` AS `o_user_id` FROM `orders` AS `o` WHERE o.amount>100)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` IS NOT NULL
DELETE FROM `users` 
WHERE `id` = 1
DELETE FROM `users` 
WHERE id=7
