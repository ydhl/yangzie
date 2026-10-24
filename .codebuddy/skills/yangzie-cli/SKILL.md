---
name: yangzie-cli
description: Yangzie PHP 框架的命令行代码生成工具（入口 scripts/yze.php）的使用指南。当用户需要生成 model（数据库表转代码）、生成 module/controller/view 脚手架、打包模块为 phar、运行单元测试，或涉及 scripts/yze.php、scripts/generate-model.php 等脚本时使用本 skill。此 skill 应用于 yangzie 项目（含 scripts/ 目录和 app/modules/ 结构的项目）。
---

# Yangzie CLI

## Overview

yangzie CLI 是 yangzie 框架自带的代码生成工具，入口为 `scripts/yze.php`。支持 CLI 参数与交互向导两种运行方式，可生成 model、module/controller/view 脚手架、打包 phar、运行测试。

**必须从项目根目录执行**：脚本会 `chdir` 到 `app/public_html` 并加载 `init.php`，在其他目录运行会失败。

## 核心命令速查

| 任务 | 命令 |
|---|---|
| 生成 Model（表转代码） | `php scripts/yze.php --model --table=TABLE --module=MODULE [--db=DB]` |
| 生成脚手架 | `php scripts/yze.php --mvc --module=MODULE --controller=CONTROLLER [--action=A] [--route=URI]` |
| Phar 打包 | `php scripts/yze.php --phar --module=MODULE [--key=KEY_FILE]` |
| 交互向导 | `php scripts/yze.php`（不带参数） |
| 查看帮助 | `php scripts/yze.php -h` |

## 生成 Model

用法：`--model`（或 `-m`）+ `--table`（必填）+ `--module`（必填），`--db` 可选（缺省用配置的 `default_db`）。

生成产物：
- `app/modules/<module>/models/<table>.model.php` — Model 类，字段元数据用 `#[Column]` 注解声明
- `app/modules/<module>/models/<table>_<field>.enum.php` — 每个 MySQL enum 字段一个 PHP enum 类型
- `app/modules/<module>/models/<table>.method.php` — 业务方法 trait
- `tests/<module>/<table>_model.model.phpt` — 测试骨架

注意：
- 需要可用的 MySQL 连接（配置在 `app/__config__.php` 的 `db_connections`）
- 生成的 `.model.php` 标注 DO NOT EDIT，业务方法写到 `.method.php` trait
- enum 类型：类型名 `{Table}_{Field}_Enum`，用 `::cases()` / `::from()` 取值

## 生成 Module / Controller / View

用法：`--mvc`（或 `-c`）+ `--module`（必填）+ `--controller`（必填），`--action` 默认 `index`，`--route` 可选。

产物：模块目录（controllers/ models/ views/ hooks/ public_html/）、controller 类、视图 `<controller>-<action>.tpl.php`、tpl 布局、phpt 测试。

注意：controller 已存在时追加新 action 方法；显式传 `--route` 才会写入 `__config__.php` 的 `routers`。

## Phar 打包

用法：`--phar`（或 `-p`）+ `--module`（必填），`--key` 可选。

- `--key` 接受完整路径或 `tmp/` 下的 pem 文件名
- key 文件不存在时红字报错终止，不会生成未签名 phar
- 产物：`app/modules/<module>.phar`，签名时附带 `.phar.pubkey`

## 交互向导

不带参数运行 `php scripts/yze.php`，菜单含：生成脚手架、生成 model、删除模块、删除 controller、Phar 打包、运行单元测试，输入 `0` 退出/返回上级。向导覆盖 CLI 参数模式的全部功能。

## 详细参考

完整参数表、示例与产物清单见 `docs/cli.md`（本 skill 与框架手册共用同一份文档，路径相对仓库根：`docs/cli.md`）。
