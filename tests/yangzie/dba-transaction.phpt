--TEST--
yangzie/dba.php 事务操作单元测试：begin_Transaction、commit、rollBack、auto_Commit、commit_all/rollBack_all（复用 test 模块的 tests_dba_user 表与 model）
--FILE--
<?php
// ai@2026-08-31 dba.php 事务集成测试：依赖本地 MySQL；DDL 会隐式提交事务，故建表后显式 begin_Transaction 再操作
// ai@2026-09-21 测试库/表/model 统一由 test 模块提供（Tests_Db 检查库表是否存在，不存在则创建；model 见 app/modules/test/models）
// ai@2026-08-31 sql.php where() 仅接受原生字符串；dba.php 的 find()/delete() 内部结构化调用有缺陷，改用 lookup/deletefrom 验证事务效果
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
use app\modules\test\Tests_Db;
use app\test\Tests_Dba_User_Model;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

function ok($cond, $msg){ echo ($cond ? "PASS" : "FAIL"), " - ", $msg, "\n"; }

// 检查测试库、测试表是否存在，不存在则创建，并清空数据（DDL 隐式提交）
Tests_Db::reset();

$db = YZE_DBAImpl::get_instance();

// 1. begin_Transaction 开启事务
$db->begin_Transaction();
ok($db->in_transaction() === true, "begin_Transaction 后处于事务中");

// 2. 事务内插入可见，rollBack 后撤销
$u = new Tests_Dba_User_Model();
$u->set("name","tx1"); $u->set("role","admin"); $u->set("price",1); $u->set("created_on","2026-01-01 00:00:00");
$db->save($u);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u->get_key()]) !== null, "事务内插入后可见");
$db->rollBack();
ok($db->in_transaction() === false, "rollBack 后不在事务中");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u->get_key()]) === null, "rollBack 撤销插入");

// 3. 插入后 commit，数据保留
$db->begin_Transaction();
$u2 = new Tests_Dba_User_Model();
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
$u3 = new Tests_Dba_User_Model();
$u3->set("name","tx3"); $u3->set("role","manager"); $u3->set("price",3); $u3->set("created_on","2026-01-01 00:00:00");
$db->save($u3);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u3->get_key()]) !== null, "auto_Commit(true) 插入即时生效");
// auto_Commit(false) 关闭自动提交
$db->auto_Commit(false);

// 6. commit_all 提交所有连接的事务
$db->begin_Transaction();
$u4 = new Tests_Dba_User_Model();
$u4->set("name","tx4"); $u4->set("role","manager"); $u4->set("price",4); $u4->set("created_on","2026-01-01 00:00:00");
$db->save($u4);
YZE_DBAImpl::commit_all();
ok($db->in_transaction() === false, "commit_all 提交所有连接事务");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u4->get_key()]) !== null, "commit_all 后数据保留");

// 7. rollBack_all 回滚所有连接的事务
$db->begin_Transaction();
$u5 = new Tests_Dba_User_Model();
$u5->set("name","tx5"); $u5->set("role","manager"); $u5->set("price",5); $u5->set("created_on","2026-01-01 00:00:00");
$db->save($u5);
YZE_DBAImpl::rollBack_all();
ok($db->in_transaction() === false, "rollBack_all 回滚所有连接事务");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$u5->get_key()]) === null, "rollBack_all 撤销插入");

// ai@2026-09-21 测试表由 Tests_Db 统一管理，测试内不再 DROP
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
