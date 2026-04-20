# Data Provider 使用指南

本文件说明如何使用 DataProvider 从数组中安全地读取和校验数据。

---

## 快速开始

```php
use Oasis\Mlib\Utils\ArrayDataProvider;

$dp = new ArrayDataProvider([
    'name'  => 'Alice',
    'age'   => '25',
    'email' => 'alice@example.com',
]);

// 读取必填字段（不存在则抛异常）
$name = $dp->getMandatory('name');                          // "Alice"

// 读取可选字段（不存在返回默认值）
$nickname = $dp->getOptional('nickname', $dp::STRING_TYPE, 'Anonymous');

// 指定类型校验（宽松模式下字符串 "25" 自动转为 int）
$age = $dp->getMandatory('age', $dp::INT_TYPE);            // 25
```

---

## 类型常量速查

| 常量 | 用途 | 宽松模式行为 |
|------|------|-------------|
| `STRING_TYPE` | 字符串（默认） | 标量自动转字符串 |
| `NON_EMPTY_STRING_TYPE` | 非空字符串 | 同上，空串报错 |
| `TRIMMED_STRING_TYPE` | 去首尾空白 | 同上，返回 trim 后结果 |
| `INT_TYPE` | 整数 | `"25"` → `25` |
| `FLOAT_TYPE` | 浮点数 | `"3.14"` → `3.14` |
| `BOOL_TYPE` | 布尔 | `"true"` / `"1"` / `"yes"` → `true` |
| `ARRAY_TYPE` | 数组 | — |
| `ARRAY_2D_TYPE` | 二维数组 | — |
| `OBJECT_TYPE` | 对象 | `null` 视为合法 |
| `MIXED_TYPE` | 任意类型 | 不做校验 |

---

## 使用自定义 Validator

除了类型常量，也可以直接传入 `ValidatorInterface` 实例：

```php
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

$host = $dp->getMandatory('database.host');                // "localhost"
$port = $dp->getMandatory('database.port', $dp::INT_TYPE); // 3306
```

### 路径导航

```php
// 进入子路径
$dp->pushPath('database');
$host = $dp->getMandatory('host');    // 相对于 database 查找
$dp->popPath();

// 直接设置路径
$dp->setCurrentPath('database');
$port = $dp->getMandatory('port', $dp::INT_TYPE);
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

// 带类型检查：key 存在且值能通过 INT_TYPE 验证
if ($dp->has('age', $dp::INT_TYPE)) {
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
    $value = $dp->getMandatory('name', $dp::INT_TYPE);
} catch (InvalidDataTypeException $e) {
    echo $e->getFieldName(); // "name"
    echo $e->getMessage();   // "Validated data is not integer!"
}
```
