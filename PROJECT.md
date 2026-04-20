# Project

本文件为 Agent 提供项目级元信息，是首次接触项目时的必读文件。

---

## 基本信息

| 项目 | 值 |
|------|-----|
| 名称 | oasis/utils |
| 类型 | PHP Library |
| 命名空间 | `Oasis\Mlib\Utils` |
| 许可证 | MIT |
| PHP 版本 | ≥ 5.6（推断自 PHPUnit 5.x） |

---

## 技术栈

| 层 | 技术 |
|----|------|
| 语言 | PHP |
| 包管理 | Composer |
| 测试框架 | PHPUnit 5.x |
| 依赖 | `voku/portable-utf8` ^3.0 |
| 自动加载 | PSR-4（`Oasis\Mlib\Utils\` → `src/`） |

---

## 目录结构

```
src/                  # 源代码（PSR-4 根）
src/Exceptions/       # 自定义异常类
src/Validators/       # 数据验证器
ut/                   # 单元测试
docs/                 # 文档分层目录
```

---

## 构建与测试命令

```bash
# 安装依赖
composer install

# 运行全量测试（本项目需要 PHP 7.4）
php74 vendor/bin/phpunit

# 运行单个测试文件
php74 vendor/bin/phpunit ut/SomeTest.php
```

> **注意**：本项目运行在 PHP 7.4 上。系统默认 `php` 是 8.x，请使用 `php74` 别名（指向 `/usr/local/opt/php@7.4/bin/php`）。

---

## 版本号位置

- `composer.json` → `version` 字段（当前未显式声明，由 VCS tag 管理）

---

## 敏感文件清单

| 文件/模式 | 说明 |
|-----------|------|
| `composer.lock` | 锁定依赖版本，不应随意修改 |
| `.env*` | 环境变量（当前不存在，但若出现应视为敏感） |

---

## 主要功能模块

| 模块 | 说明 |
|------|------|
| `DataProviderInterface` / `AbstractDataProvider` / `ArrayDataProvider` | 统一数据读取与类型校验 |
| `Validators/*` | 各类型验证器（String、Int、Float、Bool、Array、Email、Url、Regex 等） |
| `StringUtils` | 字符串工具方法 |
| `DataPacker` | 数据打包工具 |
| `CaesarCipher` / `Rc4` | 加密算法实现 |
| `AnsiColorizer` | ANSI 终端颜色输出 |
| `CommonUtils` | 通用工具（CLI 检测、内存监控、位运算） |
