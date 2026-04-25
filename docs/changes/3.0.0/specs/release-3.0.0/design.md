# Design Document: Release 3.0.0

## Overview

本设计文档描述 `oasis/utils` v3.0.0 release 的执行方案。

v3.0.0 对 `src/` 下所有模块进行 PHP 8.2+ 语法全量改造，覆盖 constructor promotion、`readonly`、union/intersection types、`match` 表达式、named arguments、新字符串函数、`enum`、类型声明补齐共 8 个方向。改造为纯语法层面重构，不新增功能、不变更业务逻辑、不引入新依赖。由于公共 API 签名变更（类型声明补齐、enum 替代常量等）构成 breaking change，以 major version 3.0.0 发布。

核心原则：**行为语义不变**——所有改造仅改变代码表达形式，不改变运行时行为。

---

## Architecture

项目整体架构不变。改造范围限于 `src/` 下的源代码文件和 `ut/` 下的测试适配，以及 `docs/state/` 的 SSOT 更新。

### 文件结构变更

```
src/
├── Exceptions/                  # 异常类（类型声明补齐）
├── Validators/
│   ├── ValidatorInterface.php   # 类型声明补齐
│   ├── *Validator.php           # constructor promotion + readonly + 类型声明
│   └── ...
├── TrimDirection.php            # 新增 enum
├── DataType.php                 # 新增 enum
├── AnsiColor.php                # 新增 enum
├── DataProviderInterface.php    # enum 替代常量 + 类型声明
├── AbstractDataProvider.php     # match + 类型声明 + DataType enum
├── ArrayDataProvider.php        # constructor promotion + 类型声明
├── AnsiColorizer.php            # AnsiColor enum + str_starts_with/str_ends_with
├── StringUtils.php              # str_starts_with/str_ends_with
├── CommonUtils.php              # match + 类型声明
├── DataPacker.php               # constructor promotion（部分）+ 类型声明
├── CaesarCipher.php             # constructor promotion + readonly + 类型声明
└── Rc4.php                      # 类型声明
```

新增 3 个 enum 文件，均放置在 `src/` 根目录（`Oasis\Mlib\Utils` 命名空间），遵循 PSR-4。

---

## Components and Interfaces

### 1. Enum 定义

#### 1.1 `TrimDirection` enum

替代 `TrimmedStringValidator` 中的 `TRIM_BOTH`、`TRIM_LEFT`、`TRIM_RIGHT` 常量。

```php
namespace Oasis\Mlib\Utils;

enum TrimDirection
{
    case Both;
    case Left;
    case Right;
}
```

- 纯 enum（无 backed value），因为原常量字符串值（`'both'`、`'left'`、`'right'`）仅用于内部分支判断，无需对外暴露
- `TrimmedStringValidator` 构造函数 `$direction` 参数类型从 `string` 改为 `TrimDirection`，默认值从 `self::TRIM_BOTH` 改为 `TrimDirection::Both`

#### 1.2 `DataType` enum

替代 `DataProviderInterface` 中的 10 个类型常量。

```php
namespace Oasis\Mlib\Utils;

enum DataType: string
{
    case Int              = 'requireInt';
    case Float            = 'requireFloat';
    case String           = 'requireString';
    case NonEmptyString   = 'requireNonEmptyString';
    case TrimmedString    = 'requireTrimmedString';
    case Array            = 'requireArray';
    case Array2D          = 'requireArray2D';
    case Bool             = 'requireBool';
    case Object           = 'requireObject';
    case Mixed            = 'requireMixed';
}
```

- String-backed enum，backed value 保留原常量字符串值，确保 `AbstractDataProvider::getValidatorByLegacyString()` 的 `match` 表达式可直接匹配
- `DataProviderInterface` 移除全部 10 个 `const` 声明，方法签名中 `$validator` 参数类型改为 `ValidatorInterface|DataType`
- 方法默认值同步变更：`has()` 的 `$validator` 默认值从 `self::MIXED_TYPE` 改为 `DataType::Mixed`；`get()` / `getMandatory()` / `getOptional()` 的 `$validator` 默认值从 `self::STRING_TYPE` 改为 `DataType::String`

#### 1.3 `AnsiColor` enum

替代 `AnsiColorizer` 中的颜色名字符串查找和 `COLOR_*` 常量。

```php
namespace Oasis\Mlib\Utils;

enum AnsiColor: int
{
    case Black        = 0;
    case Red          = 1;
    case Green        = 2;
    case Yellow       = 3;
    case Blue         = 4;
    case Magenta      = 5;
    case Cyan         = 6;
    case White        = 7;
    case LightBlack   = 100;
    case LightRed     = 101;
    case LightGreen   = 102;
    case LightYellow  = 103;
    case LightBlue    = 104;
    case LightMagenta = 105;
    case LightCyan    = 106;
    case LightWhite   = 107;
}
```

- Int-backed enum，基础 8 色的 backed value 为 0–7（与原 `COLOR_*` 常量一致），亮色变体使用 100–107（仅作区分标识，不直接用于 ANSI 码计算）
- `foreground()` / `background()` 参数类型改为 `AnsiColor|int`：
  - `AnsiColor` 基础色 case → 直接用 `$color->value` 计算 ANSI 码（30+offset / 40+offset）
  - `AnsiColor` 亮色 case → 用 bold 包裹对应基础色（与原 `LIGHT-` 前缀逻辑一致）
  - `int` → 256 色模式（`38;5;N` / `48;5;N`）
- 移除 `AnsiColorizer` 中的 `COLOR_*` 常量和 `CLOSE_TAG` 常量（`CLOSE_TAG` 改为类内 `private const` 或直接内联）

---

### 2. Constructor Promotion 与 `readonly` 改造

改造原则：
- 构造函数中直接赋值且后续不再修改的参数 → `readonly` promoted property
- 构造函数中有额外初始化逻辑的参数 → 仅 promote 可直接赋值的部分，保留手动赋值
- 延迟初始化的属性（如 `CaesarCipher::$lookupTable`）→ 不使用 `readonly`

#### 改造清单

| 类 | Promoted 属性 | `readonly` | 备注 |
|----|---------------|------------|------|
| `StringValidator` | `$strict`, `$allowEmpty` | 全部 readonly | 构造后不变 |
| `TrimmedStringValidator` | `$strict`, `$direction`, `$characters` | 全部 readonly | `$direction` 类型改为 `TrimDirection` |
| `IntegerValidator` | `$strict`, `$base` | 全部 readonly | |
| `FloatValidator` | `$strict` | readonly | |
| `BooleanValidator` | `$strict` | readonly | |
| `ArrayValidator` | `$allowNull` | readonly | `$elementValidator` 有默认值回退逻辑，需手动赋值 |
| `ObjectValidator` | `$allowNull` | readonly | |
| `EnumerationValidator` | `$strictType`, `$caseSensitive` | readonly | `$values` 有条件转换逻辑，需手动赋值 |
| `StringLengthValidator` | `$maxLength`, `$minLength`, `$chopDown`, `$encoding` | 全部 readonly | |
| `RegexValidator` | `$pattern` | readonly | 构造函数中有 pattern 校验逻辑，但赋值本身是直接的 |
| `ChainedValidator` | `$validators`（variadic） | readonly | variadic 参数需先校验再赋值，不能直接 promote；保留手动赋值 |
| `CaesarCipher` | `$bits`, `$partition`, `$strength` | 全部 readonly | `$lookupTable`、`$reverseTable` 延迟初始化，不 promote、不 readonly |
| `DataPacker` | — | — | `$serializer`、`$unserializer` 有 callable 回退逻辑，不能直接 promote |
| `ArrayDataProvider` | `$data` | readonly | `$delimeter`、`$paths` 运行时可变，不 readonly |
| `Array2DValidator` | — | — | 仅调用 `parent::__construct()`，无自有属性需 promote |

**`DataPacker` 特殊处理**：构造函数接收 `?callable $serializer = null, ?callable $unserializer = null`，内部有 `is_callable()` 检查和 igbinary 回退逻辑。这两个参数不能直接 promote，保留手动属性声明和赋值。但为属性添加 `callable` 类型声明。

**`ChainedValidator` 特殊处理**：构造函数使用 variadic `...$args`，内部有类型校验循环。不能直接 promote variadic 参数，保留手动赋值。但为 `$validators` 属性添加 `array` 类型声明。

**`EnumerationValidator` 特殊处理**：`$values` 在 `!$caseSensitive` 时会被 `array_map` 转换为小写，不能直接 promote。`$strictType` 和 `$caseSensitive` 可以 promote 为 readonly。

---

### 3. 类型声明补齐

#### 3.1 接口层

| 接口 | 方法 | 参数类型 | 返回类型 |
|------|------|----------|----------|
| `ValidatorInterface` | `validate($target)` | `mixed` | `mixed` |
| `DataProviderInterface` | `has($key, $validator)` | `string`, `ValidatorInterface\|DataType` | `bool` |
| `DataProviderInterface` | `get($key, $validator, $isMandatory, $default)` | `string`, `ValidatorInterface\|DataType`, `bool`, `mixed` | `mixed` |
| `DataProviderInterface` | `getMandatory($key, $validator)` | `string`, `ValidatorInterface\|DataType` | `mixed` |
| `DataProviderInterface` | `getOptional($key, $validator, $default)` | `string`, `ValidatorInterface\|DataType`, `mixed` | `mixed` |
| `HierarchicalDataProviderInterface` | `getCurrentPath()` | — | `string` |
| `HierarchicalDataProviderInterface` | `setCurrentPath($path)` | `string` | `void` |
| `HierarchicalDataProviderInterface` | `pushPath($relativePath)` | `string` | `void` |
| `HierarchicalDataProviderInterface` | `popPath()` | — | `void` |
| `HierarchicalDataProviderInterface` | `getPathDelimiter()` | — | `string` |
| `HierarchicalDataProviderInterface` | `setPathDelimiter($delimiter)` | `string` | `void` |

#### 3.2 `DataValidationException`

| 方法 | 参数类型 | 返回类型 |
|------|----------|----------|
| `create($message, $code, $previous)` | `string`, `int`, `?\Throwable` | `static` |
| `__construct($message, $code, $previous)` | `string`, `int`, `?\Throwable` | — |
| `getFieldName()` | — | `string` |
| `setFieldName($fieldName)` | `string` | `void` |
| `withFieldName($fieldName)` | `string` | `static` |

注意：`$previous` 参数当前已使用 `?\Exception`，改为 `?\Throwable` 以符合 PHP 最佳实践（`\Throwable` 是更通用的接口）。

#### 3.3 各 Validator 的 `validate()` 方法

所有 Validator 的 `validate()` 方法签名统一为 `validate(mixed $target): mixed`，与 `ValidatorInterface` 一致。

#### 3.4 工具类

| 类 | 方法 | 参数类型 | 返回类型 |
|----|------|----------|----------|
| `StringUtils` | `stringChopdown($str, $maxLength, $lengthUnitInByte)` | `string`, `int`, `bool` | `string` |
| `StringUtils` | `stringStartsWith($haystack, $needle)` | `string`, `string` | `bool` |
| `StringUtils` | `stringEndsWith($haystack, $needle)` | `string`, `string` | `bool` |
| `CommonUtils` | `isRunningFromCommandLine()` | — | `bool` |
| `CommonUtils` | `monitorMemoryUsage($minUsage, $lowerThreshold, $upperThreshold)` | `int`, `int`, `int` | `void` |
| `CommonUtils` | `registerMemoryMonitorForTick()` | — | `void` |
| `CommonUtils` | `unsignedRightShift($num, $bits)` | `int`, `int` | `int` |
| `Rc4` | `rc4($key, $input)` | `string`, `string` | `string` |

#### 3.5 `CaesarCipher`

| 方法 | 参数类型 | 返回类型 |
|------|----------|----------|
| `__construct($bits, $partition, $strength)` | `int`, `int`, `int` | — |
| `encrypt($number)` | `int\|string` | `int\|string` |
| `decrypt($number)` | `int\|string` | `int\|string` |
| `getBits()` | — | `int` |
| `getPartition()` | — | `int` |
| `getStrength()` | — | `int` |
| `getLookupTable()` | — | `array` |
| `setLookupTable($lookupTable)` | `array` | `void` |

#### 3.6 `DataPacker`

| 方法 | 参数类型 | 返回类型 |
|------|----------|----------|
| `__construct($serializer, $unserializer)` | `?callable`, `?callable` | — |
| `pack($dataObject)` | `mixed` | `string` |
| `unpack($data)` | `string` | `mixed` |
| `packToStream($dataObject)` | `mixed` | `void` |
| `unpackFromStream()` | — | `mixed` |
| `attachStream($stream)` | `mixed` | `void` |

---

### 4. `match` 表达式替代

#### 4.1 `AbstractDataProvider::getValidatorByLegacyString()`

原 `switch` 基于字符串常量匹配，改为基于 `DataType` enum 的 `match`：

```php
protected function getValidatorByLegacyString(DataType $type): ValidatorInterface
{
    return match ($type) {
        DataType::String         => new StringValidator(),
        DataType::NonEmptyString => new StringValidator(false, false),
        DataType::TrimmedString  => new TrimmedStringValidator(false),
        DataType::Int            => new IntegerValidator(),
        DataType::Float          => new FloatValidator(),
        DataType::Bool           => new BooleanValidator(),
        DataType::Object         => new ObjectValidator(),
        DataType::Mixed          => new DummyValidator(),
        DataType::Array2D        => new Array2DValidator(),
        DataType::Array          => new ArrayValidator(),
    };
}
```

由于 `DataType` enum 已穷举所有 case，`match` 无需 `default` 分支（PHP 会在未匹配时抛出 `\UnhandledMatchError`）。方法参数类型改为 `DataType`（调用方已确保非 `ValidatorInterface` 时传入 `DataType`）。

#### 4.2 `TrimmedStringValidator::validate()`

原 `switch` 基于字符串常量匹配 trim 方向，改为基于 `TrimDirection` enum 的 `match`：

```php
return match ($this->direction) {
    TrimDirection::Left  => \ltrim($target, $this->characters),
    TrimDirection::Right => \rtrim($target, $this->characters),
    TrimDirection::Both  => \trim($target, $this->characters),
};
```

#### 4.3 `CommonUtils::monitorMemoryUsage()`

原 `switch` 解析 memory limit 后缀（`g`/`m`/`k`），改为 `match`：

```php
$currentLimit = match ($last) {
    'g'     => (int)substr($currentLimit, 0, -1) * 1024 * 1024 * 1024,
    'm'     => (int)substr($currentLimit, 0, -1) * 1024 * 1024,
    'k'     => (int)substr($currentLimit, 0, -1) * 1024,
    default => (int)$currentLimit,
};
```

原 `switch` 无 `default` 分支（未匹配时 `$currentLimit` 保持原值），`match` 中补充 `default` 分支以保持等价语义。注意 `match` 使用 `===` 比较，此处比较对象为 `strtolower()` 返回的单字符字符串，语义一致。

---

### 5. 新字符串函数替代

#### 5.1 `StringUtils::stringStartsWith()`

```php
// Before
return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;

// After
return str_starts_with($haystack, $needle);
```

注意：原实现对空 `$needle` 返回 `true`，`str_starts_with()` 对空 `$needle` 同样返回 `true`，语义一致。

#### 5.2 `StringUtils::stringEndsWith()`

```php
// Before
return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);

// After
return str_ends_with($haystack, $needle);
```

#### 5.3 `AnsiColorizer::close()`

原实现使用 `StringUtils::stringEndsWith()`，改造后内部实现已使用 `str_ends_with()`，无需额外变更。可选择直接使用 `str_ends_with()` 替代 `StringUtils::stringEndsWith()` 调用以减少间接调用。

#### 5.4 其他 `strpos` 惯用法

扫描 `src/` 下所有文件，确认是否存在其他 `strpos($haystack, $needle) !== false` 或等价的子串检查惯用法，如有则替换为 `str_contains()`。

---

### 6. `AnsiColorizer` 改造细节

改造后的 `foreground()` / `background()` 方法逻辑：

```php
public static function foreground(string $text, AnsiColor|int $color): string
{
    if ($color instanceof AnsiColor) {
        // 亮色 case → bold 包裹基础色
        $baseColor = self::getBaseColor($color);
        if ($baseColor !== null) {
            return self::bold(self::foreground($text, $baseColor));
        }
        $code = 30 + $color->value;
    } else {
        // int → 256 色模式
        $code = "38;5;{$color}";
    }
    return self::close("\e[{$code}m{$text}");
}
```

辅助方法 `getBaseColor()` 将亮色 case 映射到对应基础色 case：

```php
private static function getBaseColor(AnsiColor $color): ?AnsiColor
{
    return match ($color) {
        AnsiColor::LightBlack   => AnsiColor::Black,
        AnsiColor::LightRed     => AnsiColor::Red,
        AnsiColor::LightGreen   => AnsiColor::Green,
        AnsiColor::LightYellow  => AnsiColor::Yellow,
        AnsiColor::LightBlue    => AnsiColor::Blue,
        AnsiColor::LightMagenta => AnsiColor::Magenta,
        AnsiColor::LightCyan    => AnsiColor::Cyan,
        AnsiColor::LightWhite   => AnsiColor::White,
        default                 => null,
    };
}
```

`background()` 方法逻辑类似，基础色码为 `40 + $color->value`，256 色码为 `48;5;N`。

注意：原 `background()` 方法中亮色分支存在 bug——调用了 `self::foreground()` 而非 `self::background()`。改造时保持原有行为不变（R9 不变性约束），不修复此 bug。

---

### 7. PHPDoc 清理

当类型声明与 PHPDoc 注解完全等价时，移除冗余的 `@param` 和 `@return` 标签。保留以下情况的 PHPDoc：
- 提供额外语义说明（如参数用途描述）
- 类型声明无法完全表达的泛型信息（如 `@param ValidatorInterface[] $validators`）
- 文件/类级别的文档注释

---

## Data Models

本次改造不引入新的数据模型。3 个 enum 类型是类型系统的增强，不涉及数据存储或传输格式变更。

### Enum 类型汇总

| Enum | 类型 | Case 数量 | 替代目标 |
|------|------|-----------|----------|
| `TrimDirection` | 纯 enum | 3 | `TrimmedStringValidator::TRIM_*` 常量 |
| `DataType` | string-backed | 10 | `DataProviderInterface::*_TYPE` 常量 |
| `AnsiColor` | int-backed | 16 | `AnsiColorizer::COLOR_*` 常量 + 字符串颜色名 |

### 类型常量迁移映射

#### `DataType` 映射

| 原常量 | Enum Case | Backed Value |
|--------|-----------|--------------|
| `DataProviderInterface::INT_TYPE` | `DataType::Int` | `'requireInt'` |
| `DataProviderInterface::FLOAT_TYPE` | `DataType::Float` | `'requireFloat'` |
| `DataProviderInterface::STRING_TYPE` | `DataType::String` | `'requireString'` |
| `DataProviderInterface::NON_EMPTY_STRING_TYPE` | `DataType::NonEmptyString` | `'requireNonEmptyString'` |
| `DataProviderInterface::TRIMMED_STRING_TYPE` | `DataType::TrimmedString` | `'requireTrimmedString'` |
| `DataProviderInterface::ARRAY_TYPE` | `DataType::Array` | `'requireArray'` |
| `DataProviderInterface::ARRAY_2D_TYPE` | `DataType::Array2D` | `'requireArray2D'` |
| `DataProviderInterface::BOOL_TYPE` | `DataType::Bool` | `'requireBool'` |
| `DataProviderInterface::OBJECT_TYPE` | `DataType::Object` | `'requireObject'` |
| `DataProviderInterface::MIXED_TYPE` | `DataType::Mixed` | `'requireMixed'` |

#### `TrimDirection` 映射

| 原常量 | Enum Case |
|--------|-----------|
| `TrimmedStringValidator::TRIM_BOTH` | `TrimDirection::Both` |
| `TrimmedStringValidator::TRIM_LEFT` | `TrimDirection::Left` |
| `TrimmedStringValidator::TRIM_RIGHT` | `TrimDirection::Right` |

#### `AnsiColor` 映射

| 原常量/字符串 | Enum Case | Backed Value |
|---------------|-----------|--------------|
| `COLOR_BLACK` / `'BLACK'` | `AnsiColor::Black` | `0` |
| `COLOR_RED` / `'RED'` | `AnsiColor::Red` | `1` |
| `COLOR_GREEN` / `'GREEN'` | `AnsiColor::Green` | `2` |
| `COLOR_YELLOW` / `'YELLOW'` | `AnsiColor::Yellow` | `3` |
| `COLOR_BLUE` / `'BLUE'` | `AnsiColor::Blue` | `4` |
| `COLOR_MAGENTA` / `'MAGENTA'` | `AnsiColor::Magenta` | `5` |
| `COLOR_CYAN` / `'CYAN'` | `AnsiColor::Cyan` | `6` |
| `COLOR_WHITE` / `'WHITE'` | `AnsiColor::White` | `7` |
| `'LIGHT-BLACK'` | `AnsiColor::LightBlack` | `100` |
| `'LIGHT-RED'` | `AnsiColor::LightRed` | `101` |
| `'LIGHT-GREEN'` | `AnsiColor::LightGreen` | `102` |
| `'LIGHT-YELLOW'` | `AnsiColor::LightYellow` | `103` |
| `'LIGHT-BLUE'` | `AnsiColor::LightBlue` | `104` |
| `'LIGHT-MAGENTA'` | `AnsiColor::LightMagenta` | `105` |
| `'LIGHT-CYAN'` | `AnsiColor::LightCyan` | `106` |
| `'LIGHT-WHITE'` | `AnsiColor::LightWhite` | `107` |


---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

本次改造的核心约束是**行为不变性**（R9）：语法现代化不改变任何运行时行为。这意味着最有价值的 property 是：对于任意合法输入，改造后的代码应产生与改造前完全相同的输出（或抛出相同的异常）。

### Property 1: Validator behavior preservation

*For any* validator instance (constructed with any valid combination of constructor arguments) and *for any* input value (valid, invalid, or edge-case types), the `validate()` method SHALL either return the same value as before modernization, or throw the same exception type with equivalent semantics.

**Validates: Requirements 3.4, 9.2**

> 覆盖所有 14 个 Validator 类。TrimmedStringValidator 使用 `TrimDirection` enum 替代字符串常量后，对任意字符串输入和任意 trim 方向，trim 结果应与直接调用 `ltrim`/`rtrim`/`trim` 一致。EnumerationValidator、ArrayValidator 等有条件构造逻辑的 validator 同样需要验证构造后行为不变。

### Property 2: DataProvider behavior preservation

*For any* nested array data, *for any* key path, and *for any* `DataType` enum case, `ArrayDataProvider::get()` and `ArrayDataProvider::has()` SHALL return the same result as using the equivalent old string constant through `getValidatorByLegacyString()`.

**Validates: Requirements 3.5, 9.5**

> 验证 `DataType` enum 到 Validator 的映射与原字符串常量映射完全等价，且 ArrayDataProvider 的路径解析和数据查找逻辑不变。

### Property 3: AnsiColorizer output correctness

*For any* non-empty text string and *for any* `AnsiColor` enum case, `foreground()` SHALL produce a string containing the correct ANSI escape code prefix and the `\e[0m` close tag suffix. For basic colors (Black–White), the escape code SHALL be `\e[{30+offset}m`. For light colors (LightBlack–LightWhite), the output SHALL be bold-wrapped with the corresponding basic color code. For `background()`, the same rules apply with base code 40.

**Validates: Requirements 3.6**

> 同时验证 `int` 参数路径（256 色模式）产生 `38;5;N` / `48;5;N` 格式的 ANSI 码。

### Property 4: StringUtils function equivalence

*For any* pair of strings `($haystack, $needle)`, `StringUtils::stringStartsWith($haystack, $needle)` SHALL return the same value as `str_starts_with($haystack, $needle)`, and `StringUtils::stringEndsWith($haystack, $needle)` SHALL return the same value as `str_ends_with($haystack, $needle)`.

**Validates: Requirements 5.1, 5.2**

> 改造后内部实现直接委托给 PHP 内置函数，此 property 验证包装方法与内置函数的行为完全一致。

### Property 5: CaesarCipher encrypt/decrypt round-trip

*For any* valid CaesarCipher configuration (even bits in (0,64], even partition dividing bits, strength ≥ 1) and *for any* integer within the bit range, `decrypt(encrypt(n))` SHALL equal `n`. For *any* binary string, `decrypt(encrypt(s))` SHALL equal `s` (when bits is divisible by 8).

**Validates: Requirements 9.3**

> 验证加解密算法逻辑在 constructor promotion + readonly + 类型声明改造后保持不变。

### Property 6: Rc4 symmetric round-trip

*For any* key string and *for any* input string, `Rc4::rc4($key, Rc4::rc4($key, $input))` SHALL equal `$input`.

**Validates: Requirements 9.3**

> RC4 是对称流密码，加密和解密使用同一方法。此 property 验证类型声明补齐后算法行为不变。

### Property 7: DataPacker pack/unpack round-trip

*For any* serializable PHP value, `unpack(pack($value))` SHALL return a value equal to `$value`.

**Validates: Requirements 9.4**

> 验证 DataPacker 在类型声明补齐后，长度前缀编码和序列化/反序列化行为不变。

---

## Error Handling

本次改造不变更任何错误处理逻辑（R9 约束）。以下为改造后需注意的错误处理变化点：

### 类型错误（TypeError）

类型声明补齐后，PHP 运行时会在参数类型不匹配时自动抛出 `\TypeError`。这是**新增的运行时行为**，但不违反 R9 约束——原代码对错误类型的输入行为是未定义的（可能产生意外结果或抛出其他异常），类型声明使错误更早、更明确地暴露。

| 场景 | 改造前行为 | 改造后行为 |
|------|-----------|-----------|
| `StringUtils::stringStartsWith(123, 'abc')` | 隐式类型转换或警告 | `\TypeError` |
| `DataProvider::get(123, ...)` | 可能正常执行 | `\TypeError`（`$key` 要求 `string`） |
| `CaesarCipher::encrypt(3.14)` | 抛出 `\InvalidArgumentException` | 抛出 `\TypeError`（`$number` 要求 `int\|string`） |

### Enum 类型约束

使用 enum 替代字符串/常量后，传入非 enum 值会触发 `\TypeError`：

| 场景 | 改造前行为 | 改造后行为 |
|------|-----------|-----------|
| `new TrimmedStringValidator(false, 'invalid')` | 运行时 `switch` 落入 `default` | `\TypeError`（`$direction` 要求 `TrimDirection`） |
| `$dp->get('key', 'requireInt')` | 正常执行（字符串映射） | `\TypeError`（`$validator` 要求 `ValidatorInterface\|DataType`） |
| `AnsiColorizer::foreground('text', 'RED')` | 正常执行（字符串查找） | `\TypeError`（`$color` 要求 `AnsiColor\|int`） |

### `match` 表达式

`match` 使用严格比较（`===`），且未匹配时抛出 `\UnhandledMatchError`。在本次改造中：

- `getValidatorByLegacyString()`：参数类型改为 `DataType`，enum 已穷举所有 case，不会触发 `UnhandledMatchError`
- `TrimmedStringValidator::validate()`：`TrimDirection` 已穷举，不会触发
- `CommonUtils::monitorMemoryUsage()`：保留 `default` 分支，不会触发

---

## Testing Strategy

### 测试框架与工具

| 项目 | 选型 |
|------|------|
| 单元测试框架 | PHPUnit 11.x（现有） |
| Property-based testing | 手动实现随机输入生成 + 循环断言（PHP 生态无成熟 PBT 库与 PHPUnit 深度集成） |
| 全量测试命令 | `php vendor/bin/phpunit` |

> **PBT 库选型说明**：PHP 生态中 PBT 库（如 `eris/eris`、`innmind/black-box`）成熟度和维护状态不如 Haskell/JS 生态。考虑到本项目不引入新依赖（R9 AC6），采用 PHPUnit 内手动实现随机输入生成 + 循环断言的方式实现 property-based testing 效果。每个 property test 运行 100+ 次迭代。

### 双轨测试策略

#### 现有测试（回归验证）

现有 `ut/` 下的测试用例是行为不变性的第一道防线。改造完成后全量运行，要求零失败、零 error、零 deprecation 警告。测试适配仅调整调用方式（如 enum case 替代旧常量），不新增用例或修改断言逻辑。

#### Property-based tests（新增）

针对 Correctness Properties 中定义的 7 个 property，新增 property-based test 文件。每个 property test：

- 运行最少 100 次迭代
- 使用随机输入生成器覆盖边界条件
- 标注对应的 design property 编号
- Tag 格式：`Feature: release-3.0.0, Property {N}: {property_text}`

#### 测试分工

| 测试类型 | 覆盖范围 | 目的 |
|----------|----------|------|
| 现有单元测试 | 具体示例、边界条件、错误路径 | 回归验证，确认改造未破坏已知行为 |
| Property tests | 随机输入的通用性质 | 验证行为不变性在广泛输入空间上成立 |
| 手动验证 | SSOT 文档、代码结构、composer.json | 确认非运行时约束 |

### Property Test 实现要点

| Property | 输入生成策略 | 关键断言 |
|----------|-------------|----------|
| P1: Validator preservation | 随机标量值（string/int/float/bool/null/array/object）× 各 Validator 构造参数组合 | `validate()` 返回值或异常类型一致 |
| P2: DataProvider preservation | 随机嵌套数组 × 随机 key path × 全部 DataType case | `get()`/`has()` 返回值一致 |
| P3: AnsiColorizer output | 随机字符串 × 全部 AnsiColor case + 随机 int(0–255) | 输出包含正确 ANSI 码和 close tag |
| P4: StringUtils equivalence | 随机字符串对 | 返回值与 PHP 内置函数一致 |
| P5: CaesarCipher round-trip | 随机 int（位范围内）+ 随机 binary string | `decrypt(encrypt(x)) === x` |
| P6: Rc4 round-trip | 随机 key + 随机 input | `rc4(key, rc4(key, input)) === input` |
| P7: DataPacker round-trip | 随机标量值和数组 | `unpack(pack(x)) === x` |

---

## Impact Analysis

### 受影响的 SSOT 文档

| 文件 | 受影响 Section | 变更内容 |
|------|---------------|----------|
| `docs/state/validators.md` | 接口、验证器清单（全部） | 补充类型声明、constructor promotion 后的构造参数格式、`TrimDirection` enum 替代 `TRIM_*` 常量 |
| `docs/state/data-provider.md` | 类型常量、方法签名、行为规则 | `DataType` enum 替代 10 个类型常量、方法参数类型和默认值变更 |
| `docs/state/crypto.md` | 构造参数、公开方法 | constructor promotion + readonly 标注、类型声明补齐 |
| `docs/state/utils.md` | StringUtils、CommonUtils、AnsiColorizer、DataPacker | 类型声明补齐、`str_starts_with`/`str_ends_with` 替代说明、`AnsiColor` enum 替代颜色名/常量、`match` 替代 `switch` |
| `docs/state/exceptions.md` | DataValidationException | 类型声明补齐（`?\Throwable`）、方法返回类型 |

### 现有行为变化

- **公共 API 签名变更（breaking change）**：类型声明补齐和 enum 替代常量导致调用方必须适配新签名。传入旧类型（字符串常量、无类型参数）将触发 `\TypeError`。
- **验证逻辑不变**：所有 Validator 的验证规则、异常抛出条件、返回值语义均不变。
- **加解密算法不变**：CaesarCipher 和 Rc4 的算法逻辑不变。
- **DataProvider 路径解析不变**：ArrayDataProvider 的点号路径解析策略不变。

### 数据模型变更

不涉及。本次改造不改变任何数据存储或传输格式。

### 外部系统交互

不涉及。本项目为独立工具库，无外部系统依赖。

### 配置项变更

不涉及。`composer.json` 不新增或移除依赖，不变更 PHP 版本约束。

---

## Convergence Plan

### SSOT 更新方案

改造完成后，按以下顺序更新 `docs/state/` 下的文档：

1. **`docs/state/validators.md`**：更新 `ValidatorInterface` 接口签名（`mixed` 类型）；各 Validator 构造参数表格标注 `readonly`、类型声明；`TrimmedStringValidator` 的 `$direction` 参数改为 `TrimDirection` enum；新增 `TrimDirection` enum 说明
2. **`docs/state/data-provider.md`**：移除类型常量表格，替换为 `DataType` enum 说明；更新方法签名表格（参数类型、默认值）；更新行为规则中的字符串映射描述
3. **`docs/state/crypto.md`**：更新构造参数表格标注 `readonly`；更新方法签名表格（类型声明）
4. **`docs/state/utils.md`**：更新 StringUtils 方法签名（类型声明）；更新 AnsiColorizer 颜色支持说明（`AnsiColor` enum 替代字符串/常量）；更新 CommonUtils 方法签名；更新 DataPacker 方法签名
5. **`docs/state/exceptions.md`**：更新 DataValidationException 方法签名（`?\Throwable`、返回类型）

### 版本号更新

- `composer.json` 中 `version` 字段（如存在）更新为 `3.0.0`

### 归档步骤

Release 合并并打 tag 后：
1. 将 `.kiro/specs/release-3.0.0/` 归档至 `docs/changes/3.0.0/specs/release-3.0.0/`
2. 创建 `docs/changes/3.0.0/CHANGELOG.md` 记录本次 release 的变更摘要

---

## Socratic Review

### Q1: 为什么 `TrimDirection` 选择纯 enum 而非 string-backed enum？

原常量值（`'both'`、`'left'`、`'right'`）仅在 `validate()` 内部的 `switch` 分支中使用，不对外暴露。改为 `match` 后直接匹配 enum case，无需 backed value。纯 enum 更简洁，且避免暴露无意义的字符串值。

### Q2: 为什么 `AnsiColor` 使用 int-backed 而非纯 enum？

基础 8 色的 backed value（0–7）直接用于 ANSI 码计算（`30 + $color->value` / `40 + $color->value`），避免额外的映射表。亮色变体的 backed value（100–107）仅作区分标识，不直接参与 ANSI 码计算。

### Q3: `DataPacker` 为什么不使用 constructor promotion？

`DataPacker` 构造函数中 `$serializer` 和 `$unserializer` 参数有 callable 回退逻辑：先检查传入值是否 callable，再检查 igbinary 扩展是否可用，最后回退到 PHP 内置 `serialize`/`unserialize`。这种条件赋值逻辑不能用 constructor promotion 表达。

### Q4: `ChainedValidator` 为什么不使用 constructor promotion？

`ChainedValidator` 使用 variadic 参数 `...$args`，构造函数中有类型校验循环。PHP 不支持对 variadic 参数使用 constructor promotion。

### Q5: `AnsiColorizer::background()` 的亮色分支 bug 是否应在本次改造中修复？

不修复。原代码中 `background()` 的亮色分支错误地调用了 `self::foreground()` 而非 `self::background()`。R9 明确要求不修改业务逻辑，修复此 bug 属于行为变更。应记录为 issue 在后续版本修复。

### Q6: `match` 的严格比较（`===`）是否会引入行为变更？

不会。三处 `switch` 替换中：
- `getValidatorByLegacyString()`：参数类型改为 `DataType` enum，`match` 直接匹配 enum case，无类型转换问题
- `TrimmedStringValidator::validate()`：匹配 `TrimDirection` enum case，同上
- `CommonUtils::monitorMemoryUsage()`：匹配 `strtolower()` 返回的单字符字符串，`===` 和 `==` 对字符串比较行为一致

### Q7: 类型声明补齐是否可能导致现有调用方出现 TypeError？

是的，这是预期的 breaking change（v3.0.0 major bump）。例如：
- 原来传入字符串类型常量（如 `'requireInt'`）的调用方需改为 `DataType::Int`
- 原来传入字符串颜色名（如 `'RED'`）的调用方需改为 `AnsiColor::Red`
- 原来传入 `TrimmedStringValidator::TRIM_LEFT` 的调用方需改为 `TrimDirection::Left`

这些变更在 R6 中通过测试适配覆盖。

### Q8: Property 5 和 Property 6 是否冗余？

不冗余。CaesarCipher 和 Rc4 是完全不同的加密算法，有不同的构造参数和内部逻辑。CaesarCipher 涉及 constructor promotion + readonly 改造，Rc4 仅涉及类型声明补齐。分开测试确保各自的改造不引入问题。

### Q9: `DataValidationException::$previous` 从 `?\Exception` 改为 `?\Throwable` 是否违反 R9？

不违反。`\Throwable` 是 `\Exception` 的父接口，参数类型从子类型放宽为父类型是协变兼容的（Liskov 替换原则）。所有原来传入 `\Exception` 的调用仍然有效。这是类型声明的增强，不改变行为语义。

### Q10: `DataProviderInterface` 方法默认值变更是否需要在 design 中明确？

需要。原代码中 `has()` 默认 `self::MIXED_TYPE`、`get()`/`getMandatory()`/`getOptional()` 默认 `self::STRING_TYPE`。移除常量后，默认值必须改为对应的 `DataType` enum case（`DataType::Mixed` 和 `DataType::String`）。这是 enum 替代常量的直接后果，已在 Section 1.2 中补充说明。

### Q11: design 是否完整覆盖了 R7（SSOT 更新）和 R8（Release 分支全量验证）？

R7 通过 Impact Analysis 和 Convergence Plan 覆盖——列出了每个 state 文件的具体更新内容和顺序。R8 通过 Testing Strategy（全量测试）和 Convergence Plan（归档步骤）覆盖。


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 补充 `## Impact Analysis` section——原文档缺少影响分析，已补充受影响的 SSOT 文档条目、行为变化、数据模型/外部系统/配置项评估
- [结构] 补充 `## Convergence Plan` section——release spec 要求收敛计划，已补充 SSOT 更新方案（具体文件和更新内容）、版本号更新、归档步骤
- [内容] Section 1.2 补充 `DataProviderInterface` 方法默认值变更说明——原文档仅提及参数类型改为 `ValidatorInterface|DataType`，未说明 `has()` 默认值从 `self::MIXED_TYPE` 改为 `DataType::Mixed`、`get()`/`getMandatory()`/`getOptional()` 默认值从 `self::STRING_TYPE` 改为 `DataType::String`
- [内容] Socratic Review 补充 Q10（默认值变更必要性）和 Q11（R7/R8 覆盖度确认）

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirements 编号、术语引用）
- [x] 代码块语法正确（语言标注、闭合）
- [x] 无 markdown 格式错误
- [x] 一级标题存在（`# Design Document: Release 3.0.0`）
- [x] 技术方案主体存在，承接 requirements 中的需求（Components and Interfaces 覆盖 R1–R5）
- [x] 接口签名 / 数据模型有明确定义（Section 3 类型声明补齐、Section 1 Enum 定义、Data Models）
- [x] 各 section 之间使用 `---` 分隔
- [x] R1（Constructor Promotion）→ Section 2 改造清单覆盖
- [x] R2（Union/Intersection Types）→ Section 3 类型声明补齐覆盖
- [x] R3（Enum）→ Section 1 Enum 定义覆盖
- [x] R4（Match）→ Section 4 match 表达式替代覆盖
- [x] R5（新字符串函数）→ Section 5 覆盖
- [x] R6（测试适配）→ Testing Strategy 覆盖
- [x] R7（SSOT 更新）→ Impact Analysis + Convergence Plan 覆盖
- [x] R8（Release 分支全量验证）→ Testing Strategy + Convergence Plan 覆盖
- [x] R9（功能与行为不变性）→ Correctness Properties + Error Handling 覆盖
- [x] Impact Analysis 覆盖：受影响 state 文档、行为变化、数据模型、外部系统、配置项
- [x] 技术选型有明确理由（Socratic Review Q1–Q4）
- [x] 接口签名足够清晰，能让 task 独立执行
- [x] 无过度设计
- [x] 与 state 文档中描述的现有架构一致
- [x] Socratic Review 覆盖充分（11 个 Q&A）
- [x] Requirements CR 已回答的决策在 design 中体现（Q1→16 case enum, Q2→ValidatorInterface|DataType, Q3→readonly 仅直接赋值）

### Clarification Round

**状态**: ✅ 已确认

**Q1:** Design 中 R1–R5 的改造涉及多个模块的交叉变更（如 enum 创建影响 Validator、DataProvider、AnsiColorizer 三个模块）。拆分 task 时，优先按**改造方向**（先做所有 enum，再做所有 constructor promotion，再做类型声明）还是按**模块**（先完成 Validators 全部改造，再完成 DataProvider 全部改造）？
- A) 按改造方向拆分——每个 task 对应一个改造方向（如 Task: Enum 创建、Task: Constructor Promotion、Task: 类型声明补齐），跨模块执行
- B) 按模块拆分——每个 task 对应一个模块或模块组（如 Task: Validators 改造、Task: DataProvider 改造），在单个 task 内完成该模块的所有改造方向
- C) 混合策略——先创建 enum 文件（基础设施），再按模块完成剩余改造
- D) 其他（请说明）

**A:** B——按模块拆分，每个 task 对应一个模块组，在单个 task 内完成该模块的所有改造方向。

**Q2:** Property-based tests（7 个 property）应作为独立 task 还是合并到各模块改造 task 中？独立 task 意味着所有模块改造完成后统一编写 property tests；合并意味着每个模块改造 task 同时编写对应的 property test。
- A) 独立 task——所有模块改造完成后，单独一个 task 编写全部 7 个 property tests
- B) 合并到模块改造 task——每个改造 task 同时编写对应的 property test
- C) 分两个独立 task——一个 task 编写 P1–P4（Validator/DataProvider/AnsiColorizer/StringUtils），另一个编写 P5–P7（Crypto/DataPacker）
- D) 其他（请说明）

**A:** A——独立 task，所有模块改造完成后单独编写全部 7 个 property tests。

**Q3:** SSOT 更新（R7）和测试适配（R6）应在什么时机执行？选项涉及是否在每个模块改造 task 中同步更新 SSOT 和测试，还是在所有改造完成后统一处理。
- A) 同步更新——每个改造 task 完成后立即更新对应的 SSOT 文件和测试文件
- B) 统一后置——所有改造 task 完成后，单独 task 统一更新 SSOT 和适配测试
- C) 测试同步 + SSOT 后置——测试适配随改造 task 同步进行（确保每步可验证），SSOT 更新在最后统一执行
- D) 其他（请说明）

**A:** B——统一后置，所有改造完成后单独 task 统一更新 SSOT 和适配测试。

**Q4:** `AnsiColorizer::background()` 的亮色分支 bug（调用了 `self::foreground()` 而非 `self::background()`）在 design 中明确标注为不修复（R9 约束）。是否需要在本次 release 的 task 中创建一个 issue 记录此 bug，以便后续版本跟踪？
- A) 是，在改造 task 中顺便创建 `issues/` 下的 issue 文件
- B) 是，但作为独立的收尾 task
- C) 不需要，在 release notes / CHANGELOG 中提及即可
- D) 其他（请说明）

**A:** D——直接做一个 task 修复此 bug（不受 R9 约束，作为 v3.0.0 的额外修复项）。
