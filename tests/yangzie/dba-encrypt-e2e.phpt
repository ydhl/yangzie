--TEST--
yangzie/dba.php 字段加密端到端：Column(encrypt: true) 字段保存时自动加密、读取时自动解密
--SKIPIF--
<?php
// ai@2026-09-21 跳过条件：与 dba-crud.phpt 一致，本地 MySQL 可达且 .env 中 default_db 已配置
ini_set("display_errors",0);
$root = dirname(dirname(dirname(__FILE__)));
if (!file_exists($root."/app/public_html/init.php")) die("skip init.php not found");

$loaded = false;
ob_start();
chdir($root."/app/public_html");
try {
    include_once "init.php";
    $loaded = true;
} catch (\Throwable $e) {
    $loaded = false;
}
while (ob_get_level()) ob_end_clean();
if (!$loaded) die("skip init.php load failed");

try {
    $app_module = new \app\App_Module();
    $db_name = $app_module->get_module_config("default_db");
    $conn = $app_module->get_module_config("db_connections")[$db_name] ?? null;
    if (!$conn || !$conn["db_host"]) die("skip db connection not configured");
    if (!mysqli_connect($conn["db_host"], $conn["db_user"], $conn["db_psw"], "", $conn["db_port"])) die("skip db not connectable");
} catch (\Throwable $e) {
    die("skip ".$e->getMessage());
}
?>
--FILE--
<?php
// ai@2026-09-21 字段加密端到端测试：
//   1) 注解识别：is_encrypt_column() 与 get_columns() 返回的 encrypt 标记一致
//   2) save 加密：set/__set 写入明文，DB 中加密列落密文（hex 且不等于明文）
//   3) load 解密：select / get_Single / find_by_id 构造的 model 持有明文
//   4) 不二次加密：多次 save_update 不会产生密文嵌套
//   5) NULL 加密字段往返：secret_note = null 在 DB 与 model 中均为 null
//   6) 非加密字段对照：name 列保持明文存储
//   7) 旁路一致性：lookup_record 直接读出的加密列仍是密文
//   8) __set/__set 与 set 行为一致：两条赋值路径在 save/load 后语义相同
ini_set("display_errors",0);
use yangzie\YZE_DBAImpl;
use yangzie\YZE_SQL;
use app\modules\test\Tests_Db;
use app\test\Yze_Encrypt_E2e_Model;

chdir(dirname(dirname(dirname(__FILE__)))."/app/public_html");
include "init.php";

function ok($cond, $msg){ echo ($cond ? "PASS" : "FAIL"), " - ", $msg, "\n"; }

Tests_Db::reset();
$db = YZE_DBAImpl::get_instance();
$db_name = $db->get_db_name();

// ============================================================
// 1) Column 注解识别
// ============================================================
$m = new Yze_Encrypt_E2e_Model();
$cols = $m->get_columns();
ok(isset($cols["password"]["encrypt"]) && $cols["password"]["encrypt"] === true, "password 列识别为加密字段");
ok(isset($cols["token"]["encrypt"]) && $cols["token"]["encrypt"] === true, "token 列识别为加密字段");
ok(isset($cols["secret_note"]["encrypt"]) && $cols["secret_note"]["encrypt"] === true, "secret_note 列识别为加密字段");
ok(!($cols["name"]["encrypt"] ?? false), "name 列不是加密字段");

ok($m->is_encrypt_column("password") === true, "is_encrypt_column('password') = true");
ok($m->is_encrypt_column("name") === false, "is_encrypt_column('name') = false");
ok($m->is_encrypt_column("__no_such__") === false, "is_encrypt_column 未知字段 = false");

// ============================================================
// 2) save 后 DB 中加密列是密文、非加密列是明文
// ============================================================
$plain_password = "P@ssw0rd-明文-2026";
$plain_token    = "token-abcdef-0123456789";
$plain_note     = "这是私密备注，包含中英文 Mixed abc 123";

$m->set("name", "alice");
$m->set("password", $plain_password);
$m->set("token", $plain_token);
$m->set("secret_note", $plain_note);
$id = $db->save($m);
ok($id > 0, "save 插入返回主键 $id");
ok($m->get_key() == $id, "save 后 entity 主键同步");

// ai@2026-09-21 用 PDO 直接拉底层 raw row，绕开所有解密逻辑，验证存储形态
$pdo = $db->get_Conn($db_name);
$stmt = $pdo->prepare("SELECT name, password, token, secret_note FROM yze_encrypt_e2e WHERE id = :id");
$stmt->execute([":id" => $id]);
$raw = $stmt->fetch(\PDO::FETCH_ASSOC);

ok($raw["name"] === "alice", "DB 中 name 是明文");
ok($raw["password"] !== $plain_password, "DB 中 password != 明文");
ok($raw["token"] !== $plain_token, "DB 中 token != 明文");
ok($raw["secret_note"] !== $plain_note, "DB 中 secret_note != 明文");
ok(ctype_xdigit($raw["password"]) && strlen($raw["password"]) % 2 === 0, "DB 中 password 是偶数长度 hex");
ok(ctype_xdigit($raw["token"]) && strlen($raw["token"]) % 2 === 0, "DB 中 token 是偶数长度 hex");
ok(ctype_xdigit($raw["secret_note"]) && strlen($raw["secret_note"]) % 2 === 0, "DB 中 secret_note 是偶数长度 hex");

// ai@2026-09-21 lookup_record 直接读数据库行（不解密），加密列应仍是密文
$row = $db->lookup_record("password,token,name", "yze_encrypt_e2e", "id=:id", [":id" => $id]);
ok($row["password"] === $raw["password"], "lookup_record 读到 DB 原始密文（password）");
ok($row["token"] === $raw["token"], "lookup_record 读到 DB 原始密文（token）");
ok($row["name"] === "alice", "lookup_record 读到明文 name");

// ai@2026-09-21 直接通过 DBA 解密 hex，应还原明文（证明 DB 存的是真密文，不是任意 hex）
ok($db->decrypt($raw["password"]) === $plain_password, "DBA decrypt(password hex) 还原明文");
ok($db->decrypt($raw["token"]) === $plain_token, "DBA decrypt(token hex) 还原明文");
ok($db->decrypt($raw["secret_note"]) === $plain_note, "DBA decrypt(secret_note hex) 还原明文");

// ============================================================
// 3) select / get_Single / find_by_id 构造的 model 自动持有明文
// ============================================================
$sql = YZE_SQL::new_SQL()->from(Yze_Encrypt_E2e_Model::class, "t")->where("t.id = ".$db->quote($id));
$loaded = $db->get_Single($sql);
ok($loaded instanceof Yze_Encrypt_E2e_Model, "get_Single 返回 model");
ok($loaded->get("name") === "alice", "get_Single 后 name = alice");
ok($loaded->get("password") === $plain_password, "get_Single 后 records['password'] = 明文");
ok($loaded->get("token") === $plain_token, "get_Single 后 records['token'] = 明文");
ok($loaded->get("secret_note") === $plain_note, "get_Single 后 records['secret_note'] = 明文");
ok($loaded->password === $plain_password, "__get('password') = 明文");
ok($loaded->token === $plain_token, "__get('token') = 明文");

$found = Yze_Encrypt_E2e_Model::find_by_id($id);
ok($found instanceof Yze_Encrypt_E2e_Model, "find_by_id 返回 model");
ok($found->get("password") === $plain_password, "find_by_id 后 records['password'] = 明文");
ok($found->name === "alice", "find_by_id 后 __get('name') = 明文");

$sql2 = YZE_SQL::new_SQL()->from(Yze_Encrypt_E2e_Model::class, "t");
$rows = $db->select($sql2);
ok(count($rows) >= 1, "select 返回至少 1 行");
$row0 = array_values($rows)[0];
ok($row0->get("password") === $plain_password, "select 后 records['password'] = 明文");
ok($row0->get("secret_note") === $plain_note, "select 后 records['secret_note'] = 明文");

// ============================================================
// 4) update 不会产生双层加密
// ============================================================
$loaded->set("password", "new-password-9999");
$db->save($loaded);
$raw2 = $pdo->prepare("SELECT password FROM yze_encrypt_e2e WHERE id = :id");
$raw2->execute([":id" => $id]);
$pw_raw = $raw2->fetchColumn();
ok($pw_raw !== "new-password-9999", "update 后 DB 仍是密文");
ok(ctype_xdigit($pw_raw) && strlen($pw_raw) % 2 === 0, "update 后 DB password 是单层 hex 密文");
ok($db->decrypt($pw_raw) === "new-password-9999", "update 后单层 decrypt 即可还原明文（无双层加密）");

// ai@2026-09-21 进一步：连续两次 update，验证不会嵌套
$loaded->set("password", "new-password-third");
$db->save($loaded);
$raw3 = $pdo->prepare("SELECT password FROM yze_encrypt_e2e WHERE id = :id");
$raw3->execute([":id" => $id]);
$pw_raw3 = $raw3->fetchColumn();
ok($db->decrypt($pw_raw3) === "new-password-third", "第二次 update 后单层 decrypt 还原明文（无嵌套）");

// ============================================================
// 5) NULL 加密字段往返
// ============================================================
$m2 = new Yze_Encrypt_E2e_Model();
$m2->set("name", "bob");
$m2->set("password", "bob-pwd");
// ai@2026-09-21 token / secret_note 不设置（依赖框架默认空字符串或 NULL；nullable 字段未显式赋值时取 NULL）
$id2 = $db->save($m2);
$raw4 = $pdo->prepare("SELECT token, secret_note FROM yze_encrypt_e2e WHERE id = :id");
$raw4->execute([":id" => $id2]);
$nullable_row = $raw4->fetch(\PDO::FETCH_ASSOC);
ok($nullable_row["token"] === null, "未赋值的 nullable 加密列在 DB 中为 NULL");
ok($nullable_row["secret_note"] === null, "未赋值的 nullable 加密列 secret_note 在 DB 中为 NULL");

// ai@2026-09-21 显式置 NULL 也应保持 NULL（dba.php decrypt(null) 返回 null，不会报错）
$m2->set("token", null);
$m2->set("secret_note", null);
$db->save($m2);
$raw5 = $pdo->prepare("SELECT token, secret_note FROM yze_encrypt_e2e WHERE id = :id");
$raw5->execute([":id" => $id2]);
$nullable_row2 = $raw5->fetch(\PDO::FETCH_ASSOC);
ok($nullable_row2["token"] === null, "显式置 NULL 的 token 仍是 NULL");
ok($nullable_row2["secret_note"] === null, "显式置 NULL 的 secret_note 仍是 NULL");

$m2b = Yze_Encrypt_E2e_Model::find_by_id($id2);
ok($m2b->get("token") === null, "回读后 records['token'] = null");
ok($m2b->get("secret_note") === null, "回读后 records['secret_note'] = null");
ok($m2b->token === null, "回读后 __get('token') = null");
ok($m2b->secret_note === null, "回读后 __get('secret_note') = null");

// ============================================================
// 6) __set 与 set 行为一致：两条路径都正确
// ============================================================
$m3 = new Yze_Encrypt_E2e_Model();
$m3->name = "carol";
$m3->password = "carol-secret";
$m3->token = "carol-tok";
$m3->secret_note = "carol-note";
$id3 = $db->save($m3);
ok($id3 > 0, "__set 路径下 save 成功");

$raw6 = $pdo->prepare("SELECT password, token, secret_note FROM yze_encrypt_e2e WHERE id = :id");
$raw6->execute([":id" => $id3]);
$carol = $raw6->fetch(\PDO::FETCH_ASSOC);
ok($carol["password"] !== "carol-secret", "__set 路径下 DB 中 password 是密文");
ok($carol["token"] !== "carol-tok", "__set 路径下 DB 中 token 是密文");
ok(ctype_xdigit($carol["password"]) && $db->decrypt($carol["password"]) === "carol-secret", "__set 路径下 password 单层 decrypt 还原（无双层加密）");

$m3b = Yze_Encrypt_E2e_Model::find_by_id($id3);
ok($m3b->password === "carol-secret", "__set 路径下回读 __get('password') = 明文");
ok($m3b->get("token") === "carol-tok", "__set 路径下回读 records['token'] = 明文");
ok($m3b->secret_note === "carol-note", "__set 路径下回读 __get('secret_note') = 明文");

// ============================================================
// 7) 非加密字段对照：name 应一直保持明文，且不影响加密字段
// ============================================================
$m4 = new Yze_Encrypt_E2e_Model();
$m4->set("name", "dave");
$m4->set("password", "dave-pwd");
$id4 = $db->save($m4);
$raw7 = $pdo->prepare("SELECT name, password FROM yze_encrypt_e2e WHERE id = :id");
$raw7->execute([":id" => $id4]);
$dave = $raw7->fetch(\PDO::FETCH_ASSOC);
ok($dave["name"] === "dave", "name 列始终是明文存储");
ok($dave["password"] !== "dave-pwd", "password 列是密文存储");
ok($db->decrypt($dave["password"]) === "dave-pwd", "password 单层 decrypt 还原明文");

echo "ALL DONE\n";
?>
--EXPECT--
PASS - password 列识别为加密字段
PASS - token 列识别为加密字段
PASS - secret_note 列识别为加密字段
PASS - name 列不是加密字段
PASS - is_encrypt_column('password') = true
PASS - is_encrypt_column('name') = false
PASS - is_encrypt_column 未知字段 = false
PASS - save 插入返回主键 1
PASS - save 后 entity 主键同步
PASS - DB 中 name 是明文
PASS - DB 中 password != 明文
PASS - DB 中 token != 明文
PASS - DB 中 secret_note != 明文
PASS - DB 中 password 是偶数长度 hex
PASS - DB 中 token 是偶数长度 hex
PASS - DB 中 secret_note 是偶数长度 hex
PASS - lookup_record 读到 DB 原始密文（password）
PASS - lookup_record 读到 DB 原始密文（token）
PASS - lookup_record 读到明文 name
PASS - DBA decrypt(password hex) 还原明文
PASS - DBA decrypt(token hex) 还原明文
PASS - DBA decrypt(secret_note hex) 还原明文
PASS - get_Single 返回 model
PASS - get_Single 后 name = alice
PASS - get_Single 后 records['password'] = 明文
PASS - get_Single 后 records['token'] = 明文
PASS - get_Single 后 records['secret_note'] = 明文
PASS - __get('password') = 明文
PASS - __get('token') = 明文
PASS - find_by_id 返回 model
PASS - find_by_id 后 records['password'] = 明文
PASS - find_by_id 后 __get('name') = 明文
PASS - select 返回至少 1 行
PASS - select 后 records['password'] = 明文
PASS - select 后 records['secret_note'] = 明文
PASS - update 后 DB 仍是密文
PASS - update 后 DB password 是单层 hex 密文
PASS - update 后单层 decrypt 即可还原明文（无双层加密）
PASS - 第二次 update 后单层 decrypt 还原明文（无嵌套）
PASS - 未赋值的 nullable 加密列在 DB 中为 NULL
PASS - 未赋值的 nullable 加密列 secret_note 在 DB 中为 NULL
PASS - 显式置 NULL 的 token 仍是 NULL
PASS - 显式置 NULL 的 secret_note 仍是 NULL
PASS - 回读后 records['token'] = null
PASS - 回读后 records['secret_note'] = null
PASS - 回读后 __get('token') = null
PASS - 回读后 __get('secret_note') = null
PASS - __set 路径下 save 成功
PASS - __set 路径下 DB 中 password 是密文
PASS - __set 路径下 DB 中 token 是密文
PASS - __set 路径下 password 单层 decrypt 还原（无双层加密）
PASS - __set 路径下回读 __get('password') = 明文
PASS - __set 路径下回读 records['token'] = 明文
PASS - __set 路径下回读 __get('secret_note') = 明文
PASS - name 列始终是明文存储
PASS - password 列是密文存储
PASS - password 单层 decrypt 还原明文
ALL DONE