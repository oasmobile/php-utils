# Changelog v3.1.0

本文件记录 v3.1.0 hotfix 的变更内容。

---

## 概述

修复 `CommonUtils::monitorMemoryUsage()` 在 PHP 8.5 下因 `memory_limit` 返回小数值导致的不兼容问题；将 `fprintf(STDERR)` 替换为 PSR-3 Logger 静态注入；PropertyTest 从手动 `mt_rand` 循环改为 Eris `forAll`/`then` 模式。

---

## 依赖变更

| 包 | 变更前 | 变更后 | 类型 |
|----|--------|--------|------|
| `php` | >=8.2 | >=8.5 | 运行时 |
| `psr/log` | — | ^3.0 | 运行时（新增） |
| `phpunit/phpunit` | ^11.0 | ^13.0 | 开发 |
| `giorgiosironi/eris` | — | ^1.1 | 开发（新增） |

---

## 修复

### `CommonUtils::monitorMemoryUsage()` PHP 8.5 兼容性

- PHP 8.5 的 `ini_get('memory_limit')` 可能返回小数值字符串，原代码使用 `(int)substr()` 截断后缀时会丢失精度
- 修复：使用 `(int)` 强制转换确保整数运算正确

### `CommonUtils` 日志输出方式变更

- 原实现使用 `fprintf(STDERR)` 输出内存调整信息
- 改为 PSR-3 `LoggerInterface` 静态注入（`setLogger()` / null-safe 调用）
- 默认无 logger 时静默，不产生任何输出

---

## 测试变更

### PropertyTest 改用 Eris

- 从手动 `mt_rand` 循环改为 Eris `forAll`/`then` 模式
- 获得自动 shrinking 能力，失败时能定位最小反例
- 新增开发依赖 `giorgiosironi/eris` ^1.1

---

## 测试覆盖

- 377 tests, 22645 assertions，零失败
