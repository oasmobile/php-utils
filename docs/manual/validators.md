# Validators 使用指南

本文件说明如何独立使用验证器，以及如何组合验证器实现复杂校验逻辑。

---

## 基本用法

每个验证器实现 `ValidatorInterface`，调用 `validate($target)` 返回校验后的值（可能经过类型转换或 trim），失败抛出异常。

```php
use Oasis\Mlib\Utils\Validators\IntegerValidator;

$validator = new IntegerValidator();
$result = $validator->validate("42");  // 返回 int 42（宽松模式）
$result = $validator->validate("abc"); // 抛出 InvalidDataTypeException
```

---

## 常用验证器示例

### 字符串长度限制

```php
use Oasis\Mlib\Utils\Validators\StringLengthValidator;

// 最大 50 字符，最小 1 字符
$v = new StringLengthValidator(50, 1);
$v->validate("hello");   // "hello"
$v->validate("");         // 抛出 StringTooShortException

// 超长自动截断（不抛异常）
$v = new StringLengthValidator(10, 0, true);
$v->validate("a]very long string here"); // 返回前 10 个字符
```

### Email 验证

```php
use Oasis\Mlib\Utils\Validators\EmailValidator;

$v = new EmailValidator();
$v->validate("user@example.com");  // "user@example.com"
$v->validate("not-an-email");      // 抛出 InvalidDataTypeException
$v->validate("user#tag@test.com"); // 抛出（禁止 # 字符）
```

### URL 验证

```php
use Oasis\Mlib\Utils\Validators\UrlValidator;

$v = new UrlValidator();
$v->validate("https://example.com/path"); // 通过
$v->validate("not a url");                // 抛出 InvalidDataTypeException
```

### 正则验证

```php
use Oasis\Mlib\Utils\Validators\RegexValidator;

$v = new RegexValidator('/^\d{4}-\d{2}-\d{2}$/');
$v->validate("2024-01-15"); // "2024-01-15"
$v->validate("Jan 15");     // 抛出 RegexNotMatchedException
```

### 枚举验证

```php
use Oasis\Mlib\Utils\Validators\EnumerationValidator;

// 大小写敏感（默认）
$v = new EnumerationValidator(['active', 'inactive', 'pending']);
$v->validate("active");   // "active"
$v->validate("Active");   // 抛出 InvalidValueException

// 大小写不敏感
$v = new EnumerationValidator(['active', 'inactive'], false, false);
$v->validate("ACTIVE");   // 返回 "ACTIVE"（保留原始大小写）
```

### 布尔验证（宽松模式）

```php
use Oasis\Mlib\Utils\Validators\BooleanValidator;

$v = new BooleanValidator();
$v->validate("yes");  // true
$v->validate("off");  // false
$v->validate("0");    // false
$v->validate("maybe"); // 抛出 InvalidDataTypeException
```

### 数组验证（带元素校验）

```php
use Oasis\Mlib\Utils\Validators\ArrayValidator;
use Oasis\Mlib\Utils\Validators\IntegerValidator;

// 验证数组中每个元素都是整数
$v = new ArrayValidator(false, new IntegerValidator());
$v->validate([1, "2", 3]);    // [1, 2, 3]（元素被转换）
$v->validate([1, "abc", 3]);  // 抛出 InvalidArrayElementException
```

---

## 链式组合

`ChainedValidator` 将多个验证器串联，前一个的输出作为后一个的输入：

```php
use Oasis\Mlib\Utils\Validators\ChainedValidator;
use Oasis\Mlib\Utils\Validators\TrimmedStringValidator;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\RegexValidator;

// 先 trim → 再检查长度 → 再匹配正则
$v = new ChainedValidator(
    new TrimmedStringValidator(),
    new StringLengthValidator(20, 3),
    new RegexValidator('/^[a-z0-9_]+$/')
);

$v->validate("  hello_world  "); // "hello_world"
$v->validate("  ab  ");          // 抛出 StringTooShortException（trim 后只有 2 字符）
```

---

## Strict 模式 vs 宽松模式

大多数验证器默认为宽松模式（`$strict = false`），会尝试类型转换。设置 `$strict = true` 则只接受精确类型：

```php
$v = new IntegerValidator(true);
$v->validate(42);    // 42
$v->validate("42");  // 抛出 InvalidDataTypeException（strict 不转换）
```

适用场景：
- **宽松模式**：处理外部输入（HTTP 参数、配置文件）时推荐
- **Strict 模式**：内部数据传递、需要严格类型保证时使用
