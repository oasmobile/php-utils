# Migration Guide: 1.x → ^3.0

从 `oasis/utils` 1.x 升级到 ^3.0 的迁移指南。

---

## 前置条件

| 项目 | 1.x | ^3.0 |
|------|-----|------|
| PHP | 7.x | >=8.5 |
| PHPUnit | ^5.1 | ^13.0 |
| `voku/portable-utf8` | ^3.0 | ^3.0（不变） |
| `psr/log` | — | ^3.0（新增） |

升级前确保运行环境为 PHP 8.5+。

```bash
composer require oasis/utils:^3.0
```

---

## Breaking Changes 总览

^3.0 包含两个 major 版本的累积变更（2.0.0 + 3.0.0），以下按影响范围分类。

### 1. 类型常量 → Enum

这是最大的 breaking change。三组类常量被替换为原生 PHP enum。

#### DataType enum（替代 `DataProviderInterface` 类型常量）

```php
// 1.x
$dp->get('key', DataProviderInterface::REQUIRE_INT);
$dp->getMandatory('name', DataProviderInterface::REQUIRE_STRING);

// ^3.0
use Oasis\Mlib\Utils\DataType;

$dp->get('key', DataType::Int);
$dp->getMandatory('name', DataType::String);
```

完整映射：

| 1.x 常量 | ^3.0 Enum Case |
|-----------|----------------|
| `DataProviderInterface::REQUIRE_INT` | `DataType::Int` |
| `DataProviderInterface::REQUIRE_FLOAT` | `DataType::Float` |
| `DataProviderInterface::REQUIRE_STRING` | `DataType::String` |
| `DataProviderInterface::REQUIRE_NON_EMPTY_STRING` | `DataType::NonEmptyString` |
| `DataProviderInterface::REQUIRE_TRIMMED_STRING` | `DataType::TrimmedString` |
| `DataProviderInterface::REQUIRE_ARRAY` | `DataType::Array` |
| `DataProviderInterface::REQUIRE_ARRAY_2D` | `DataType::Array2D` |
| `DataProviderInterface::REQUIRE_BOOL` | `DataType::Bool` |
| `DataProviderInterface::REQUIRE_OBJECT` | `DataType::Object` |
| `DataProviderInterface::REQUIRE_MIXED` | `DataType::Mixed` |

#### TrimDirection enum（替代 `TrimmedStringValidator` 常量）

```php
// 1.x
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;

new TrimmedStringValidator(false, TrimmedStringValidator::TRIM_LEFT);

// ^3.0
use Oasis\Mlib\Utils\TrimDirection;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;

new TrimmedStringValidator(false, TrimDirection::Left);
```

| 1.x 常量 | ^3.0 Enum Case |
|-----------|----------------|
| `TrimmedStringValidator::TRIM_BOTH` | `TrimDirection::Both` |
| `TrimmedStringValidator::TRIM_LEFT` | `TrimDirection::Left` |
| `TrimmedStringValidator::TRIM_RIGHT` | `TrimDirection::Right` |

#### AnsiColor enum（替代 `AnsiColorizer` 颜色常量/字符串）

```php
// 1.x
AnsiColorizer::foreground('text', AnsiColorizer::COLOR_RED);
AnsiColorizer::foreground('text', 'red');

// ^3.0
use Oasis\Mlib\Utils\AnsiColor;

AnsiColorizer::foreground('text', AnsiColor::Red);
```

`AnsiColorizer` 不再接受字符串颜色名，参数类型为 `AnsiColor|int`。

---

### 2. 方法签名类型声明

所有公开/受保护方法补齐了原生类型声明。1.x 中传入错误类型时可能静默处理或产生 PHP warning，^3.0 中会直接触发 `\TypeError`。

主要影响：

| 接口/类 | 变更 |
|---------|------|
| `ValidatorInterface::validate()` | 签名变为 `validate(mixed $target): mixed` |
| `DataProviderInterface` 方法 | `$validator` 参数类型为 `ValidatorInterface\|DataType` |
| `DataValidationException` | `$previous` 参数类型从 `?\Exception` 改为 `?\Throwable` |

如果你的代码继承了这些接口/类，子类签名必须兼容新的类型声明。

---

### 3. Constructor Promotion 与 Readonly

以下类的构造参数改为 readonly promoted properties：

`StringValidator`、`TrimmedStringValidator`、`IntegerValidator`、`FloatValidator`、`BooleanValidator`、`ArrayValidator`、`ObjectValidator`、`EnumerationValidator`、`StringLengthValidator`、`RegexValidator`、`CaesarCipher`、`ArrayDataProvider`

影响：

- 构造后不可再修改这些属性（`readonly`）
- 如果 1.x 代码中有在构造后修改这些属性的逻辑，需要调整

---

### 4. PHPUnit 升级（2.0.0 引入）

如果你的测试代码依赖本库的测试基础设施：

```php
// 1.x
class MyTest extends PHPUnit_Framework_TestCase { ... }

// ^3.0
class MyTest extends \PHPUnit\Framework\TestCase { ... }
```

- Data provider 方法需要添加 `static` 关键字
- `setUp()` / `tearDown()` 需要添加 `: void` 返回类型

---

### 5. Strict Types（3.0.1 引入）

所有源文件添加了 `declare(strict_types=1)`。这意味着库内部的类型强制转换更严格，但对外部调用者的影响主要体现在：传入类型不匹配时更容易触发 `\TypeError`。

---

### 6. CommonUtils 日志方式变更（3.1.0 引入）

`CommonUtils::monitorMemoryUsage()` 不再使用 `fprintf(STDERR)` 输出内存调整信息，改为 PSR-3 Logger 静态注入。

```php
// 3.0.x：内存调整时自动输出到 stderr
CommonUtils::monitorMemoryUsage();

// ^3.1.0：默认静默，需手动注入 logger
use Psr\Log\LoggerInterface;

CommonUtils::setLogger($myLogger); // 注入后才有日志输出
CommonUtils::monitorMemoryUsage();
```

如果你的代码依赖 stderr 输出来监控内存调整，需要改为注入一个写 stderr 的 PSR-3 Logger。

新增运行时依赖 `psr/log` ^3.0。

---

## 迁移步骤

### 快速查找替换

大部分迁移可以通过全局搜索替换完成：

```
# DataType 常量
DataProviderInterface::REQUIRE_INT          → DataType::Int
DataProviderInterface::REQUIRE_FLOAT        → DataType::Float
DataProviderInterface::REQUIRE_STRING       → DataType::String
DataProviderInterface::REQUIRE_NON_EMPTY_STRING → DataType::NonEmptyString
DataProviderInterface::REQUIRE_TRIMMED_STRING   → DataType::TrimmedString
DataProviderInterface::REQUIRE_ARRAY        → DataType::Array
DataProviderInterface::REQUIRE_ARRAY_2D     → DataType::Array2D
DataProviderInterface::REQUIRE_BOOL         → DataType::Bool
DataProviderInterface::REQUIRE_OBJECT       → DataType::Object
DataProviderInterface::REQUIRE_MIXED        → DataType::Mixed

# TrimDirection 常量
TrimmedStringValidator::TRIM_BOTH  → TrimDirection::Both
TrimmedStringValidator::TRIM_LEFT  → TrimDirection::Left
TrimmedStringValidator::TRIM_RIGHT → TrimDirection::Right

# AnsiColor 常量
AnsiColorizer::COLOR_BLACK   → AnsiColor::Black
AnsiColorizer::COLOR_RED     → AnsiColor::Red
AnsiColorizer::COLOR_GREEN   → AnsiColor::Green
AnsiColorizer::COLOR_YELLOW  → AnsiColor::Yellow
AnsiColorizer::COLOR_BLUE    → AnsiColor::Blue
AnsiColorizer::COLOR_MAGENTA → AnsiColor::Magenta
AnsiColorizer::COLOR_CYAN    → AnsiColor::Cyan
AnsiColorizer::COLOR_WHITE   → AnsiColor::White
```

### 添加 use 语句

替换后需要在相关文件顶部添加 enum 的 `use` 语句：

```php
use Oasis\Mlib\Utils\DataType;
use Oasis\Mlib\Utils\TrimDirection;
use Oasis\Mlib\Utils\AnsiColor;
```

### 检查子类签名

如果你的代码实现了 `ValidatorInterface` 或 `DataProviderInterface`，确保方法签名与新的类型声明兼容。

### 检查 readonly 属性访问

如果你的代码在构造后修改了 validator 或 `CaesarCipher` 的属性，需要改为在构造时传入正确的值。

---

## Bug 修复（附带）

- `AnsiColorizer::background()` 亮色分支修复：1.x 中亮色背景错误调用了 `foreground()`，^3.0 已修正
- `StringValidator` / `TrimmedStringValidator`：`method_exists($target, '__toString()')` 修复为 `method_exists($target, '__toString')`（2.0.0 引入）

---

## 行为不变性

除上述 bug 修复外，^3.0 的所有变更为纯语法层面重构，不变更业务逻辑。验证规则、数据读取行为、加密算法结果均与 1.x 保持一致。
