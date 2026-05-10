# Changelog v3.2.0

本文件记录 v3.2.0 的变更内容。

---

## 修复的 Issue

- [ISS-3.1.0-L01](fixed/ISS-3.1.0-L01-fieldname-uninitialized.md)：`DataValidationException::$fieldName` 未初始化导致 `getFieldName()` 抛错，下游框架处理异常时触发 500

---

## 工程变更

### CaesarCipher 死代码清理

- 删除 `encrypt()` / `decrypt()` 中不可达的 `!is_integer` 分支（`int|string` 类型联合已由 PHP 类型系统保证）

### PHPUnit 配置加固

- 启用 `failOnNotice`、`failOnWarning`、`failOnRisky`、`failOnDeprecation`，任何非 OK 状态均视为失败

### PBT 重构与扩展

- 将单文件 `PropertyTest.php` 拆分为 7 个按领域分组的文件（`ut/pbt/`）
- 新增 property 8-23，覆盖：异常属性安全、类型保持、幂等性、组合等价、后置条件、双射性、strict 拒绝、枚举正确性、密钥敏感性等

---

## 测试覆盖

- 412 tests, 28471 assertions
- 行覆盖率 99.45%（545 行中 542 行覆盖）
- 剩余 3 行为环境依赖不可达代码（igbinary 未安装、fread 防御性分支）
