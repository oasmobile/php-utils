# Implementation Plan: Release 3.0.0 — PHP 8.2+ Syntax Modernization

## Overview

按模块分组对 `src/` 下所有代码进行 PHP 8.2+ 语法全量改造，覆盖 constructor promotion、`readonly`、union/intersection types、`match` 表达式、新字符串函数、`enum`、类型声明补齐共 8 个方向。每个 task 在单个模块组内完成所有改造方向。改造完成后统一适配测试、更新 SSOT、编写 property tests。

实现语言：PHP（与 design 一致）。

## Tasks

- [ ] 1. Validators 模块改造
  - [ ] 1.1 创建 `src/TrimDirection.php` enum 文件
    - 定义 `TrimDirection` 纯 enum，包含 `Both`、`Left`、`Right` 三个 case
    - 命名空间 `Oasis\Mlib\Utils`，遵循 PSR-4
    - _Requirements: 3.1, 3.7_
  - [ ] 1.2 改造 `src/Validators/ValidatorInterface.php`
    - `validate()` 方法添加参数类型 `mixed` 和返回类型 `mixed`
    - 移除冗余 PHPDoc `@param` / `@return` 标签（如与类型声明等价）
    - _Requirements: 2.5, 2.9_
  - [ ] 1.3 改造 `src/Validators/StringValidator.php`
    - Constructor promotion：`$strict`、`$allowEmpty` → `private readonly` promoted properties
    - 保留原始默认值
    - 移除手动属性声明和赋值语句
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.4 改造 `src/Validators/TrimmedStringValidator.php`
    - Constructor promotion：`$strict`、`$direction`、`$characters` → `private readonly` promoted properties
    - `$direction` 参数类型改为 `TrimDirection`，默认值改为 `TrimDirection::Both`
    - 移除 `TRIM_BOTH`、`TRIM_LEFT`、`TRIM_RIGHT` 类常量
    - `validate()` 中 `switch` 替换为 `match` 表达式（基于 `TrimDirection` enum case）
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9, 3.4, 4.2, 4.5_
  - [ ] 1.5 改造 `src/Validators/IntegerValidator.php`
    - Constructor promotion：`$strict`、`$base` → `private readonly` promoted properties
    - 保留原始默认值
    - 移除手动属性声明和赋值语句
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.6 改造 `src/Validators/FloatValidator.php`
    - Constructor promotion：`$strict` → `private readonly` promoted property
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.7 改造 `src/Validators/BooleanValidator.php`
    - Constructor promotion：`$strict` → `private readonly` promoted property
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.8 改造 `src/Validators/ArrayValidator.php`
    - Constructor promotion：`$allowNull` → `private readonly` promoted property
    - `$elementValidator` 有默认值回退逻辑（`new DummyValidator()`），保留手动赋值
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 2.1, 2.2, 2.9_
  - [ ] 1.9 改造 `src/Validators/Array2DValidator.php`
    - 无自有属性需 promote（仅调用 `parent::__construct()`）
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 1.10 改造 `src/Validators/ObjectValidator.php`
    - Constructor promotion：`$allowNull` → `private readonly` promoted property
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.11 改造 `src/Validators/EnumerationValidator.php`
    - Constructor promotion：`$strictType`、`$caseSensitive` → `private readonly` promoted properties
    - `$values` 有条件转换逻辑（`!$caseSensitive` 时 `array_map` 转小写），保留手动赋值
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 2.1, 2.2, 2.9_
  - [ ] 1.12 改造 `src/Validators/StringLengthValidator.php`
    - Constructor promotion：`$maxLength`、`$minLength`、`$chopDown`、`$encoding` → `private readonly` promoted properties
    - 保留原始默认值
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.13 改造 `src/Validators/RegexValidator.php`
    - Constructor promotion：`$pattern` → `private readonly` promoted property
    - 构造函数中有 pattern 校验逻辑，但赋值本身是直接的，可 promote
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 1.14 改造 `src/Validators/ChainedValidator.php`
    - Variadic 参数 `...$args` 不能直接 promote，保留手动赋值
    - 为 `$validators` 属性添加 `array` 类型声明
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 2.1, 2.2, 2.9_
  - [ ] 1.15 改造 `src/Validators/EmailValidator.php`
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 1.16 改造 `src/Validators/UrlValidator.php`
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 1.17 改造 `src/Validators/DummyValidator.php`
    - `validate()` 添加 `mixed` 参数类型和 `mixed` 返回类型
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 1.18 Checkpoint: 运行 `php vendor/bin/phpunit --filter Validator` 确认 Validators 模块编译无语法错误；如因 enum 替代旧常量导致测试失败，记录失败项（将在 Task 8 中适配）

- [ ] 2. DataProvider 模块改造
  - [ ] 2.1 创建 `src/DataType.php` enum 文件
    - 定义 `DataType` string-backed enum，包含 10 个 case（`Int`、`Float`、`String`、`NonEmptyString`、`TrimmedString`、`Array`、`Array2D`、`Bool`、`Object`、`Mixed`）
    - 每个 case 的 backed value 为原常量字符串值（如 `'requireInt'`）
    - 命名空间 `Oasis\Mlib\Utils`，遵循 PSR-4
    - _Requirements: 3.2, 3.7_
  - [ ] 2.2 改造 `src/DataProviderInterface.php`
    - 移除全部 10 个 `const` 类型常量声明
    - 所有方法添加参数类型和返回类型声明
    - `$validator` 参数类型改为 `ValidatorInterface|DataType`
    - `has()` 的 `$validator` 默认值改为 `DataType::Mixed`
    - `get()` / `getMandatory()` / `getOptional()` 的 `$validator` 默认值改为 `DataType::String`
    - 移除冗余 PHPDoc
    - _Requirements: 2.6, 3.5_
  - [ ] 2.3 改造 `src/AbstractDataProvider.php`
    - `getValidatorByLegacyString()` 参数类型改为 `DataType`，`switch` 替换为 `match` 表达式
    - 所有方法添加参数类型和返回类型声明
    - `$validator` 参数类型使用 `ValidatorInterface|DataType`
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.3, 2.9, 4.1, 4.5_
  - [ ] 2.4 改造 `src/HierarchicalDataProviderInterface.php`
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 2.7_
  - [ ] 2.5 改造 `src/ArrayDataProvider.php`
    - Constructor promotion：`$data` → `private readonly` promoted property
    - `$delimeter`、`$paths` 运行时可变，不 readonly
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 2.6 Checkpoint: 运行 `php vendor/bin/phpunit --filter DataProvider` 确认 DataProvider 模块编译无语法错误；记录因常量移除导致的测试失败项

- [ ] 3. Exceptions 模块改造
  - [ ] 3.1 改造 `src/Exceptions/DataValidationException.php`
    - `create()`、`__construct()` 的 `$previous` 参数类型从 `?\Exception` 改为 `?\Throwable`
    - 所有方法添加参数类型和返回类型声明（`create()` → `static`、`getFieldName()` → `string`、`setFieldName()` → `void`、`withFieldName()` → `static`）
    - 移除冗余 PHPDoc
    - _Requirements: 2.8, 2.9_
  - [ ] 3.2 改造 `src/Exceptions/` 下所有子类
    - 涉及文件：`DataEmptyException.php`、`ExistenceViolationException.php`、`InvalidArrayElementException.php`、`InvalidDataTypeException.php`、`InvalidValueException.php`、`MandatoryValueMissingException.php`、`RegexNotMatchedException.php`、`StringTooLongException.php`、`StringTooShortException.php`、`UniquenessViolationException.php`
    - 为各子类的构造函数和方法补齐参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 3.3 Checkpoint: 确认 `src/Exceptions/` 下所有文件无语法错误（`php -l src/Exceptions/*.php`）

- [ ] 4. Utils 模块改造
  - [ ] 4.1 创建 `src/AnsiColor.php` enum 文件
    - 定义 `AnsiColor` int-backed enum，包含 16 个 case（基础 8 色 `Black`–`White` backed value 0–7，亮色变体 `LightBlack`–`LightWhite` backed value 100–107）
    - 命名空间 `Oasis\Mlib\Utils`，遵循 PSR-4
    - _Requirements: 3.3, 3.7_
  - [ ] 4.2 改造 `src/StringUtils.php`
    - `stringStartsWith()` 内部实现替换为 `str_starts_with()`
    - `stringEndsWith()` 内部实现替换为 `str_ends_with()`
    - 保留公开方法签名不变（方法名、参数个数、参数名）
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9, 5.1, 5.2, 5.4_
  - [ ] 4.3 改造 `src/AnsiColorizer.php`
    - `foreground()` / `background()` 参数类型改为 `AnsiColor|int`
    - 移除 `COLOR_*` 常量和字符串颜色名查找逻辑
    - 实现 `getBaseColor()` 辅助方法（亮色 case → 基础色 case 映射）
    - 亮色 case 通过 bold 包裹基础色实现（与原 `LIGHT-` 前缀逻辑一致）
    - `int` 参数路径保留 256 色模式（`38;5;N` / `48;5;N`）
    - `CLOSE_TAG` 常量改为 `private const` 或内联
    - 扫描并替换 `strpos` 惯用法为 `str_contains()` / `str_starts_with()` / `str_ends_with()`（如有）
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9, 3.6, 5.3_
  - [ ] 4.4 改造 `src/CommonUtils.php`
    - `monitorMemoryUsage()` 中解析 memory limit 后缀的 `switch` 替换为 `match` 表达式（保留 `default` 分支）
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9, 4.3, 4.4, 4.5_
  - [ ] 4.5 Checkpoint: 确认 `src/StringUtils.php`、`src/AnsiColorizer.php`、`src/CommonUtils.php` 无语法错误（`php -l`）

- [ ] 5. Crypto 模块改造
  - [ ] 5.1 改造 `src/CaesarCipher.php`
    - Constructor promotion：`$bits`、`$partition`、`$strength` → `private readonly` promoted properties
    - `$lookupTable`、`$reverseLookupTable` 延迟初始化，不 promote、不 readonly
    - 保留原始默认值
    - 所有方法添加参数类型和返回类型声明（`encrypt()`/`decrypt()` → `int|string` 参数和返回类型）
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.9_
  - [ ] 5.2 改造 `src/Rc4.php`
    - `rc4()` 静态方法添加参数类型 `string, string` 和返回类型 `string`
    - 移除冗余 PHPDoc
    - _Requirements: 2.1, 2.2, 2.9_
  - [ ] 5.3 Checkpoint: 运行 `php vendor/bin/phpunit --filter "CaesarCipher|Rc4"` 确认 Crypto 模块测试通过

- [ ] 6. DataPacker 模块改造
  - [ ] 6.1 改造 `src/DataPacker.php`
    - `$serializer`、`$unserializer` 有 callable 回退逻辑，不能直接 promote
    - 为 `$serializer`、`$unserializer` 属性添加 `callable` 类型声明
    - 构造函数参数类型 `?callable`，保留 igbinary 回退逻辑
    - 所有方法添加参数类型和返回类型声明
    - 移除冗余 PHPDoc
    - _Requirements: 1.1, 1.5, 2.1, 2.2, 2.9_
  - [ ] 6.2 Checkpoint: 运行 `php vendor/bin/phpunit`（全量测试），确认所有模块改造后代码编译正确；此时测试可能因 enum 替代旧常量而失败，属于预期——记录失败项，在 Task 8 中适配

- [ ] 7. 修复 `AnsiColorizer::background()` 亮色分支 bug
  - [ ] 7.1 修复 `background()` 方法中亮色分支错误调用 `self::foreground()` 的 bug，改为正确调用 `self::background()`
    - 确保亮色 case 通过 bold 包裹对应基础色的 `background()` 输出
    - _Requirements: Design GK Clarification Q4 — 直接修复此 bug 作为 v3.0.0 额外修复项_
  - [ ] 7.2 Checkpoint: 确认 `src/AnsiColorizer.php` 无语法错误（`php -l`）

- [ ] 8. 测试适配（R6）
  - [ ] 8.1 适配 `ut/TrimmedStringValidatorTest.php`
    - 将测试中使用的 `TrimmedStringValidator::TRIM_BOTH` / `TRIM_LEFT` / `TRIM_RIGHT` 常量替换为 `TrimDirection::Both` / `Left` / `Right` enum case
    - 不新增测试用例，不修改断言逻辑，仅调整调用方式
    - _Requirements: 6.1, 6.3, 6.4_
  - [ ] 8.2 适配 `ut/MlibDataProviderTest.php`
    - 将测试中使用的 `DataProviderInterface::*_TYPE` 常量替换为 `DataType::*` enum case
    - 不新增测试用例，不修改断言逻辑，仅调整调用方式
    - _Requirements: 6.1, 6.3, 6.4_
  - [ ] 8.3 扫描并适配 `ut/` 下其他受影响的测试文件
    - 检查所有测试文件是否引用了被移除的类常量或旧字符串值
    - 如有，替换为对应的 enum case
    - 不新增测试用例，不修改断言逻辑
    - _Requirements: 6.1, 6.3_
  - [ ] 8.4 Checkpoint: 运行 `php vendor/bin/phpunit`（全量测试），要求零失败、零 error、零 deprecation 警告
    - _Requirements: 6.2, 8.1_

- [ ] 9. SSOT 更新（R7）
  - [ ] 9.1 更新 `docs/state/validators.md`
    - 更新 `ValidatorInterface` 接口签名（`mixed` 类型）
    - 各 Validator 构造参数表格标注 `readonly`、类型声明
    - `TrimmedStringValidator` 的 `$direction` 参数改为 `TrimDirection` enum
    - 新增 `TrimDirection` enum 说明
    - 移除 `TRIM_BOTH` / `TRIM_LEFT` / `TRIM_RIGHT` 常量描述
    - _Requirements: 7.1, 7.6_
  - [ ] 9.2 更新 `docs/state/data-provider.md`
    - 移除类型常量表格，替换为 `DataType` enum 说明
    - 更新方法签名表格（参数类型 `ValidatorInterface|DataType`、默认值）
    - 更新行为规则中的字符串映射描述
    - _Requirements: 7.2, 7.6_
  - [ ] 9.3 更新 `docs/state/crypto.md`
    - 更新构造参数表格标注 `readonly`
    - 更新方法签名表格（类型声明）
    - _Requirements: 7.3_
  - [ ] 9.4 更新 `docs/state/utils.md`
    - 更新 StringUtils 方法签名（类型声明）和实现说明（`str_starts_with` / `str_ends_with`）
    - 更新 AnsiColorizer 颜色支持说明（`AnsiColor` enum 替代字符串/常量，`AnsiColor|int` 参数类型）
    - 新增 `AnsiColor` enum 说明
    - 更新 CommonUtils 方法签名（类型声明）和 `match` 替代 `switch` 说明
    - 更新 DataPacker 方法签名（类型声明）
    - _Requirements: 7.4, 7.6_
  - [ ] 9.5 更新 `docs/state/exceptions.md`
    - 更新 DataValidationException 方法签名（`?\Throwable`、返回类型）
    - _Requirements: 7.5_
  - [ ] 9.6 Checkpoint: 逐一比对 `docs/state/` 各文件与改造后的 `src/` 代码，确认 SSOT 与代码一致

- [ ] 10. Property-Based Tests
  - [ ] 10.1 编写 Property 1: Validator behavior preservation
    - 对所有 14 个 Validator 类，使用随机标量值（string/int/float/bool/null/array/object）× 各构造参数组合，验证 `validate()` 返回值或异常类型一致
    - 包含 `TrimmedStringValidator` 使用 `TrimDirection` enum 后的 trim 结果验证
    - 每个 property test 运行 100+ 次迭代
    - **Property 1: Validator behavior preservation**
    - **Validates: Requirements 3.4, 9.2**
  - [ ] 10.2 编写 Property 2: DataProvider behavior preservation
    - 对随机嵌套数组 × 随机 key path × 全部 `DataType` case，验证 `ArrayDataProvider::get()` / `has()` 返回值与原字符串常量映射等价
    - 每个 property test 运行 100+ 次迭代
    - **Property 2: DataProvider behavior preservation**
    - **Validates: Requirements 3.5, 9.5**
  - [ ] 10.3 编写 Property 3: AnsiColorizer output correctness
    - 对随机非空字符串 × 全部 `AnsiColor` case + 随机 int(0–255)，验证 `foreground()` / `background()` 输出包含正确 ANSI 码和 close tag
    - 基础色验证 `\e[{30+offset}m` / `\e[{40+offset}m`，亮色验证 bold 包裹，int 验证 `38;5;N` / `48;5;N`
    - 每个 property test 运行 100+ 次迭代
    - **Property 3: AnsiColorizer output correctness**
    - **Validates: Requirements 3.6**
  - [ ] 10.4 编写 Property 4: StringUtils function equivalence
    - 对随机字符串对 `($haystack, $needle)`，验证 `stringStartsWith()` 返回值与 `str_starts_with()` 一致，`stringEndsWith()` 返回值与 `str_ends_with()` 一致
    - 每个 property test 运行 100+ 次迭代
    - **Property 4: StringUtils function equivalence**
    - **Validates: Requirements 5.1, 5.2**
  - [ ] 10.5 编写 Property 5: CaesarCipher encrypt/decrypt round-trip
    - 对随机有效配置（偶数 bits ∈ (0,64]、偶数 partition 整除 bits、strength ≥ 1）× 随机整数（位范围内）和随机二进制字符串，验证 `decrypt(encrypt(x)) === x`
    - 每个 property test 运行 100+ 次迭代
    - **Property 5: CaesarCipher encrypt/decrypt round-trip**
    - **Validates: Requirements 9.3**
  - [ ] 10.6 编写 Property 6: Rc4 symmetric round-trip
    - 对随机 key 和随机 input，验证 `Rc4::rc4($key, Rc4::rc4($key, $input)) === $input`
    - 每个 property test 运行 100+ 次迭代
    - **Property 6: Rc4 symmetric round-trip**
    - **Validates: Requirements 9.3**
  - [ ] 10.7 编写 Property 7: DataPacker pack/unpack round-trip
    - 对随机可序列化 PHP 值（标量和数组），验证 `unpack(pack($value)) === $value`
    - 每个 property test 运行 100+ 次迭代
    - **Property 7: DataPacker pack/unpack round-trip**
    - **Validates: Requirements 9.4**
  - [ ] 10.8 Checkpoint: 运行 `php vendor/bin/phpunit`（全量测试，含 property tests），要求零失败、零 error

- [ ] 11. Final checkpoint — Release 全量验证
  - [ ] 11.1 运行 `php vendor/bin/phpunit`，要求零失败、零 error、零 deprecation 警告
    - _Requirements: 8.1_
  - [ ] 11.2 验证 `docs/state/` 中所有文件已更新反映改造后的接口
    - _Requirements: 8.2_
  - [ ] 11.3 验证 `src/` 下无残留的 PHP 7.x 风格类型常量（已被 enum 替代的）
    - _Requirements: 8.3_
  - [ ] 11.4 验证 `composer.json` 未引入新的运行时依赖
    - _Requirements: 8.4, 9.6_

- [ ] 12. Code Review
  - 委托给 code-reviewer agent 执行

## Issues

（stabilize 阶段新发现的 issue 记录于此，初始为空）

## Socratic Review

### Q1: tasks 是否完整覆盖了 design 中的所有实现项？

是。Design 中 7 个主要 section（Enum 定义、Constructor Promotion、类型声明补齐、match 表达式、新字符串函数、AnsiColorizer 改造、PHPDoc 清理）均在 Tasks 1–6 的模块改造中覆盖。Convergence Plan 中的 SSOT 更新在 Task 9 覆盖。Testing Strategy 中的 property tests 在 Task 10 覆盖。Design GK Clarification Q4 的 bug fix 在 Task 7 覆盖。

### Q2: task 之间的依赖顺序是否正确？

是。模块改造（Tasks 1–6）按依赖关系排序：Validators 先行（创建 `TrimDirection` enum），DataProvider 次之（创建 `DataType` enum），Utils 创建 `AnsiColor` enum。Exceptions 和 Crypto 无跨模块依赖，排在中间。Bug fix（Task 7）在 Utils 改造后执行。测试适配（Task 8）在所有改造完成后统一执行。SSOT 更新（Task 9）在测试通过后执行。Property tests（Task 10）在 SSOT 更新后执行。

### Q3: 每个 task 的粒度是否合适？

基本合适。每个 sub-task 对应一个源文件的改造，粒度清晰。Task 3.2（Exceptions 子类）将 10 个文件合并为一个 sub-task，因为这些子类的改造模式完全相同（仅类型声明补齐），拆分为 10 个 sub-task 过细。

### Q4: checkpoint 的设置是否覆盖了关键阶段？

是。每个模块改造 task 末尾有 checkpoint 验证编译正确性。Task 6.2 为全量测试首次验证（允许因 enum 替代导致的测试失败）。Task 8.4 为测试适配后的全量通过验证。Task 10.8 为 property tests 通过验证。Task 11 为最终 release 全量验证。

### Q5: Design CR 决策是否在 tasks 编排中体现？

是。Q1（按模块拆分）→ Tasks 1–6 按模块组织。Q2（property tests 独立 task）→ Task 10 独立。Q3（SSOT 和测试统一后置）→ Tasks 8、9 在所有改造后统一执行。Q4（修复 background bug）→ Task 7。

### Q6: 手工测试是否需要？

本次 release 为纯语法重构，无新增用户可感知的功能。行为不变性通过自动化测试（现有回归测试 + property tests）充分验证。手工测试的价值有限，Final checkpoint（Task 11）中的验证项覆盖了非自动化的检查需求。

## Notes

- 每个 task 引用了具体的 requirements 条目以确保可追溯性
- Checkpoints 确保增量验证——Task 6.2 为改造后首次全量验证（允许测试失败），Task 8.4 为测试适配后的全量通过验证，Task 11 为最终 release 验证
- Property tests 使用 PHPUnit 内手动实现随机输入生成 + 循环断言（不引入新依赖，符合 R9 AC6）
- Task 7（bug fix）不受 R9 不变性约束，是 Design GK Clarification Q4 中确认的额外修复项
- 模块改造顺序：Validators → DataProvider → Exceptions → Utils → Crypto → DataPacker，确保 enum 文件在使用它们的模块 task 中创建


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] Checkpoint 从独立 top-level task 改为各 top-level task 的最后一个 sub-task——原 Task 7（全量测试首次验证）合并为 Task 6.2，原 Task 10（全量测试通过）合并为 Task 8.4，原 Task 13（Release 全量验证）重构为 Task 11（含 sub-task）；为 Tasks 1–5 补充各自的 checkpoint sub-task
- [结构] 补充 Code Review 作为最后一个 top-level task（Task 12），描述为委托给 code-reviewer agent 执行
- [结构] 补充 `## Issues` section（release spec 要求，初始为空）
- [结构] 补充 `## Socratic Review` section，覆盖 design 全覆盖、依赖顺序、粒度、checkpoint 设置、Design CR 决策体现、手工测试必要性
- [格式] 移除 Property-Based Tests sub-task 的 `*` optional 标记——steering 要求所有 task 均为 mandatory
- [格式] 移除 Notes 中 "Tasks marked with `*` are optional and can be skipped for faster MVP" 说明
- [内容] Task 7（原 Task 8）bug fix 重构为含 sub-task 和 checkpoint 的标准格式
- [内容] Task 11（原 Task 13）Final checkpoint 重构为含 sub-task 的标准格式，每个验证项独立列出并引用对应 requirement
- [内容] 全文 task 重新编号（原 13 个 top-level task → 12 个），sub-task 编号同步更新
- [内容] Notes section 中的 task 编号引用同步更新

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 中的模块名）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 最后一个 top-level task 是 Code Review（Task 12）
- [x] 自动化实现 task 排在 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1–12），连续无跳号
- [x] sub-task 有层级序号（N.1, N.2...），连续无跳号
- [x] 每个实现类 sub-task 引用了具体的 requirements 条款
- [x] requirements.md 中的每条 requirement 至少被一个 task 引用（R1→Tasks 1/2/5/6, R2→Tasks 1–6, R3→Tasks 1/2/4, R4→Tasks 1/2/4, R5→Task 4, R6→Task 8, R7→Task 9, R8→Task 11, R9→跨 task 引用）
- [x] 引用的 requirement 编号在 requirements.md 中确实存在
- [x] top-level task 按依赖关系排序
- [x] 无循环依赖
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 描述包含具体验证命令或验证方式
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory（无 optional 标记）
- [x] Code Review 是最后一个 top-level task
- [x] Code Review 描述为委托给 code-reviewer agent 执行
- [x] Code Review 未展开 review checklist
- [x] Socratic Review 存在且覆盖充分
- [x] Design CR Q1–Q4 决策在 tasks 编排中体现
- [x] Design 全覆盖（所有模块、接口、实现项均有对应 task）
- [x] 每个 sub-task 描述自包含，可凭 task 描述 + Ref 完成实现
- [x] checkpoint + code review 构成验收闭环
- [x] 执行路径无歧义（排序和依赖关系清晰）
- [○] Release spec "Increment alpha tag" 子任务——本次 release 为纯语法重构，无 alpha 测试周期，不适用此模式
- [○] 手工测试 top-level task——本次 release 为纯语法重构，无新增用户可感知功能，行为不变性通过自动化测试充分验证（见 Socratic Review Q6）
