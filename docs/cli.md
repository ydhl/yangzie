
## 一、两种运行方式

| 方式 | 命令 | 适用场景 |
|---|---|---|
| CLI 参数 | `php scripts/yze.php [options]` | 传参直接生成，适合脚本化/自动化 |
| 交互向导 | `php scripts/yze.php`（不带参数） | 按菜单逐步操作，功能最全 |

说明：脚本运行时会把工作目录切到 `app/public_html` 并加载 `init.php`，因此**必须从项目根目录执行**。

---

## 二、CLI 参数方式

通用语法：
```bash
php scripts/yze.php <模式选项> [其他参数]
```

### 1. 生成 Model（数据库表转代码）

```
php scripts/yze.php --model --table=TABLE --module=MODULE [--db=DB_NAME]
```

| 参数 | 别名 | 必填 | 说明 |
|---|---|---|---|
| `--model` | `-m` | 是 | 进入 model 生成模式 |
| `--table=TABLE` | `-t` | 是 | 数据库表名 |
| `--module=MODULE` | `-M` | 是 | Model 所在模块名 |
| `--db=DB_NAME` | `-d` | 否 | 数据库名，缺省用 app 配置的 `default_db` |

示例：
```bash
# 用默认库生成
php scripts/yze.php --model --table=acl --module=admin

# 指定数据库
php scripts/yze.php -m -t user -M user -d test
```

<!-- ai@2026-08-28 更新：字段元数据改为 Column 注解；补充 enum 类型产物；修正 phpt 文件名 -->
生成产物：
- `app/modules/<module>/models/<table>.model.php`（Model 类：字段元数据用 `#[Column]` 注解声明，类 docblock 生成 `@property` 提示；enum 字段的属性类型为生成的 PHP enum 类）
- `app/modules/<module>/models/<table>_<field>.enum.php`（MySQL enum 字段对应的 PHP enum 类型，每个 enum 字段生成一个文件）
- `app/modules/<module>/models/<table>.method.php`（业务方法 trait）
- `tests/<module>/<table>.model.phpt`（测试骨架）
- 若模块不存在，自动创建模块脚手架

enum 字段说明（PHP 8.1+）：
- 枚举类型名 `{Table}_{Field}_Enum`（如 `Users_Role_Enum`），文件 `<table>_<field>.enum.php`
- case 名为大写的 MySQL enum 值，值为原始字符串：`case ADMIN = 'admin'`
- 用 `{Enum}::cases()` 获取全部取值、`{Enum}::from($v)` 按值取实例

### 2. 生成 Module / Controller / View 脚手架

```
php scripts/yze.php --mvc --module=MODULE --controller=CONTROLLER [--action=ACTION] [--route=URI]
```

| 参数 | 别名 | 必填 | 说明 |
|---|---|---|---|
| `--mvc` | `-c` | 是 | 进入脚手架生成模式 |
| `--module=MODULE` | `-M` | 是 | 模块名 |
| `--controller=CONTROLLER` | `-C` | 是 | 控制器名 |
| `--action=ACTION` | `-a` | 否 | action 名，默认 `index` |
| `--route=URI` | `-r` | 否 | 路由 URI，支持正则如 `user/(?P<id>\d+)` |

示例：
```bash
# 最小用法
php scripts/yze.php --mvc --module=admin --controller=index

# 指定 action 和路由（带正则参数）
php scripts/yze.php --mvc -M admin -C user -a edit -r 'user/(?P<id>\d+)'
```

生成产物：
<!-- ai@2026-08-28 修正：模块目录为 controllers/ models/ views/ hooks/ public_html/，无 layouts/ -->
- `app/modules/<module>/`（模块完整目录：controllers/ models/ views/ hooks/ public_html/ 及 `__config__.php`）
- `app/modules/<module>/controllers/<controller>.controller.php`（controller 类，已存在则追加新 action 方法）
- `app/modules/<module>/views/<controller>-<action>.tpl.php`（视图文件）
- `app/vendor/layouts/tpl.layout.php`（tpl 布局，不存在时创建）
- `tests/<module>/<controller>.controller.phpt`
- 显式传入 `--route` 时，会把路由写入 `__config__.php` 的 `routers` 配置；不传则默认 URI 为 `/{module}/{controller}/{action}` 但不会写入配置。

### 3. Phar 打包模块

```
php scripts/yze.php --phar --module=MODULE [--key=KEY_FILE]
```

| 参数 | 别名 | 必填 | 说明 |
|---|---|---|---|
| `--phar` | `-p` | 是 | Phar 打包模式 |
| `--module=MODULE` | `-M` | 是 | 要打包的模块名 |
| `--key=KEY_FILE` | `-k` | 否 | OpenSSL 签名私钥（pem 文件，可传完整路径，或 tmp 目录下的文件名，如 `mykey.pem`） |

示例：
```bash
# 未签名
php scripts/yze.php --phar --module=admin

# 带 OpenSSL 签名（key 为 tmp 目录下的 pem 文件名，或完整路径）
php scripts/yze.php --phar --module=admin --key=mykey.pem
php scripts/yze.php -p -M admin -k /path/to/mykey.pem
```

产物：`app/modules/<module>.phar`（有签名时附带 `<module>.phar.pubkey`）。

> 说明：key 文件不存在时（`tmp/` 下也找不到）会红字提示并终止，不会静默生成未签名 phar。

### 4. 查看帮助

```bash
php scripts/yze.php -h
php scripts/yze.php --help
```

---

## 三、交互式向导方式

直接运行（不带参数）：
```bash
php scripts/yze.php
```

菜单选项：
1. 生成 module、controller、view 脚手架
2. 生成 model（数据库表转代码）
3. 删除模块
4. 删除 controller 及对应 view 文件
5. Phar 打包模块
6. 运行单元测试
0. 退出

各级输入 `0` 可返回上级/退出。
