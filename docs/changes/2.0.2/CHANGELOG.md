# Changelog v2.0.2

本文件记录 v2.0.2 hotfix 的变更内容。

---

## Bugfix

### stderr-to-error-log

将 `CommonUtils::monitorMemoryUsage()` 的日志输出从 `fprintf(STDERR, ...)` 替换为 `error_log()`，同时移除 CLI 环境限定（`isRunningFromCommandLine()` 判断），使内存监控日志在所有 SAPI 环境下统一通过 `error_log()` 输出。

**修复内容：**

- `src/CommonUtils.php`：`monitorMemoryUsage()` 中的 `fprintf(STDERR, ...)` 替换为 `error_log()`
- `src/CommonUtils.php`：移除 `isRunningFromCommandLine()` 条件判断，日志输出不再限定 CLI 环境

---

## 修复的 Issue

无。

---

## 测试覆盖

- 全量测试通过：399 tests, 2524 assertions
