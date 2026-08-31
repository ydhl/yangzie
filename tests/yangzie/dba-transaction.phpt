--TEST--
yangzie/dba.php 事务操作单元测试：begin_Transaction、commit、rollBack、auto_Commit、commit_all/rollBack_all（自建 tests_dba_user 表）
--FILE--
<?php
// ai@2026-08-31 dba.php 事务集成测试：依赖本地 MySQL；DDL 会隐式提交事务，故建表后显式 begin_Transaction 再操作；文件末尾 DROP TABLE 清理
// ai@2026-08-31 sql.php where() 仅接受原生字符串；dba.php 的 find()/delete() 内部结构化调用有缺陷，改用 lookup/deletefrom 验证事务效果
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
use yangzie\YZE_Model;
use yangzie\Column;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

class T_DBA_User extends YZE_Model {
    const TABLE = "tests_dba_user";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";

    #[Column(type: 'int', nullable: false, length: 11)]
    private int $id;
    #[Column(type: 'date', nullable: false, default: 'CURRENT_TIMESTAMP')]
    private string $created_on;
    #[Column(type: 'string', nullable: false, length: 45)]
    private string $name;
    #[Column(type: 'enum', nullable: false)]
    private string $role;
    #[Column(type: 'float', nullable: false, length: 10, default: '0.00')]
    private float $price;
    #[Column(type: 'string', nullable: true, length: 100)]
    private ?string $email;

    public function get_role(){ return ["manager","admin","warehouse"]; }
}

function ok($cond, $msg){ echo ($cond ? "PASS" : "FAIL"), " - ", $msg, "\n"; }

$db = YZE_DBAImpl::get_instance();
$db->exec("DROP TABLE IF EXISTS tests_dba_user");
$db->exec("CREATE TABLE tests_dba_user (
  id int(11) NOT NULL AUTO_INCREMENT,
  created_on datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  name varchar(45) NOT NULL,
  role enum('manager','admin','warehouse') NOT NULL,
  price decimal(10,2) NOT NULL DEFAULT '0.00',
  email varchar(100) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY email_UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

// 1. begin_Transaction 开启事务
$db->begin_Transaction();
ok($db->in_transaction() === true, "begin_Transaction 后处于事务中");

// 2. 事务内插入可见，rollBack 后撤销
$u = new T_DBA_User();
$u->set("name","tx1"); $u->set("role","admin"); $u->set("price",1); $u->set("created_on","2026-01-01 00:00:00");
$db->save($u);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u->get_key()]) !== null, "事务内插入后可见");
$db->rollBack();
ok($db->in_transaction() === false, "rollBack 后不在事务中");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u->get_key()]) === null, "rollBack 撤销插入");

// 3. 插入后 commit，数据保留
$db->begin_Transaction();
$u2 = new T_DBA_User();
$u2->set("name","tx2"); $u2->set("role","admin"); $u2->set("price",2); $u2->set("created_on","2026-01-01 00:00:00");
$db->save($u2);
$db->commit();
ok($db->in_transaction() === false, "commit 后不在事务中");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u2->get_key()]) !== null, "commit 保留数据");

// 4. 事务内删除 + rollBack 撤销删除
$db->begin_Transaction();
$db->deletefrom("tests_dba_user","id=:id",[":id"=>$u2->get_key()]);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u2->get_key()]) === null, "事务内删除生效");
$db->rollBack();
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u2->get_key()]) !== null, "rollBack 撤销删除");

// 5. auto_Commit(true) 后插入立即生效
$db->auto_Commit(true);
$u3 = new T_DBA_User();
$u3->set("name","tx3"); $u3->set("role","manager"); $u3->set("price",3); $u3->set("created_on","2026-01-01 00:00:00");
$db->save($u3);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u3->get_key()]) !== null, "auto_Commit(true) 插入即时生效");
// auto_Commit(false) 关闭自动提交
$db->auto_Commit(false);

// 6. commit_all 提交所有连接的事务
$db->begin_Transaction();
$u4 = new T_DBA_User();
$u4->set("name","tx4"); $u4->set("role","manager"); $u4->set("price",4); $u4->set("created_on","2026-01-01 00:00:00");
$db->save($u4);
YZE_DBAImpl::commit_all();
ok($db->in_transaction() === false, "commit_all 提交所有连接事务");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u4->get_key()]) !== null, "commit_all 后数据保留");

// 7. rollBack_all 回滚所有连接的事务
$db->begin_Transaction();
$u5 = new T_DBA_User();
$u5->set("name","tx5"); $u5->set("role","manager"); $u5->set("price",5); $u5->set("created_on","2026-01-01 00:00:00");
$db->save($u5);
YZE_DBAImpl::rollBack_all();
ok($db->in_transaction() === false, "rollBack_all 回滚所有连接事务");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u5->get_key()]) === null, "rollBack_all 撤销插入");

// 清理测试表
$db->exec("DROP TABLE IF EXISTS tests_dba_user");
?>
--EXPECT--
PASS - begin_Transaction 后处于事务中
PASS - 事务内插入后可见
PASS - rollBack 后不在事务中
PASS - rollBack 撤销插入
PASS - commit 后不在事务中
PASS - commit 保留数据
PASS - 事务内删除生效
PASS - rollBack 撤销删除
PASS - auto_Commit(true) 插入即时生效
PASS - commit_all 提交所有连接事务
PASS - commit_all 后数据保留
PASS - rollBack_all 回滚所有连接事务
PASS - rollBack_all 撤销插入
