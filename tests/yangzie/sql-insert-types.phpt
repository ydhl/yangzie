--TEST--
YZE_SQL insert 七种 insert_type（NORMAL/EXIST/NOT_EXIST/NOT_EXIST_OR_UPDATE/ON_DUPLICATE_KEY_UPDATE/REPLACE/IGNORE）：语句生成、YZE_DBAImpl::save 分支行为、真实 MySQL 端到端
--FILE--
<?php
// ai@2026-09-13 覆盖 sql.php 的 7 种 insert_type：YZE_SQL::insert 的语句生成、
//            YZE_DBAImpl::save 的分支行为（桩子，不连库）、真实 MySQL 端到端执行
// 第 1 层只使用 null/数值，避免 _quoteValue 调 quote() 触发连库
ini_set("display_errors",0);
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";
include dirname(__FILE__)."/sql_test_models.php";

use yangzie\YZE_SQL;
use yangzie\YZE_DBAImpl;
use yangzie\YZE_Model;
use yangzie\Column;
use yangzie\T_User;

class T_Insert_User extends YZE_Model {
    const TABLE = "tests_insert_types";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";

    protected $unique_key = array("email" => "email");

    #[Column(type: 'int', nullable: false, length: 11)]
    private int $id;
    #[Column(type: 'string', nullable: false, length: 45)]
    private string $name;
    #[Column(type: 'float', nullable: false, length: 10, default: '0.00')]
    private float $price;
    #[Column(type: 'string', nullable: true, length: 100)]
    private ?string $email;
    #[Column(type: 'date', nullable: false, default: 'CURRENT_TIMESTAMP')]
    private string $created_on;
}

function s($sql){ return str_replace("\r\n","\n",(string)$sql); }
function one_line($sql){ return preg_replace('/\s+/', ' ', s($sql)); }
function ok($cond,$msg){ echo ($cond?"PASS":"FAIL")," - ",$msg,"\n"; }

// ============ 1. YZE_SQL::insert 生成的 SQL（7 种 insert_type） ============
echo "--- 1. YZE_SQL insert_type 语句生成 ---\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null))),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'price'=>9.9), YZE_SQL::INSERT_NORMAL)),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE)),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null,'price'=>9.9), YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE, array('id','name'))),"\n";
// ai@2026-09-14 边界：唯一键字段包含全部插入字段时 update 列表为空，语句不应出现尾随逗号
$emptyUpdate = s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1), YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE, array('id')));
echo $emptyUpdate,"\n";
ok(substr($emptyUpdate, -1) !== "," && strpos($emptyUpdate, "ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`)") !== false, "ON DUPLICATE KEY UPDATE update 列表为空时不输出尾随逗号");
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null,'price'=>9.9), YZE_SQL::INSERT_ON_DUPLICATE_KEY_REPLACE)),"\n";
$ckExist = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->where("m.id=1");
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_EXIST, $ckExist)),"\n";
// ai@2026-09-13 未传 check_sql 时按 KEY_NAME 反查、使用自身的 where 条件
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->where("m.name='admin'")->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_EXIST)),"\n";
$ckNotExist = YZE_SQL::new_SQL()->from(T_User::class,'m')->select('m',array('id'))->where("m.name='admin'");
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST, $ckNotExist)),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->where("m.name='admin'")->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST)),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE, $ckNotExist)),"\n";
echo s(YZE_SQL::new_SQL()->from(T_User::class,'m')->where("m.name='admin'")->insert('m', array('id'=>1,'name'=>null), YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE)),"\n";

// ============ 2. YZE_DBAImpl::save 各 insert_type 的分支行为（桩子，不连库） ============
echo "--- 2. YZE_DBAImpl::save 分支 ---\n";

class T_Fake_PDO {
    public $insert_id = 42;
    public function lastInsertId($name=null){ return $this->insert_id; }
}

class T_Insert_Spy extends YZE_DBAImpl {
    public $logs = array();
    public $rowCount = 1;
    public $single = null;
    public function __construct($db_name=null){}
    public function execute(YZE_SQL $sql, $params=[]){ $this->logs[] = s($sql); return $this->rowCount; }
    public function select(YZE_SQL $sql, $params=array(), $index_field=null){ $this->logs[] = s($sql); return array(); }
    public function get_Single(YZE_SQL $sql, $params=array()){ $this->logs[] = s($sql); return $this->single; }
    public function quote($value){ return "'".$value."'"; }
    public function encrypt($value){ return $value; }
}

// save() 内部通过 self::$conn[$this->db_name] 取连接，private static 属性用反射注入 fake PDO
$connProp = new ReflectionProperty('yangzie\YZE_DBAImpl', 'conn');
$connProp->setAccessible(true);
$origConn = $connProp->getValue();
$fakePdo = new T_Fake_PDO();
$connProp->setValue(null, array('' => $fakePdo));

$spy = new T_Insert_Spy();
function last_log(T_Insert_Spy $spy){ return one_line($spy->logs[count($spy->logs)-1]); }

// 2.1 INSERT_NORMAL：返回 lastInsertId 并写回 model
$u1 = new T_User(); $u1->set('name','n1');
$r1 = $spy->save($u1);
ok($r1 === 42 && $u1->get_key() === 42, "save INSERT_NORMAL 返回 42 并写回主键");
ok(strpos(last_log($spy), "INSERT INTO `users` (`name`) VALUES('n1')") === 0, "save INSERT_NORMAL SQL => ".last_log($spy));

// 2.2 INSERT_EXIST：rowCount=1 命中，插入成功
$spy->rowCount = 1;
$u2 = new T_User(); $u2->set('name','n2');
$ck2 = YZE_SQL::new_SQL()->from(T_User::class,'t')->where("t.name='n2'");
$r2 = $spy->save($u2, YZE_SQL::INSERT_EXIST, $ck2);
ok($r2 === 42 && $u2->get_key() === 42, "save INSERT_EXIST 命中时返回 42");
ok(strpos(last_log($spy), "WHERE EXISTS (SELECT") !== false, "save INSERT_EXIST SQL => ".last_log($spy));

// 2.3 INSERT_EXIST：rowCount=0 未命中，不插入返回 0
$spy->rowCount = 0;
$u3 = new T_User(); $u3->set('name','n3');
$ck3 = YZE_SQL::new_SQL()->from(T_User::class,'t')->where("t.name='n3'");
$r3 = $spy->save($u3, YZE_SQL::INSERT_EXIST, $ck3);
ok($r3 === 0 && $u3->get_key() === 0, "save INSERT_EXIST 未命中时返回 0 且主键置 0");

// 2.4 INSERT_NOT_EXIST：rowCount=1 未命中，插入成功
$spy->rowCount = 1;
$u4 = new T_User(); $u4->set('name','n4');
$ck4 = YZE_SQL::new_SQL()->from(T_User::class,'t')->where("t.name='n4'");
$r4 = $spy->save($u4, YZE_SQL::INSERT_NOT_EXIST, $ck4);
ok($r4 === 42, "save INSERT_NOT_EXIST 未命中时返回 42");
ok(strpos(last_log($spy), "WHERE NOT EXISTS (SELECT") !== false, "save INSERT_NOT_EXIST SQL => ".last_log($spy));

// 2.5 INSERT_NOT_EXIST：rowCount=0 命中，不插入返回 0
$spy->rowCount = 0;
$u5 = new T_User(); $u5->set('name','n5');
$ck5 = YZE_SQL::new_SQL()->from(T_User::class,'t')->where("t.name='n5'");
$r5 = $spy->save($u5, YZE_SQL::INSERT_NOT_EXIST, $ck5);
ok($r5 === 0 && $u5->get_key() === 0, "save INSERT_NOT_EXIST 命中时返回 0 且主键置 0");

// 2.6 INSERT_NOT_EXIST_OR_UPDATE：rowCount=0 未插入，回查已有记录并更新
$spy->rowCount = 0;
$exists = new T_User(); $exists->set('id', 7);
$spy->single = $exists;
$u6 = new T_User(); $u6->set('name','n6');
$ck6 = YZE_SQL::new_SQL()->from(T_User::class,'t')->where("t.name='n6'");
$r6 = $spy->save($u6, YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE, $ck6);
ok($r6 === 7 && $u6->get_key() === 7, "save INSERT_NOT_EXIST_OR_UPDATE 命中时返回已有主键 7");
ok(strpos(last_log($spy), "UPDATE `users` AS `t`") === 0, "save INSERT_NOT_EXIST_OR_UPDATE 命中后执行 update => ".last_log($spy));

// 2.7 INSERT_ON_DUPLICATE_KEY_IGNORE：恒返回 0
$spy->rowCount = 1;
$u7 = new T_User(); $u7->set('name','n7');
$r7 = $spy->save($u7, YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE);
ok($r7 === 0 && $u7->get_key() === 0, "save INSERT_ON_DUPLICATE_KEY_IGNORE 恒返回 0");
ok(strpos(last_log($spy), "INSERT IGNORE INTO `users`") === 0, "save INSERT_ON_DUPLICATE_KEY_IGNORE SQL => ".last_log($spy));

// 2.8 INSERT_ON_DUPLICATE_KEY_UPDATE：返回 lastInsertId
$spy->rowCount = 1;
$u8 = new T_User(); $u8->set('name','n8');
$r8 = $spy->save($u8, YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE);
ok($r8 === 42, "save INSERT_ON_DUPLICATE_KEY_UPDATE 返回 42");
ok(strpos(last_log($spy), "ON DUPLICATE KEY UPDATE") !== false, "save INSERT_ON_DUPLICATE_KEY_UPDATE SQL => ".last_log($spy));

// 2.9 INSERT_ON_DUPLICATE_KEY_REPLACE：返回 lastInsertId
$spy->rowCount = 1;
$u9 = new T_User(); $u9->set('name','n9');
$r9 = $spy->save($u9, YZE_SQL::INSERT_ON_DUPLICATE_KEY_REPLACE);
ok($r9 === 42, "save INSERT_ON_DUPLICATE_KEY_REPLACE 返回 42");
ok(strpos(last_log($spy), "REPLACE INTO `users` SET `name`='n9'") === 0, "save INSERT_ON_DUPLICATE_KEY_REPLACE SQL => ".last_log($spy));

// 2.10 已有主键：走 save_update 分支
$u10 = new T_User(); $u10->set('id', 5); $u10->set('name','n10');
$r10 = $spy->save($u10);
ok($r10 === 5, "save 已有主键走 update 分支返回自身主键 5");
ok(strpos(last_log($spy), "UPDATE `users` AS `t`") === 0, "save 已有主键 SQL => ".last_log($spy));

// 恢复连接静态属性，交给下面的端到端用例
$connProp->setValue(null, $origConn ?: array());

// ============ 3. 端到端：真实 MySQL 验证 7 种策略（自建 tests_insert_types 表） ============
echo "--- 3. 端到端（真实 MySQL） ---\n";
$db = YZE_DBAImpl::get_instance();
$db->exec("DROP TABLE IF EXISTS tests_insert_types");
$db->exec("CREATE TABLE tests_insert_types (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(45) NOT NULL,
  price decimal(10,2) NOT NULL DEFAULT '0.00',
  email varchar(100) DEFAULT NULL,
  created_on datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email_UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

function new_user($name, $email, $price=1){
    $u = new T_Insert_User();
    $u->set('name', $name); $u->set('price', $price); $u->set('email', $email);
    $u->set('created_on', '2026-01-01 00:00:00');
    return $u;
}
function count_email($db, $email){ return $db->lookup("count(*)", "tests_insert_types", "email=:e", array(":e"=>$email)); }
function name_of($db, $email){ return $db->lookup("name", "tests_insert_types", "email=:e", array(":e"=>$email)); }

// 3.1 INSERT_NORMAL
$u = new_user('normal', 'normal@x.com');
$id = $db->save($u);
ok($id > 0 && $u->get_key() == $id, "端到端 INSERT_NORMAL 插入返回主键 $id");
ok(name_of($db,'normal@x.com') === 'normal', "端到端 INSERT_NORMAL 记录已写入");

// 3.2 INSERT_ON_DUPLICATE_KEY_UPDATE：email 冲突时更新
$u2 = new_user('dup-updated', 'normal@x.com');
$id2 = $db->save($u2, YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE);
ok($id2 > 0, "端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 返回主键 $id2");
ok(name_of($db,'normal@x.com') === 'dup-updated', "端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 冲突更新 name");
ok(count_email($db,'normal@x.com') == 1, "端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 未新增行");

// 3.3 INSERT_ON_DUPLICATE_KEY_IGNORE：email 冲突时忽略
$u3 = new_user('ignored', 'normal@x.com');
$r3 = $db->save($u3, YZE_SQL::INSERT_ON_DUPLICATE_KEY_IGNORE);
ok($r3 === 0 && $u3->get_key() == 0, "端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 冲突返回 0");
ok(name_of($db,'normal@x.com') === 'dup-updated', "端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 未修改原记录");
ok(count_email($db,'normal@x.com') == 1, "端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 未新增行");

// 3.4 INSERT_ON_DUPLICATE_KEY_REPLACE：email 冲突时替换
$u4 = new_user('replaced', 'normal@x.com');
$r4 = $db->save($u4, YZE_SQL::INSERT_ON_DUPLICATE_KEY_REPLACE);
ok($r4 > 0, "端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 返回新主键 $r4");
ok(name_of($db,'normal@x.com') === 'replaced', "端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 记录被替换");
ok(count_email($db,'normal@x.com') == 1, "端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 仍只有一行");

// 3.5 INSERT_EXIST：checkSql 命中才插入
$db->save(new_user('exists-target', 'exists@x.com'));
$ckExist = YZE_SQL::new_SQL()->from(T_Insert_User::class,'t')->where("t.email = ".$db->quote('exists@x.com'));
$u5 = new_user('created-by-exist', 'exist-new@x.com');
$r5 = $db->save($u5, YZE_SQL::INSERT_EXIST, $ckExist);
ok($r5 > 0 && $u5->get_key() == $r5, "端到端 INSERT_EXIST 命中时插入返回主键 $r5");
ok(count_email($db,'exist-new@x.com') == 1, "端到端 INSERT_EXIST 命中时记录已写入");

$ckExist2 = YZE_SQL::new_SQL()->from(T_Insert_User::class,'t')->where("t.email = ".$db->quote('nobody@x.com'));
$u6 = new_user('not-created', 'exist-none@x.com');
$r6 = $db->save($u6, YZE_SQL::INSERT_EXIST, $ckExist2);
ok($r6 === 0 && $u6->get_key() == 0, "端到端 INSERT_EXIST 未命中时返回 0");
ok(count_email($db,'exist-none@x.com') == 0, "端到端 INSERT_EXIST 未命中时未写入");

// 3.6 INSERT_NOT_EXIST：checkSql 未命中才插入
$ckNotExist = YZE_SQL::new_SQL()->from(T_Insert_User::class,'t')->where("t.email = ".$db->quote('not-exist@x.com'));
$u7 = new_user('inserted-by-not-exist', 'not-exist@x.com');
$r7 = $db->save($u7, YZE_SQL::INSERT_NOT_EXIST, $ckNotExist);
ok($r7 > 0 && $u7->get_key() == $r7, "端到端 INSERT_NOT_EXIST 未命中时插入返回主键 $r7");

$u8 = new_user('should-not-insert', 'not-exist@x.com');
$r8 = $db->save($u8, YZE_SQL::INSERT_NOT_EXIST, $ckNotExist);
ok($r8 === 0 && $u8->get_key() == 0, "端到端 INSERT_NOT_EXIST 命中时返回 0");
ok(name_of($db,'not-exist@x.com') === 'inserted-by-not-exist', "端到端 INSERT_NOT_EXIST 命中时未修改记录");

// 3.7 INSERT_NOT_EXIST_OR_UPDATE：未命中插入，命中更新
$ckUpsert = YZE_SQL::new_SQL()->from(T_Insert_User::class,'t')->where("t.email = ".$db->quote('upsert@x.com'));
$u9 = new_user('upsert-first', 'upsert@x.com');
$r9 = $db->save($u9, YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE, $ckUpsert);
ok($r9 > 0 && $u9->get_key() == $r9, "端到端 INSERT_NOT_EXIST_OR_UPDATE 未命中时插入返回主键 $r9");

$ckUpsert2 = YZE_SQL::new_SQL()->from(T_Insert_User::class,'t')->where("t.email = ".$db->quote('upsert@x.com'));
$u10 = new_user('upsert-second', 'upsert@x.com');
$r10 = $db->save($u10, YZE_SQL::INSERT_NOT_EXIST_OR_UPDATE, $ckUpsert2);
ok($r10 == $r9, "端到端 INSERT_NOT_EXIST_OR_UPDATE 命中时返回已有主键 $r10");
ok(name_of($db,'upsert@x.com') === 'upsert-second', "端到端 INSERT_NOT_EXIST_OR_UPDATE 命中时更新记录");
ok(count_email($db,'upsert@x.com') == 1, "端到端 INSERT_NOT_EXIST_OR_UPDATE 未新增行");

$db->exec("DROP TABLE IF EXISTS tests_insert_types");
?>
--EXPECT--
--- 1. YZE_SQL insert_type 语句生成 ---
INSERT INTO `users` (`id`,`name`) VALUES(1,null)
INSERT INTO `users` (`id`,`price`) VALUES(1,9.9)
INSERT IGNORE INTO `users` (`id`,`name`) VALUES(1,null)
INSERT INTO `users` (`id`,`name`,`price`) VALUES(1,null,9.9)  ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `price`=VALUES(`price`)
INSERT INTO `users` (`id`) VALUES(1)  ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`)
PASS - ON DUPLICATE KEY UPDATE update 列表为空时不输出尾随逗号
REPLACE INTO `users` SET `id`=1,`name`=null,`price`=9.9
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE EXISTS (SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.id=1)
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE EXISTS (SELECT `id` FROM `users` WHERE m.name='admin')
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.name='admin')
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `id` FROM `users` WHERE m.name='admin')
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `m`.`id` AS `m_id` FROM `users` AS `m` WHERE m.name='admin')
INSERT INTO `users` (`id`,`name`) SELECT 1,null FROM dual WHERE NOT EXISTS (SELECT `id` FROM `users` WHERE m.name='admin')
--- 2. YZE_DBAImpl::save 分支 ---
PASS - save INSERT_NORMAL 返回 42 并写回主键
PASS - save INSERT_NORMAL SQL => INSERT INTO `users` (`name`) VALUES('n1')
PASS - save INSERT_EXIST 命中时返回 42
PASS - save INSERT_EXIST SQL => INSERT INTO `users` (`name`) SELECT 'n2' FROM dual WHERE EXISTS (SELECT `t`.`id` AS `t_id`,`t`.`name` AS `t_name`,`t`.`role` AS `t_role`,`t`.`price` AS `t_price`,`t`.`created_on` AS `t_created_on` FROM `users` AS `t` WHERE t.name='n2')
PASS - save INSERT_EXIST 未命中时返回 0 且主键置 0
PASS - save INSERT_NOT_EXIST 未命中时返回 42
PASS - save INSERT_NOT_EXIST SQL => INSERT INTO `users` (`name`) SELECT 'n4' FROM dual WHERE NOT EXISTS (SELECT `t`.`id` AS `t_id`,`t`.`name` AS `t_name`,`t`.`role` AS `t_role`,`t`.`price` AS `t_price`,`t`.`created_on` AS `t_created_on` FROM `users` AS `t` WHERE t.name='n4')
PASS - save INSERT_NOT_EXIST 命中时返回 0 且主键置 0
PASS - save INSERT_NOT_EXIST_OR_UPDATE 命中时返回已有主键 7
PASS - save INSERT_NOT_EXIST_OR_UPDATE 命中后执行 update => UPDATE `users` AS `t` SET `t`.`name`='n6' WHERE t.name='n6'
PASS - save INSERT_ON_DUPLICATE_KEY_IGNORE 恒返回 0
PASS - save INSERT_ON_DUPLICATE_KEY_IGNORE SQL => INSERT IGNORE INTO `users` (`name`) VALUES('n7')
PASS - save INSERT_ON_DUPLICATE_KEY_UPDATE 返回 42
PASS - save INSERT_ON_DUPLICATE_KEY_UPDATE SQL => INSERT INTO `users` (`name`) VALUES('n8') ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `name`=VALUES(`name`)
PASS - save INSERT_ON_DUPLICATE_KEY_REPLACE 返回 42
PASS - save INSERT_ON_DUPLICATE_KEY_REPLACE SQL => REPLACE INTO `users` SET `name`='n9'
PASS - save 已有主键走 update 分支返回自身主键 5
PASS - save 已有主键 SQL => UPDATE `users` AS `t` SET `t`.`id`=5,`t`.`name`='n10' WHERE `t`.`id` = 5
--- 3. 端到端（真实 MySQL） ---
PASS - 端到端 INSERT_NORMAL 插入返回主键 1
PASS - 端到端 INSERT_NORMAL 记录已写入
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 返回主键 1
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 冲突更新 name
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_UPDATE 未新增行
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 冲突返回 0
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 未修改原记录
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_IGNORE 未新增行
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 返回新主键 4
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 记录被替换
PASS - 端到端 INSERT_ON_DUPLICATE_KEY_REPLACE 仍只有一行
PASS - 端到端 INSERT_EXIST 命中时插入返回主键 6
PASS - 端到端 INSERT_EXIST 命中时记录已写入
PASS - 端到端 INSERT_EXIST 未命中时返回 0
PASS - 端到端 INSERT_EXIST 未命中时未写入
PASS - 端到端 INSERT_NOT_EXIST 未命中时插入返回主键 7
PASS - 端到端 INSERT_NOT_EXIST 命中时返回 0
PASS - 端到端 INSERT_NOT_EXIST 命中时未修改记录
PASS - 端到端 INSERT_NOT_EXIST_OR_UPDATE 未命中时插入返回主键 8
PASS - 端到端 INSERT_NOT_EXIST_OR_UPDATE 命中时返回已有主键 8
PASS - 端到端 INSERT_NOT_EXIST_OR_UPDATE 命中时更新记录
PASS - 端到端 INSERT_NOT_EXIST_OR_UPDATE 未新增行
