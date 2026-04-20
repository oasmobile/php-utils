# Engineering

本文件描述项目的工程约束与技术选型。

---

## 运行时要求

| 项目 | 约束 |
|------|------|
| PHP | >=8.2 |
| 扩展 | 无强制要求（igbinary 可选，用于 DataPacker 默认序列化） |

---

## 依赖

### 运行时依赖

| 包 | 版本约束 | 用途 |
|----|----------|------|
| `voku/portable-utf8` | ^3.0 | UTF-8 字符串处理（StringUtils、StringLengthValidator） |

### 开发依赖

| 包 | 版本约束 | 用途 |
|----|----------|------|
| `phpunit/phpunit` | ^11.0 | 单元测试框架 |

---

## 包管理

| 项目 | 值 |
|------|-----|
| 包管理器 | Composer |
| 自动加载 | PSR-4（`Oasis\Mlib\Utils\` → `src/`） |
| 包名 | `oasis/utils` |
| 许可证 | MIT |

---

## 测试

| 项目 | 值 |
|------|-----|
| 框架 | PHPUnit 11.x |
| 配置文件 | `phpunit.xml` |
| 测试目录 | `ut/` |
| Bootstrap | `ut/bootstrap.php` |
| 缓存目录 | `.phpunit.cache/`（已加入 `.gitignore`） |
| 全量命令 | `php vendor/bin/phpunit` |
| 单文件命令 | `php vendor/bin/phpunit ut/<TestFile>.php` |
