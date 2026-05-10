# ISS-3.1.0-L01: DataValidationException::$fieldName Uninitialized Access

本文件为项目级 issue，记录已发布版本中的 bug。

---

## Metadata

| 字段 | 值 |
|------|------|
| Severity | `[P1] major` |
| Status | `closed` |
| Found In | `v3.1.0` |
| Fixed In | `master@251299e` |
| Related Test | |

---

## Description

`DataValidationException::$fieldName` 声明为 typed property（`protected string $fieldName;`）且无默认值。当业务代码通过 `new DataValidationException("message")` 构造异常而未调用 `setFieldName()` 或 `withFieldName()` 时，`$fieldName` 处于未初始化状态。

下游包 `oasis/http` 的 `ExceptionWrapper::furtherProcessException()` 在捕获 `DataValidationException` 后无条件调用 `$e->getFieldName()`，触发 PHP Error：

```
Typed property Oasis\Mlib\Utils\Exceptions\DataValidationException::$fieldName must not be accessed before initialization
```

最终表现为 HTTP 500，而非预期的 400 Bad Request。

---

## Steps to Reproduce

1. 在任意业务代码中直接抛出异常，不设置 `fieldName`：
   ```php
   throw new DataValidationException("duplicate entry");
   ```
2. 由框架的 `ExceptionWrapper` 捕获并处理该异常
3. `ExceptionWrapper` 调用 `$e->getFieldName()` 触发 uninitialized property 错误

---

## Expected Behavior

异常被正常处理，返回 HTTP 400 Bad Request。

---

## Actual Behavior

PHP 抛出 `\Error`（typed property uninitialized），最终返回 HTTP 500。

---

## Analysis

根因在 `src/Exceptions/DataValidationException.php`：

- `$fieldName` 声明为 `protected string $fieldName;`，无默认值
- `getFieldName()` 直接 `return $this->fieldName;`，无 isset 保护
- 构造函数不初始化 `$fieldName`

可选修复方案：
1. 给 `$fieldName` 设置默认值：`protected string $fieldName = '';`
2. 在 `getFieldName()` 中加 `isset` 保护

---

## History

- `2026-05-10T00:00Z` `v3.1.0` [发现] 下游用户反馈，构造异常不设置 fieldName 时触发 500
- `2026-05-10T00:00Z` `v3.1.0` [修复] master 直接修复，设置默认值 `$fieldName = ''`，commit 251299e
