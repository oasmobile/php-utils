# Data Provider 使用指南

本文件说明如何使用 DataProvider 从数组中安全地读取和校验数据。

---

## 快速开始

```php
use Oasis\Mlib\Utils\ArrayDataProvider;
use Oasis\Mlib\Utils\DataType;

$dp = new ArrayDataProvider([
    'name'  => 'Alice',
    'age'   => '25',
    'email' => 'alice@example.com',
]);

// 读取必填字段（不存在则抛异常）
$name = $dp->getMandatory('name');                              // "Alice"

// 读取可选字段（不存在返回默认值）
$nickname = $dp->getOptional('nickname', DataType::String, 'Anonymous');

// 指定类型校验（宽松模式下字符串 "25" 自动转为 int）
$age = $dp->getMandatory('age', DataType::Int);                // 25
```

---

## DataType 枚举速查

v3.0.0 起，类型常量由 `DataType` enum 替代。

| Enum Case | 用途 | 宽松模式行为 |
|-----------|------|-------------|
| `DataType::String` | 字符串（默认） | 标量自动转字符串 |
| `DataType::NonEmptyString` | 非空字符串 | 同上，空串报错 |
| `DataType::TrimmedString` | 去首尾空白 | 同上，返回 trim 后结果 |
| `DataType::Int` | 整数 | `"25"` → `25` |
| `DataType::Float` | 浮点数 | `"3.14"` → `3.14` |
| `DataType::Bool` | 布尔 | `"true"` / `"1"` / `"yes"` → `true` |
| `DataType::Array` | 数组 | — |
| `DataType::Array2D` | 二维数组 | — |
| `DataType::Object` | 对象 | `null` 视为合法 |
| `DataType::Mixed` | 任意类型 | 不做校验 |

---

## 使用自定义 Validator

除了类型常量，也可以直接传入 `ValidatorInterface` 实例：

```php
use Oasis\Mlib\Utils\Validators\StringValidator;
use Oasis\Mlib\Utils\Validators\StringLengthValidator;
use Oasis\Mlib\Utils\Validators\EmailValidator;
use Oasis\Mlib\Utils\Validators\ChainedValidator;

// 单个验证器
$email = $dp->getMandatory('email', new EmailValidator());

// 链式验证器：先验证是字符串，再验证长度
$title = $dp->getMandatory('title', new ChainedValidator(
    new StringValidator(),
    new StringLengthValidator(100, 1)
));
```

---

## 嵌套数据访问

`ArrayDataProvider` 支持点号路径访问嵌套数组：

```php
$dp = new ArrayDataProvider([
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
    ],
]);

$host = $dp->getMandatory('database.host');                  // "localhost"
$port = $dp->getMandatory('database.port', DataType::Int);  // 3306
```

### 路径导航

```php
// 进入子路径
$dp->pushPath('database');
$host = $dp->getMandatory('host');    // 相对于 database 查找
$dp->popPath();

// 直接设置路径
$dp->setCurrentPath('database');
$port = $dp->getMandatory('port', DataType::Int);
$dp->setCurrentPath('');              // 回到根
```

### 自定义分隔符

```php
$dp->setPathDelimiter('/');
$host = $dp->getMandatory('database/host');
```

---

## 检查字段是否存在

```php
if ($dp->has('email')) {
    // key 存在且值非 null
}

// 带类型检查：key 存在且值能通过 DataType::Int 验证
if ($dp->has('age', DataType::Int)) {
    // ...
}
```

---

## 异常处理

```php
use Oasis\Mlib\Utils\Exceptions\MandatoryValueMissingException;
use Oasis\Mlib\Utils\Exceptions\InvalidDataTypeException;

try {
    $value = $dp->getMandatory('missing_key');
} catch (MandatoryValueMissingException $e) {
    echo $e->getFieldName(); // "missing_key"
}

try {
    $value = $dp->getMandatory('name', DataType::Int);
} catch (InvalidDataTypeException $e) {
    echo $e->getFieldName(); // "name"
    echo $e->getMessage();   // "Validated data is not integer!"
}
```
