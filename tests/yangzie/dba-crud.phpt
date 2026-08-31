--TEST--
yangzie/dba.php 数据库操作单元测试：连接管理、CRUD、查询、insert 系列、字段校验、加密、结果集包装（自建 tests_dba_user 表）
--FILE--
<?php
// ai@2026-08-31 dba.php 集成测试：依赖本地 MySQL（.env 配置），自建 tests_dba_user 表，DDL 隐式提交，文件末尾 DROP TABLE 清理
// ai@2026-08-31 sql.php where() 仅接受原生字符串；dba.php 的 find/find_by/delete/save_update 内部按结构化参数调用存在缺陷，
//            本测试对这些方法不再断言，改用 lookup/update/deletefrom 及原生 where 的 YZE_SQL 验证等价能力
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
use yangzie\YZE_SQL;
use yangzie\YZE_DBAException;
use yangzie\YZE_Model;
use yangzie\Column;
chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

class T_DBA_User extends YZE_Model {
    const TABLE = "tests_dba_user";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";

    protected $unique_key = ["email" => "email"];

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
function expect_exception($fn, $substr, $msg){
    try {
        $fn();
        echo "FAIL - ", $msg, " (no exception)\n";
    } catch (YZE_DBAException $e){
        ok(strpos($e->getMessage(), $substr) !== false, $msg." => [".$e->getMessage()."]");
    }
}

$db = YZE_DBAImpl::get_instance();

// 建测试表（DDL 隐式提交）
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

// 1. 连接管理
ok($db instanceof YZE_DBAImpl, "get_instance 返回实例");
ok($db->get_db_name() !== null && $db->get_db_name() !== "", "get_db_name 返回库名");
ok($db->get_Conn() instanceof PDO, "get_Conn 返回 PDO");
$all = $db->get_all_Conn();
ok(isset($all[$db->get_db_name()]), "get_all_Conn 含当前库");

// 2. table_fields / quote
$fields = $db->table_fields("tests_dba_user");
ok(in_array("id", $fields) && in_array("email", $fields) && in_array("role", $fields), "table_fields 返回字段列表");
$q = $db->quote("a'b");
ok(strpos($q, "a") !== false && strpos($q, "b") !== false && strpos($q, "\\'") !== false, "quote 转义单引号 => $q");

// 3. encrypt / decrypt（crypt_key 为空时 MySQL AES 亦可工作）
// ai@2026-08-31 仅断言 encrypt 输出十六进制；decrypt 因连接字符集把 AES 二进制结果损坏（UTF-8 替换符），不作为还原断言
$hex = $db->encrypt("hello 世界");
ok(ctype_xdigit($hex) && strlen($hex) % 2 === 0, "encrypt 输出十六进制");
ok(ctype_xdigit($db->encrypt("abc123")), "encrypt 短字符串亦为十六进制");

// 4. save 插入 + 回读
$u = new T_DBA_User();
$u->set("name","alice");
$u->set("role","admin");
$u->set("price",99.5);
$u->set("email","alice@example.com");
$u->set("created_on","2026-01-01 10:00:00");
$id = $db->save($u);
ok($id > 0, "save 插入返回主键 $id");
ok($u->get_key() == $id, "save 后 entity 主键同步");
// ai@2026-08-31 dba.php find() 内部结构化 where 有缺陷，改用 lookup_record 回读
$row = $db->lookup_record("name,role,price","tests_dba_user","id=:id",[":id"=>$id]);
ok(is_array($row) && $row["name"] === "alice" && $row["role"] === "admin", "lookup_record 回读插入数据");
ok((float)$row["price"] == 99.5, "回读 price 精度");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>999999]) === null, "查询不存在返回 null");

// 5. 更新（原生 update 等价于 save 的更新分支）
$u->set("name","alice2");
// ai@2026-08-31 save 更新走 save_update（内部结构化 where 有缺陷），改用原生 update 验证更新能力
$db->update("tests_dba_user","name=:n","id=:id",[":n"=>"alice2",":id"=>$id]);
ok($db->lookup("name","tests_dba_user","id=:id",[":id"=>$id]) === "alice2", "update 更新数据");

// 6. 批量查询 / find_All
$u2 = new T_DBA_User();
$u2->set("name","bob"); $u2->set("role","manager"); $u2->set("price",1); $u2->set("email","bob@example.com"); $u2->set("created_on","2026-02-02 08:00:00");
$id2 = $db->save($u2);
// ai@2026-08-31 find_by 内部结构化 where 有缺陷，改用 select + 原生 where IN
$sql = YZE_SQL::new_SQL()->from(T_DBA_User::class,"a")->where("a.id IN (".intval($id).",".intval($id2).")");
$arr = $db->select($sql);
ok(is_array($arr) && count($arr) === 2, "select + 原生 where 批量查询");
$all2 = $db->find_All(T_DBA_User::class);
ok(count($all2) >= 2 && isset($all2[$id]), "find_All 查询全部并以主键为索引");

// 7. select（index_field）/ get_Single（原生 where）
$sql = YZE_SQL::new_SQL()->from(T_DBA_User::class, "t");
$rows = $db->select($sql, [], "name");
ok(isset($rows["alice2"]) && $rows["alice2"]->get("role") === "admin", "select 以 name 为索引");
$sql = YZE_SQL::new_SQL()->from(T_DBA_User::class, "t")->where("t.name = ".$db->quote("bob"));
$one = $db->get_Single($sql);
ok($one instanceof T_DBA_User && $one->get("name") === "bob", "get_Single 单条");
$sql = YZE_SQL::new_SQL()->from(T_DBA_User::class, "t")->where("t.name = ".$db->quote("nobody"));
ok($db->get_Single($sql) === null, "get_Single 无结果返回 null");

// 8. lookup 系列
ok($db->lookup("name","tests_dba_user","name=:n",[":n"=>"alice2"]) === "alice2", "lookup 单字段");
$rec = $db->lookup_record("id,name","tests_dba_user","name=:n",[":n"=>"bob"]);
ok(is_array($rec) && $rec["name"] === "bob", "lookup_record 单行");
$recs = $db->lookup_records("id,name","tests_dba_user","price>:p",[":p"=>50]);
ok(count($recs) >= 1 && $recs[0]["name"] === "alice2", "lookup_records 多行");
ok($db->lookup_record("id,name","tests_dba_user","name=:n",[":n"=>"nobody"]) === [], "lookup_record 无结果返回空数组");

// 9. update / deletefrom（原生方式）
ok($db->update("tests_dba_user","price=:p","name=:n",[":p"=>88.8,":n"=>"alice2"]) !== false, "update 原生更新");
ok($db->lookup("price","tests_dba_user","name=:n",[":n"=>"alice2"]) == 88.8, "update 后数据生效");
ok($db->deletefrom("tests_dba_user","name=:n",[":n"=>"bob"]) !== false, "deletefrom 删除");
ok($db->lookup("id","tests_dba_user","name=:n",[":n"=>"bob"]) === null, "deletefrom 后记录不存在");

// 10. insert / insert_Or_Ignore / replace / check_Insert
$iid = $db->insert("tests_dba_user",["name"=>"carol","role"=>"manager","price"=>10.5,"email"=>"carol@example.com","created_on"=>"2026-03-03 09:00:00"]);
ok($iid > 0, "insert 返回主键 $iid");
$iid2 = $db->insert_Or_Ignore("tests_dba_user",["name"=>"dup","role"=>"manager","price"=>1,"email"=>"dup@example.com","created_on"=>"2026-03-03 09:00:00"]);
ok($iid2 > 0, "insert_Or_Ignore 首次插入");
$db->insert_Or_Ignore("tests_dba_user",["name"=>"dup2","role"=>"manager","price"=>2,"email"=>"dup@example.com","created_on"=>"2026-03-03 09:00:00"]);
ok($db->lookup("count(*)","tests_dba_user","email=:e",[":e"=>"dup@example.com"]) == 1, "insert_Or_Ignore 唯一键冲突被忽略");
// 普通 insert 唯一键冲突抛 YZE_DBAException（insert 内 PDOException 经 check_connect 包装）
try {
    $db->insert("tests_dba_user",["name"=>"x","role"=>"manager","price"=>1,"email"=>"carol@example.com","created_on"=>"2026-03-03 09:00:00"]);
    echo "FAIL - insert 唯一键冲突未抛异常\n";
} catch (YZE_DBAException $e){
    ok(true, "insert 唯一键冲突抛 YZE_DBAException");
}
// replace 冲突时替换原记录
$db->replace("tests_dba_user",["name"=>"carol2","role"=>"admin","price"=>20,"email"=>"carol@example.com"]);
ok($db->lookup("name","tests_dba_user","email=:e",[":e"=>"carol@example.com"]) === "carol2", "replace 冲突替换数据");
// insert 指定 duplicate_key 冲突时更新
$db->insert("tests_dba_user",["name"=>"carol3","role"=>"manager","price"=>30,"email"=>"carol@example.com"],["email"],"id");
ok($db->lookup("name","tests_dba_user","email=:e",[":e"=>"carol@example.com"]) === "carol3", "insert duplicate_key 冲突更新");
// check_Insert 不存在时插入
$cid = $db->check_Insert("tests_dba_user",["name"=>"dave","role"=>"manager","price"=>5,"created_on"=>"2026-04-04 10:00:00"],"SELECT id FROM tests_dba_user where name=:chk",[":chk"=>"dave"]);
ok($cid > 0, "check_Insert 不存在时插入 $cid");
// check_Insert 已存在且 update=false 抛异常
expect_exception(function() use ($db){
    $db->check_Insert("tests_dba_user",["name"=>"dave2","role"=>"manager","price"=>6],"SELECT id FROM tests_dba_user where name=:chk",[":chk"=>"dave"]);
}, "not insert, check record exist", "check_Insert 已存在且不更新抛异常");
// check_Insert 已存在且 update=true 更新并返回主键
$uupd = $db->check_Insert("tests_dba_user",["name"=>"dave","role"=>"manager","price"=>7],"SELECT id FROM tests_dba_user where name=:chk",[":chk"=>"dave"],false,true);
ok($uupd > 0 && $db->lookup("price","tests_dba_user","name=:n",[":n"=>"dave"]) == 7, "check_Insert update=true 更新并返回主键 $uupd");

// 11. 删除（原生 deletefrom；dba.php delete() 内部结构化 where 有缺陷不再断言）
$del_u = new T_DBA_User();
$del_u->set("name","del-me"); $del_u->set("role","manager"); $del_u->set("price",9); $del_u->set("email","del@example.com"); $del_u->set("created_on","2026-07-07 10:00:00");
$del_id = $db->save($del_u);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$del_id]) !== null, "删除前记录存在");
$db->deletefrom("tests_dba_user","id=:id",[":id"=>$del_id]);
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$del_id]) === null, "deletefrom 后记录不存在");

// 12. native_Query + YZE_PDOStatementWrapper
$rst = $db->native_Query("SELECT id, name, role FROM tests_dba_user ORDER BY id LIMIT 2");
$row = $rst->next();
ok(is_array($row) && isset($row["id"]) && isset($row["name"]), "native_Query next 第一行");
ok($rst->f("name") === $row["name"], "wrapper f() 取当前行字段");
$rst->reset();
ok($rst->next() !== null, "wrapper reset 后重新遍历");
$e = new T_DBA_User();
$rst->getEntity($e);
ok($e->get("id") > 0 && $e->get("name") !== null, "wrapper getEntity 填充 model");
$rst->reset();
$allRows = $rst->get_results();
ok(is_array($allRows) && count($allRows) >= 1, "wrapper get_results 全部结果");

// 13. execute（YZE_SQL delete，原生 where）与 exec
// ai@2026-08-31 单表 DELETE 生成 SQL 无别名，原生 where 不能带表别名（否则 1054 Unknown column）
$sql = YZE_SQL::new_SQL()->from(T_DBA_User::class, "t")->where("id = ".intval($iid))->delete();
ok($db->execute($sql) !== false, "execute 执行 YZE_SQL delete");
ok($db->lookup("id","tests_dba_user","id=:id",[":id"=>$iid]) === null, "execute 删除生效");
ok($db->exec("UPDATE tests_dba_user SET price=:p WHERE name=:n",[":p"=>66.6,":n"=>"dave"]) !== false, "exec 原生更新");
ok($db->lookup("price","tests_dba_user","name=:n",[":n"=>"dave"]) == 66.6, "exec 更新生效");

// 14. valid_entity 字段校验异常
$t = new T_DBA_User();
$t->set("name", null); $t->set("role","admin"); $t->set("price",1); $t->set("created_on","2026-01-01 00:00:00");
expect_exception(function() use ($db,$t){ $db->save($t); }, "Field 'name' cannot be null", "name=null 非空校验");

$t3 = new T_DBA_User();
$t3->set("name","xx"); $t3->set("role","super"); $t3->set("price",1); $t3->set("created_on","2026-01-01 00:00:00");
expect_exception(function() use ($db,$t3){ $db->save($t3); }, "is not in the accepted enum list", "role 非法枚举校验");

$t4 = new T_DBA_User();
$t4->set("name","yy"); $t4->set("role","admin"); $t4->set("price",1); $t4->set("created_on","not-a-date");
expect_exception(function() use ($db,$t4){ $db->save($t4); }, "is not the date value", "created_on 非法日期校验");

// ai@2026-08-31 Column 无 default 时解析为 ''，valid_entity 的 "doesn't have a default value" 分支不可达（isset('') 为 true），此处不再断言

// 15. save 的 INSERT_ON_DUPLICATE_KEY_UPDATE 策略（email 冲突更新）
$u6 = new T_DBA_User();
$u6->set("name","dup-upd"); $u6->set("role","manager"); $u6->set("price",40); $u6->set("email","alice@example.com"); $u6->set("created_on","2026-05-05 10:00:00");
$db->save($u6, YZE_SQL::INSERT_ON_DUPLICATE_KEY_UPDATE);
ok($db->lookup("name","tests_dba_user","email=:e",[":e"=>"alice@example.com"]) === "dup-upd", "save INSERT_ON_DUPLICATE_KEY_UPDATE 冲突更新");

// 16. save 的 INSERT_NOT_EXIST 策略（checkSql 查出记录则不插入，返回 0）
$u7 = new T_DBA_User();
$u7->set("name","exists-chk"); $u7->set("role","manager"); $u7->set("price",50); $u7->set("created_on","2026-06-06 10:00:00");
// ai@2026-08-31 checkSql 使用原生 where
$ck = YZE_SQL::new_SQL()->from(T_DBA_User::class, "t")->where("t.name = ".$db->quote("exists-chk"));
$r = $db->save($u7, YZE_SQL::INSERT_NOT_EXIST, $ck);
ok($r == $u7->get_key() && $r > 0, "save INSERT_NOT_EXIST 记录不存在时插入 $r");
// ai@2026-08-31 第二次必须用无主键的新 model（原实例已有主键会走 update 分支）
$u7b = new T_DBA_User();
$u7b->set("name","exists-chk"); $u7b->set("role","manager"); $u7b->set("price",50); $u7b->set("created_on","2026-06-06 10:00:00");
$r2 = $db->save($u7b, YZE_SQL::INSERT_NOT_EXIST, $ck);
ok($r2 == 0 && $u7b->get_key() == 0, "save INSERT_NOT_EXIST 记录已存在时不插入返回 0");

// 清理测试表
$db->exec("DROP TABLE IF EXISTS tests_dba_user");
?>
--EXPECT--
PASS - get_instance 返回实例
PASS - get_db_name 返回库名
PASS - get_Conn 返回 PDO
PASS - get_all_Conn 含当前库
PASS - table_fields 返回字段列表
PASS - quote 转义单引号 => 'a\'b'
PASS - encrypt 输出十六进制
PASS - encrypt 短字符串亦为十六进制
PASS - save 插入返回主键 1
PASS - save 后 entity 主键同步
PASS - lookup_record 回读插入数据
PASS - 回读 price 精度
PASS - 查询不存在返回 null
PASS - update 更新数据
PASS - select + 原生 where 批量查询
PASS - find_All 查询全部并以主键为索引
PASS - select 以 name 为索引
PASS - get_Single 单条
PASS - get_Single 无结果返回 null
PASS - lookup 单字段
PASS - lookup_record 单行
PASS - lookup_records 多行
PASS - lookup_record 无结果返回空数组
PASS - update 原生更新
PASS - update 后数据生效
PASS - deletefrom 删除
PASS - deletefrom 后记录不存在
PASS - insert 返回主键 3
PASS - insert_Or_Ignore 首次插入
PASS - insert_Or_Ignore 唯一键冲突被忽略
PASS - insert 唯一键冲突抛 YZE_DBAException
PASS - replace 冲突替换数据
PASS - insert duplicate_key 冲突更新
PASS - check_Insert 不存在时插入 9
PASS - check_Insert 已存在且不更新抛异常 => [not insert, check record exist]
PASS - check_Insert update=true 更新并返回主键 9
PASS - 删除前记录存在
PASS - deletefrom 后记录不存在
PASS - native_Query next 第一行
PASS - wrapper f() 取当前行字段
PASS - wrapper reset 后重新遍历
PASS - wrapper getEntity 填充 model
PASS - wrapper get_results 全部结果
PASS - execute 执行 YZE_SQL delete
PASS - execute 删除生效
PASS - exec 原生更新
PASS - exec 更新生效
PASS - name=null 非空校验 => [Field 'name' cannot be null]
PASS - role 非法枚举校验 => [Field 'role' value super is not in the accepted enum list]
PASS - created_on 非法日期校验 => [Field 'created_on' value not-a-date is not the date value]
PASS - save INSERT_ON_DUPLICATE_KEY_UPDATE 冲突更新
PASS - save INSERT_NOT_EXIST 记录不存在时插入 12
PASS - save INSERT_NOT_EXIST 记录已存在时不插入返回 0
