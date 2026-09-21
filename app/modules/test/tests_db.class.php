<?php
namespace app\modules\test;

use app\App_Module;
use yangzie\YZE_DBAException;
use yangzie\YZE_DBAImpl;

/**
 * 单元测试数据库/测试表统一管理
 *
 * ai@2026-09-21 涉及数据库的单元测试不再各自临时建库、建表、建 model，
 * 这里集中定义测试库与测试表结构，测试开始前调用 ensure()/reset()
 * 检查库、表是否存在，不存在则创建，保证测试可在任意环境重复运行。
 *
 * 类文件按框架"其他类文件"规则存放（命名空间与路径一致、文件名 类名.class.php）：
 * app/modules/test/tests_db.class.php  =>  namespace app\modules\test  =>  class Tests_Db
 * 因此可直接通过自动加载使用，无需在测试里手动 include。
 *
 * 使用示例：
 *   Tests_Db::reset();                 // 默认库中的所有测试表：建库建表并清空
 *   Tests_Db::reset(array('test1','test2')); // 指定多个库
 *   Tests_Db::ensure();                // 只保证库表存在，不清空数据
 */
class Tests_Db {

    /** 测试表：dba CRUD/事务测试 */
    const TABLE_DBA_USER = 'tests_dba_user';
    /** 测试表：insert 七种策略端到端测试 */
    const TABLE_INSERT_TYPES = 'tests_insert_types';
    /** 测试表：分库上下文测试 */
    const TABLE_DB_CTX = 'tests_db_ctx';
    /** 测试表：分库上下文测试的分表（后缀表） */
    const TABLE_DB_CTX_X = 'tests_db_ctx_x';
    /** 测试表：CLI 加密字段生成测试 */
    const TABLE_ENCRYPT = 'yze_encrypt_test';
    /** 测试表：dba.php 字段加密端到端测试 */
    const TABLE_ENCRYPT_E2E = 'yze_encrypt_e2e';

    /**
     * 所有测试表结构，key 为表名，value 为建表语句
     *
     * @return array
     */
    public static function tables(): array {
        return array(
            self::TABLE_DBA_USER => "CREATE TABLE IF NOT EXISTS `".self::TABLE_DBA_USER."` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(45) NOT NULL,
  `role` enum('manager','admin','warehouse') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            self::TABLE_INSERT_TYPES => "CREATE TABLE IF NOT EXISTS `".self::TABLE_INSERT_TYPES."` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `email` varchar(100) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            self::TABLE_DB_CTX => "CREATE TABLE IF NOT EXISTS `".self::TABLE_DB_CTX."` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            self::TABLE_DB_CTX_X => "CREATE TABLE IF NOT EXISTS `".self::TABLE_DB_CTX_X."` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            self::TABLE_ENCRYPT => "CREATE TABLE IF NOT EXISTS `".self::TABLE_ENCRYPT."` (
             `id` int NOT NULL AUTO_INCREMENT,
             `password` varchar(45) NOT NULL DEFAULT '',
             `memo` varchar(100) DEFAULT NULL,
             `other` varchar(45) DEFAULT NULL,
             PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
                        // ai@2026-09-21 dba.php 字段加密端到端测试：password/token/secret_note 为加密字段，长度按 AES_ENCRYPT+bin2hex 输出预留
                        self::TABLE_ENCRYPT_E2E => "CREATE TABLE IF NOT EXISTS `".self::TABLE_ENCRYPT_E2E."` (
             `id` int(11) NOT NULL AUTO_INCREMENT,
             `name` varchar(45) NOT NULL DEFAULT '',
             `password` varchar(255) NOT NULL DEFAULT '',
             `token` varchar(255) DEFAULT NULL,
             `secret_note` varchar(512) DEFAULT NULL,
             PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
                    );
                }

    /**
     * 配置中所有的数据库连接（key 为库名）
     *
     * @return array
     */
    public static function connections(): array {
        $app_module = new App_Module();
        return (array) $app_module->get_module_config('db_connections');
    }

    /**
     * 默认数据库名
     *
     * @return string
     */
    public static function default_db_name(): string {
        $app_module = new App_Module();
        return (string) $app_module->get_module_config('default_db');
    }

    /**
     * 建立测试库与其中的测试表，已存在则跳过
     *
     * @param array|null $db_names 需要检查的库名；null 表示配置中所有可用的连接
     * @return void
     */
    public static function ensure(array $db_names = null): void {
        foreach (self::resolve_db_names($db_names) as $db_name) {
            $connection = self::connection_config($db_name);
            if ( ! self::database_exists($db_name)) {
                self::create_database($db_name, $connection);
            }
            foreach (self::tables() as $table => $ddl) {
                if (self::table_exists($table, $db_name)) {
                    continue;
                }
                YZE_DBAImpl::get_instance($db_name)->exec($ddl);
            }
        }
    }

    /**
     * 清空测试表（同时重置自增），让每个数据库测试都从空表开始
     *
     * @param array|null $db_names 需要清空的库名；null 表示配置中所有可用的连接
     * @return void
     */
    public static function truncate(array $db_names = null): void {
        foreach (self::resolve_db_names($db_names) as $db_name) {
            $db = YZE_DBAImpl::get_instance($db_name);
            foreach (array_keys(self::tables()) as $table) {
                self::assert_identifier($table);
                $db->exec("TRUNCATE TABLE `{$table}`");
            }
        }
    }

    /**
     * 保证测试库、测试表存在，并清空数据；数据库测试开始时调用
     *
     * @param array|null $db_names 库名；null 表示配置中所有可用的连接
     * @return void
     */
    public static function reset(array $db_names = null): void {
        self::ensure($db_names);
        self::truncate($db_names);
    }

    /**
     * 数据库是否存在
     *
     * @param string $db_name
     * @return bool
     */
    public static function database_exists(string $db_name): bool {
        self::assert_identifier($db_name);
        $statement = self::server_pdo(self::connection_config($db_name))
            ->prepare("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name");
        $statement->execute(array(':name' => $db_name));
        return (bool) $statement->fetchColumn();
    }

    /**
     * 表是否存在
     *
     * @param string $table
     * @param string $db_name
     * @return bool
     */
    public static function table_exists(string $table, string $db_name): bool {
        self::assert_identifier($table);
        self::assert_identifier($db_name);
        $statement = self::server_pdo(self::connection_config($db_name))
            ->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table");
        $statement->execute(array(':db' => $db_name, ':table' => $table));
        return (bool) $statement->fetchColumn();
    }

    /**
     * 创建数据库（不带 dbname 连接 MySQL server 才能建库）
     *
     * @param string $db_name
     * @param array  $connection
     * @return void
     */
    private static function create_database(string $db_name, array $connection): void {
        self::assert_identifier($db_name);
        $charset = preg_replace('/[^a-z0-9_]/i', '', (string) ($connection['db_charset'] ?? '')) ?: 'utf8';
        self::server_pdo($connection)
            ->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET {$charset}");
    }

    /**
     * 返回指定库的连接配置
     *
     * @param string $db_name
     * @throws YZE_DBAException 库未在 db_connections 中配置时抛出
     * @return array
     */
    private static function connection_config(string $db_name): array {
        $connections = self::connections();
        if (empty($connections[$db_name])) {
            throw new YZE_DBAException("数据库 {$db_name} 未在 __config__.php 的 db_connections 中配置");
        }
        return (array) $connections[$db_name];
    }

    /**
     * 创建一个不带 dbname 的 PDO 连接，用于建库及查询 information_schema
     *
     * @param array $connection
     * @throws YZE_DBAException 连接失败时抛出
     * @return \PDO
     */
    private static function server_pdo(array $connection): \PDO {
        try {
            $pdo = new \PDO(
                'mysql:host=' . $connection['db_host'] . ';port=' . ($connection['db_port'] ?: 3306),
                (string) $connection['db_user'],
                (string) $connection['db_psw']
            );
        } catch (\PDOException $e) {
            throw new YZE_DBAException(join(' ', (array) $e->errorInfo), $e->getCode());
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /**
     * 归一化需要处理的库名列表；传 null 时取所有已配置且可用的连接
     *
     * @param array|null $db_names
     * @return array
     */
    private static function resolve_db_names(array $db_names = null): array {
        $connections = self::connections();
        if ($db_names === null) {
            $db_names = array();
            foreach ($connections as $name => $connection) {
                if ( ! empty($connection['db_host'])) {
                    $db_names[] = $name;
                }
            }
        }
        return array_values(array_unique($db_names));
    }

    /**
     * 校验标识符，避免拼接建库/清表语句时被注入
     *
     * @param string $identifier
     * @throws YZE_DBAException
     * @return void
     */
    private static function assert_identifier(string $identifier): void {
        if ( ! preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new YZE_DBAException("非法的数据库标识符：{$identifier}");
        }
    }
}
