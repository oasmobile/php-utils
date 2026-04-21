# Changelog v2.0.1

本文件记录 v2.0.1 hotfix 的变更内容。

---

## Bugfix

### memory-limit-warning-fix

修复 `CommonUtils::monitorMemoryUsage()` 在 M/G 单位转换时产生小数值导致 PHP 8.x `E_WARNING: Invalid quantity` 的问题，同时添加 Global_Switch 功能。

**修复内容：**

- `src/CommonUtils.php`：M/G 单位转换公式从 `ceil($x / 1024 * 100) / 100` 改为 `ceil($x / 1024)`，确保 Memory_Limit_String 始终为整数值
- `src/CommonUtils.php`：新增 `enableMemoryMonitor()` / `disableMemoryMonitor()` 静态方法，提供运行时全局开关控制内存监控行为
- `src/CommonUtils.php`：`monitorMemoryUsage()` 入口添加 Global_Switch 检查，禁用时直接返回

**文档更新：**

- `docs/state/utils.md`：新增 `enableMemoryMonitor()` / `disableMemoryMonitor()` 方法描述、Global_Switch 行为说明、M/G 整数转换说明

**新增测试：**

- `ut/MemoryMonitorBugConditionTest.php`：Bug Condition 验证测试
- `ut/MemoryMonitorPreservationTest.php`：Preservation 行为保留测试
- `ut/MemoryMonitorGlobalSwitchTest.php`：Global_Switch 功能测试
- `phpunit.xml`：注册 3 个新测试文件

---

## 修复的 Issue

无。

---

## 测试覆盖

- 全量测试通过：399 tests, 2524 assertions，无 deprecation 警告
