# Changelog v3.0.1

本文件记录 v3.0.1 hotfix 的变更内容。

---

## 工程变更

### `declare(strict_types=1)`

- 所有 `src/` 和 `ut/` 下的 PHP 文件添加 `declare(strict_types=1);`
- 修复 `Rc4Test` 中因 strict types 暴露的类型错误（`mt_rand()` 返回 int 传给 string 参数）

### 测试覆盖率提升

- 行覆盖率从 82.21% 提升至 95.46%
- 新增测试文件：`CommonUtilsTest`、`AnsiColorizerTest`、`DataValidationExceptionTest`
- 扩展测试：`CaesarCipherTest`、`DataPackerTest`、`ChainedValidatorTest`、`RegexValidatorTest`、`MlibDataProviderTest`、`TrimmedStringValidatorTest`、`StringValidatorTest`

---

## 测试覆盖

- 375 tests, 48848+ assertions，零失败
- 行覆盖率 95.46%（526/551）
