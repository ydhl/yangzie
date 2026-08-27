
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

生成产物：
- `app/modules/<module>/models/<table>_model.class.php`（Model 类，含表字段常量、关联字段）
- `app/modules/<module>/models/<table>_model_method.trait.php`（业务方法 trait）
- `tests/<module>/<table>_model.class.phpt`（测试骨架）
- 若模块不存在，自动创建模块脚手架

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
- `app/modules/<module>/`（模块完整目录：controllers/ views/ models/ layouts/ 及 `__config__.php`）
- `app/modules/<module>/controllers/<controller>_controller.class.php`（controller 类，已存在则追加新 action 方法）
- `app/modules/<module>/views/<controller>-<action>.tpl.php`（视图文件）
- `app/vendor/layouts/tpl.layout.php`（tpl 布局，不存在时创建）
- `tests/<module>/<controller>_controller.class.phpt`
- 显式传入 `--route` 时，会把路由写入 `__config__.php` 的 `routers` 配置；不传则默认 URI 为 `/{module}/{controller}/{action}` 但不会写入配置。

### 3. Phar 打包模块

```
php scripts/yze.php --phar --module=MODULE [--key=KEY_FILE]
```

| 参数 | 别名 | 必填 | 说明 |
|---|---|---|---|
| `--phar` | `-p` | 是 | Phar 打包模式 |
| `--module=MODULE` | `-M` | 是 | 要打包的模块名 |
| `--key=KEY_FILE` | `-k` | 否 | OpenSSL 签名私钥（tmp 目录下的 pem 文件） |

示例：
```bash
php scripts/yze.php --phar --module=admin
```

产物：`app/modules/<module>.phar`（有签名时附带 `<module>.phar.pubkey`）。

> ⚠️ **已知问题**：`-k/--key` 未注册进 `getopt()`（`getopt("mcpC:a:r:d:t:M:h", [...])` 中没有 `k:`/`key:`），所以 CLI 模式下签名 key 参数实际传不进去，只会生成未签名 phar。如需带签名，请走交互向导选项 5。

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
