--TEST--
YZE_SQL set_db_type 数据库类型：mysql/sqlserver/oracle/dm 标识符引用符号
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

// 结构化 where 无公开构造入口，通过反射注入测试 _buildWhere
function inject_wheres($sql, $wheres){
    $rp = new ReflectionProperty('yangzie\YZE_SQL','where');
    $rp->setAccessible(true);
    $rp->setValue($sql, $wheres);
    return $sql;
}

// 1. mysql（默认，无需 set_db_type）反引号
$sql = YZE_SQL::new_SQL()->from(T_User::class,'u')->select('u',array('id','name'));
echo s($sql),"\n";

// 2. sql server 方括号
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_SQLSERVER)->from(T_User::class,'u')->select('u',array('id','name'));
echo s($sql),"\n";

// 3. oracle 双引号
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_ORACLE)->from(T_User::class,'u')->select('u',array('id','name'));
echo s($sql),"\n";

// 4. dm 达梦 双引号
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_DM)->from(T_User::class,'u')->select('u',array('id','name'));
echo s($sql),"\n";

// 5. sql server 完整子句：where/group_by/order_by/limit
$sql = inject_wheres(YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_SQLSERVER)->from(T_User::class,'u')
    ->order_by('u','id',YZE_SQL::DESC)
    ->group_by('u','role')
    ->limit(10),
    array(array('alias'=>'u','field'=>'id','op'=>YZE_SQL::GT,'value'=>10)));
echo s($sql),"\n";

// 6. sql server join 多表
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_SQLSERVER)->from(T_User::class,'u')
    ->join(T_Order::class,'o','o.user_id=u.id')
    ->select('u',array('id','name'))->select('o',array('user_id','amount'));
echo s($sql),"\n";

// 7. oracle 聚合 count/sum
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_ORACLE)->from(T_User::class,'u')
    ->count('u','*','cnt')->sum('u','price','total');
echo s($sql),"\n";

// 8. dm 更新语句
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_DM)->update('u',array('price'=>99.5))->from(T_User::class,'u');
echo s($sql),"\n";

// 9. sql server 删除语句（无别名）
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_SQLSERVER)->delete()->from(T_User::class);
echo s($sql),"\n";

// 10. 兼容别名写法 mssql
$sql = YZE_SQL::new_SQL()->set_db_type('mssql')->from(T_User::class,'u')->select('u',array('id'));
echo s($sql),"\n";

// 11. sql server 插入语句
$sql = YZE_SQL::new_SQL()->set_db_type(YZE_SQL::DB_TYPE_SQLSERVER)->insert('u',array('id'=>1,'name'=>null))->from(T_User::class,'u');
echo s($sql),"\n";
?>
--EXPECT--
SELECT `u`.`id` AS `u_id`,`u`.`name` AS `u_name` FROM `users` AS `u`
SELECT [u].[id] AS [u_id],[u].[name] AS [u_name] FROM [users] AS [u]
SELECT "u"."id" AS "u_id","u"."name" AS "u_name" FROM "users" AS "u"
SELECT "u"."id" AS "u_id","u"."name" AS "u_name" FROM "users" AS "u"
SELECT [u].[id] AS [u_id],[u].[name] AS [u_name],[u].[role] AS [u_role],[u].[price] AS [u_price],[u].[created_on] AS [u_created_on] FROM [users] AS [u] WHERE [u].[id] > 10 GROUP BY [u].[role] ORDER BY [u].[id] DESC LIMIT 10
SELECT [u].[id] AS [u_id],[u].[name] AS [u_name],[o].[user_id] AS [o_user_id],[o].[amount] AS [o_amount] FROM [users] AS [u] INNER JOIN [orders] AS [o] ON o.user_id=u.id
SELECT count( *) AS "u_cnt",sum("u"."price") AS "u_total" FROM "users" AS "u"
UPDATE "users" AS "u" 
SET "u"."price"=99.5
DELETE FROM [users]
SELECT [u].[id] AS [u_id] FROM [users] AS [u]
INSERT INTO [users] ([id],[name]) VALUES(1,null)
