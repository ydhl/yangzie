<?php
namespace yangzie;
// ai@2026-08-28 SQL 类单元测试专用模型（按新规范：Column 注解声明字段元数据，无 CLASS_NAME/$columns）
class T_User extends YZE_Model{
    const TABLE = "users";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";
    const UUID_NAME = "uuid";

    /**
     * 主键
     * @var int
     */
    #[Column(type: 'int', nullable: false, length: 11)]
    private int $id;
    /**
     * 姓名
     * @var string
     */
    #[Column(type: 'string', nullable: false, length: 45)]
    private string $name;
    /**
     * 角色
     * @var string
     */
    #[Column(type: 'enum', nullable: true)]
    private ?string $role;
    /**
     * 价格
     * @var float
     */
    #[Column(type: 'float', nullable: true, length: 10)]
    private ?float $price;
    /**
     * 创建时间
     * @var string
     */
    #[Column(type: 'date', nullable: true)]
    private ?string $created_on;
}
class T_Order extends YZE_Model{
    const TABLE = "orders";
    const MODULE_NAME = "yangzie";
    const KEY_NAME = "id";
    const UUID_NAME = "uuid";

    /**
     * 主键
     * @var int
     */
    #[Column(type: 'int', nullable: false, length: 11)]
    private int $id;
    /**
     * 用户ID
     * @var int
     */
    #[Column(type: 'int', nullable: false, length: 11)]
    private int $user_id;
    /**
     * 金额
     * @var float
     */
    #[Column(type: 'float', nullable: true, length: 10)]
    private ?float $amount;
    /**
     * 状态
     * @var string
     */
    #[Column(type: 'enum', nullable: true)]
    private ?string $status;
}
