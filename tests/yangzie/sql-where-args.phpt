--TEST--
YZE_SQL where 多参（结构化）调用（含 andor 连接符）与 dba/model 层入口的 SQL 生成：save 更新分支、delete、find、find_by、update_by_id、insert_Or_Update 的 checkSql
--FILE--
<?php
// ai@2026-09-14 补 where 第 5 参 andor 的覆盖（or 连接、大小写/非法值规范化、首条件、与 and 混合顺序）
// ai@2026-09-13 覆盖 dba.php 的 find/find_by/delete/save_update 与 model.php 的 update_by_id/insert_Or_Update
//            对 where($alias,$field,$op,$value) 的结构化调用：用 YZE_DBAImpl 子类桩子捕获 execute/select/get_Single
//            收到的 SQL 做字符串断言；条件值只用数值，避免 _quoteValue 触发 quote 去连接数据库
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include dirname(__FILE__)."/sql_test_models.php";

use yangzie\YZE_SQL;
use yangzie\YZE_DBAImpl;
use yangzie\T_User;
use yangzie\T_Order;

function s($sql){ return str_replace("\r\n","\n",(string)$sql); }

// 桩子：重写构造避免连库，只记录收到的 SQL
class T_DBA_Spy extends YZE_DBAImpl {
    public $logs = array();
    public function __construct($db_name=null){}
    public function execute(YZE_SQL $sql, $params=array()){ $this->logs[] = s($sql); return 1; }
    public function exec($sql, $params=array()){ $this->logs[] = $sql; return 1; }
    public function select(YZE_SQL $sql, $params=array(), $index_field=null){ $this->logs[] = s($sql); return array(); }
    public function get_Single(YZE_SQL $sql, $params=array()){ $this->logs[] = s($sql); return null; }
    public function encrypt($value){ return $value; }
}

$spy = new T_DBA_Spy();
function last_log($spy){ return $spy->logs[count($spy->logs)-1]; }

// 1. 结构化 EQ
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::EQ,1)),"\n";

// 2. 多条件默认 and 连接
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::EQ,1)->where('m','id',YZE_SQL::GT,10)),"\n";

// 2b. 第 5 参 andor 传 or：与上一个条件按 or 连接
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::EQ,1)->where('m','price',YZE_SQL::EQ,9.9,'or')),"\n";

// 2c. andor 大小写不敏感；非 or 的非法值一律回退 and（不会进入语句）
foreach (array('OR','Or','xor','') as $andor){
    echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
        ->where('m','id',YZE_SQL::EQ,1)->where('m','id',YZE_SQL::GT,10,$andor)),"\n";
}

// 2d. 首个条件不使用 andor，不输出连接符
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::EQ,1,'or')),"\n";

// 2e. or 与 and 混合：按调用顺序平铺，框架不加括号，分组需由调用方自行保证
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::EQ,1)->where('m','price',YZE_SQL::EQ,9.9,'or')
    ->where('m','id',YZE_SQL::GT,10)),"\n";

// 3. 比较操作符
foreach (array('NE'=>2,'LT'=>10,'GEQ'=>10,'LEQ'=>10) as $op => $v){
    echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
        ->where('m','id',constant('yangzie\YZE_SQL::'.$op),$v)),"\n";
}

// 4. ISNULL / ISNOTNULL
foreach (array('ISNULL','ISNOTNULL') as $op){
    echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
        ->where('m','role',constant('yangzie\YZE_SQL::'.$op),null)),"\n";
}

// 5. IN / NOTIN 数值数组
foreach (array('IN','NOTIN') as $op){
    echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
        ->where('m','id',constant('yangzie\YZE_SQL::'.$op),array(1,2,3))),"\n";
}

// 6. BETWEEN 数值数组
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::BETWEEN,array(1,5))),"\n";

// 7. 子查询作条件值
$sub = YZE_SQL::new_SQL()->from(T_Order::class,'o')->select('o',array('user_id'))->where("o.amount>100");
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where('m','id',YZE_SQL::IN,$sub)),"\n";

// 8. 原生条件与结构化条件混用（原生串需自带连接符）
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))
    ->where("m.role='admin' and ")->where('m','id',YZE_SQL::EQ,2)),"\n";

// 9. update + 结构化 where
echo s(YZE_SQL::new_SQL()->from(T_User::class,'t')->update('t',array('price'=>99.5))
    ->where('t','id',YZE_SQL::EQ,5)),"\n";

// 10. delete + 结构化 where（delete 语句列名不带别名）
echo s(YZE_SQL::new_SQL()->from(T_User::class,'t')->where('t','id',YZE_SQL::EQ,7)->delete()),"\n";

// 11. save 更新分支（主键已有值 => save_update）
$u = new T_User();
$u->set("id", 5);
$u->set("price", 99.5);
$spy->save($u);
echo last_log($spy),"\n";

// 12. delete(entity)
$spy->delete($u);
echo last_log($spy),"\n";

// 13. find
$spy->find(5, T_User::class);
echo last_log($spy),"\n";

// 14. find_by
$spy->find_by(array(1,2), T_User::class, null);
echo last_log($spy),"\n";

// 15. update_by_id 的等价构建（YZE_DBAImpl::get_instance 为 new static，桩子无法接管该静态入口）
$sql = YZE_SQL::new_SQL()->update('t', array('price'=>8.8))->from(T_User::class,'t');
$sql->where('t', T_User::KEY_NAME, YZE_SQL::IN, array(1,2));
echo s($sql),"\n";

// 16. insert_Or_Update 的 checkSql 等价构建（model.php insert_Or_Update 内部构建方式）
$check = YZE_SQL::new_SQL()->from(T_User::class, "__mine__")->where("__mine__","price",YZE_SQL::EQ,9.9);
echo s($check),"\n";
?>
--EXPECT--
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 and `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 or `m`.`price` = 9.9
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 or `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 or `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 and `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 and `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` = 1 or `m`.`price` = 9.9 and `m`.`id` > 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` != 2
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` < 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` >= 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` <= 10
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`role` IS NULL
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`role` IS NOT NULL
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` IN (1,2,3)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` NOT IN (1,2,3)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` BETWEEN 1 AND 5
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE `m`.`id` IN (SELECT `o`.`user_id` AS `o_user_id` FROM `orders` AS `o` WHERE o.amount>100)
SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.role='admin' and `m`.`id` = 2
UPDATE `users` AS `t` 
SET `t`.`price`=99.5 
WHERE `t`.`id` = 5
DELETE FROM `users` 
WHERE `id` = 7
UPDATE `users` AS `t` 
SET `t`.`id`=5,`t`.`price`=99.5 
WHERE `t`.`id` = 5
DELETE FROM `users` 
WHERE `id` = 5
SELECT `a`.`id` AS `a_id`,`a`.`name` AS `a_name`,`a`.`role` AS `a_role`,`a`.`price` AS `a_price`,`a`.`created_on` AS `a_created_on` FROM `users` AS `a` WHERE `a`.`id` = 5 LIMIT 1
SELECT `a`.`id` AS `a_id`,`a`.`name` AS `a_name`,`a`.`role` AS `a_role`,`a`.`price` AS `a_price`,`a`.`created_on` AS `a_created_on` FROM `users` AS `a` WHERE `a`.`id` IN (1,2)
UPDATE `users` AS `t` 
SET `t`.`price`=8.8 
WHERE `t`.`id` IN (1,2)
SELECT `__mine__`.`id` AS `__mine___id`,`__mine__`.`name` AS `__mine___name`,`__mine__`.`role` AS `__mine___role`,`__mine__`.`price` AS `__mine___price`,`__mine__`.`created_on` AS `__mine___created_on` FROM `users` AS `__mine__` WHERE `__mine__`.`price` = 9.9
