--TEST--
yangzie/dba.php 分库上下文单元测试：在非默认库上查询出的 model 必须携带该库上下文，其 refresh/remove 仍作用于该库（同时覆盖分表后缀同步）
--SKIPIF--
<?php
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
try {
    include "init.php";
    $app = new \app\App_Module();
    $conns = array_keys($app->get_module_config('db_connections'));
    if (count($conns) < 2) {
        echo "skip 需要 __config__.php 中配置至少两个数据库连接\n";
        exit;
    }
    foreach ($conns as $name) {
        YZE_DBAImpl::get_instance($name)->native_Query("SELECT 1");
    }
} catch (\Throwable $e) {
    echo "skip 数据库不可用：".$e->getMessage()."\n";
}
?>
--FILE--
<?php
// ai@2026-09-21 分库上下文回归测试：
// 修复前 YZE_DBAImpl::select() 构建 model 时未写入 $this->db_name，
// 导致从非默认库查询出的 model 再次 save/remove/refresh（或自身链式查询）时会落到默认库。
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
use yangzie\YZE_SQL;
use yangzie\YZE_Model;
use yangzie\Column;

chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

class T_Db_Ctx extends YZE_Model {
    const TABLE = "tests_db_ctx";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";

    #[Column(type: 'int', nullable: false, length: 11)]
    private int $id;
    #[Column(type: 'string', nullable: true, length: 45)]
    private ?string $name;
}

function ok($cond, $msg){ echo ($cond ? "PASS" : "FAIL"), " - ", $msg, "\n"; }

$app         = new \app\App_Module();
$connections = $app->get_module_config('db_connections');
$defaultName = $app->get_module_config('default_db');

$dbA = YZE_DBAImpl::get_instance($defaultName);
$otherName = null;
foreach (array_keys($connections) as $name){
    if ($name !== $defaultName){ $otherName = $name; break; }
}
$dbB = YZE_DBAImpl::get_instance($otherName);

$ddl = "CREATE TABLE IF NOT EXISTS ".T_Db_Ctx::TABLE." (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

foreach (array($dbA, $dbB) as $db){
    $db->exec("DROP TABLE IF EXISTS ".T_Db_Ctx::TABLE);
    $db->exec("DROP TABLE IF EXISTS ".T_Db_Ctx::TABLE."_x");
    $db->exec($ddl);
}
// 两个库中同名表、同主键，但 name 不同，用于区分 model 到底落在哪个库
$dbA->exec("INSERT INTO ".T_Db_Ctx::TABLE." (id,name) VALUES (1,'a-lib')");
$dbB->exec("INSERT INTO ".T_Db_Ctx::TABLE." (id,name) VALUES (1,'b-lib')");
// 非默认库中的分表，用于验证分表后缀同步
$dbB->exec("CREATE TABLE ".T_Db_Ctx::TABLE."_x (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
$dbB->exec("INSERT INTO ".T_Db_Ctx::TABLE."_x (id,name) VALUES (1,'x')");

// 1. 在非默认库上查询
$sql   = YZE_SQL::new_SQL()->from(T_Db_Ctx::class, 't')->where("t.id = 1");
$model = $dbB->get_Single($sql);
ok($model instanceof T_Db_Ctx, "B 库查询返回 model 实例");
ok($model->get("name") === "b-lib", "查询结果来自 B 库");

// 2. model 必须携带查询所用的库上下文
$rp = new ReflectionProperty(YZE_Model::class, 'db');
$rp->setAccessible(true);
ok($rp->getValue($model) === $dbB->get_db_name(), "model 携带查询所用的库上下文");

// 3. refresh 必须回到 B 库读数（修复前会落到默认库，读到 a-lib）
$model->set("name", "dirty");
$model->refresh();
ok($model->get("name") === "b-lib", "refresh 从 B 库读取，未被默认库覆盖");

// 4. 分表后缀同样要同步到 model
$sql   = YZE_SQL::new_SQL()->from(T_Db_Ctx::class, 't', '_x')->where("t.id = 1");
$mx    = $dbB->get_Single($sql);
ok($mx->get_suffix() === "_x", "model 携带分表后缀 _x");
$mx->set("name", "dirty");
$mx->refresh();
ok($mx->get("name") === "x", "带分表后缀的 model refresh 命中分表");

// 5. remove 只能删除 B 库记录，不能误删默认库
$model->remove();
ok($dbB->lookup("id", T_Db_Ctx::TABLE, "id=:id", array(":id" => 1)) === null, "remove 只删除 B 库记录");
ok($dbA->lookup("id", T_Db_Ctx::TABLE, "id=:id", array(":id" => 1)) == 1, "A 库同名记录未被误删");

// 清理
foreach (array($dbA, $dbB) as $db){
    $db->exec("DROP TABLE IF EXISTS ".T_Db_Ctx::TABLE);
    $db->exec("DROP TABLE IF EXISTS ".T_Db_Ctx::TABLE."_x");
}
?>
--EXPECT--
PASS - B 库查询返回 model 实例
PASS - 查询结果来自 B 库
PASS - model 携带查询所用的库上下文
PASS - refresh 从 B 库读取，未被默认库覆盖
PASS - model 携带分表后缀 _x
PASS - 带分表后缀的 model refresh 命中分表
PASS - remove 只删除 B 库记录
PASS - A 库同名记录未被误删
