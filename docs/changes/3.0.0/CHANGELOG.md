# Changelog v3.0.0

本文件记录 v3.0.0 release 的变更内容。

---

## 概述

对 `src/` 下所有模块进行 PHP 8.2+ 语法全量改造，覆盖 constructor promotion、`readonly`、union/intersection types、`match` 表达式、新字符串函数、`enum`、类型声明补齐共 8 个方向。改造为纯语法层面重构，不新增功能、不变更业务逻辑、不引入新依赖。由于公共 API 签名变更构成 breaking change，以 major version 3.0.0 发布。

---

## 新增 Enum 类型

### TrimDirection

- 纯 enum，包含 `Both`、`Left`、`Right` 三个 case
- 替代 `TrimmedStringValidator` 中的 `TRIM_BOTH`、`TRIM_LEFT`、`TRIM_RIGHT` 常量

### DataType

- String-backed enum，包含 10 个 case（`Int`、`Float`、`String`、`NonEmptyString`、`TrimmedString`、`Array`、`Array2D`、`Bool`、`Object`、`Mixed`）
- 替代 `DataProviderInterface` 中的 10 个类型常量

### AnsiColor

- Int-backed enum，包含 16 个 case（基础 8 色 + 亮色变体）
- 替代 `AnsiColorizer` 中的 `COLOR_*` 常量和字符串颜色名查找

---

## Constructor Promotion 与 Readonly

以下类的构造函数改为 constructor promotion 形式，适用属性标记为 `readonly`：

- `StringValidator`（`$strict`、`$allowEmpty`）
- `TrimmedStringValidator`（`$strict`、`$direction`、`$characters`）
- `IntegerValidator`（`$strict`、`$base`）
- `FloatValidator`（`$strict`）
- `BooleanValidator`（`$strict`）
- `ArrayValidator`（`$allowNull`）
- `ObjectValidator`（`$allowNull`）
- `EnumerationValidator`（`$strictType`、`$caseSensitive`）
- `StringLengthValidator`（`$maxLength`、`$minLength`、`$chopDown`、`$encoding`）
- `RegexValidator`（`$pattern`）
- `CaesarCipher`（`$bits`、`$partition`、`$strength`）
- `ArrayDataProvider`（`$data`）

---

## 类型声明补齐

- 所有公开/受保护方法的参数和返回值补齐原生类型声明
- `ValidatorInterface::validate()` 签名统一为 `validate(mixed $target): mixed`
- `DataProviderInterface` 方法签名使用 `ValidatorInterface|DataType` union type
- `DataValidationException::$previous` 参数类型从 `?\Exception` 改为 `?\Throwable`
- 移除与类型声明等价的冗余 PHPDoc `@param` / `@return` 标签

---

## Match 表达式替代 Switch

- `AbstractDataProvider::getValidatorByLegacyString()`：基于 `DataType` enum 的 `match`
- `TrimmedStringValidator::validate()`：基于 `TrimDirection` enum 的 `match`
- `CommonUtils::monitorMemoryUsage()`：memory limit 后缀解析改为 `match`

---

## 新字符串函数替代

- `StringUtils::stringStartsWith()` 内部实现替换为 `str_starts_with()`
- `StringUtils::stringEndsWith()` 内部实现替换为 `str_ends_with()`

---

## Bug 修复

- `AnsiColorizer::background()` 亮色分支修复：原代码错误调用 `self::foreground()`，改为正确调用 `self::background()`

---

## 测试

- 现有测试适配 enum 替代常量（`TrimmedStringValidatorTest`、`MlibDataProviderTest`）
- 新增 7 个 property-based tests 验证行为不变性（`ut/PropertyTest.php`）
- 全量测试：325 tests, 48885 assertions，零失败

---

## SSOT 更新

- `docs/state/validators.md`：更新接口签名、构造参数、TrimDirection enum
- `docs/state/data-provider.md`：DataType enum 替代类型常量、方法签名更新
- `docs/state/crypto.md`：constructor promotion、readonly、类型声明
- `docs/state/utils.md`：类型声明、AnsiColor enum、str_starts_with/str_ends_with
- `docs/state/exceptions.md`：类型声明（`?\Throwable`）

---

## Manual 更新

- `docs/manual/data-provider.md`：DataType enum 替代类型常量示例
- `docs/manual/utils.md`：AnsiColor enum 替代字符串颜色名示例

---

## Breaking Changes

- `DataProviderInterface` 移除全部 10 个类型常量，改用 `DataType` enum
- `TrimmedStringValidator` 移除 `TRIM_BOTH`/`TRIM_LEFT`/`TRIM_RIGHT` 常量，`$direction` 参数改为 `TrimDirection` enum
- `AnsiColorizer` 移除 `COLOR_*` 常量，`foreground()`/`background()` 参数类型改为 `AnsiColor|int`
- 所有方法补齐类型声明，传入错误类型将触发 `\TypeError`
