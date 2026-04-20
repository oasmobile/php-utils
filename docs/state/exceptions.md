# Exceptions

本文件描述异常体系的当前结构与行为规则。

---

## 继承树

```
\RuntimeException
└── DataValidationException
    ├── MandatoryValueMissingException
    ├── DataEmptyException
    ├── InvalidDataTypeException
    │   └── InvalidArrayElementException
    ├── InvalidValueException
    ├── ExistenceViolationException
    ├── UniquenessViolationException
    ├── RegexNotMatchedException
    ├── StringTooLongException
    └── StringTooShortException
```

---

## DataValidationException

所有数据验证异常的基类。

| 方法 | 说明 |
|------|------|
| `static create($message, $code, $previous)` | 工厂方法 |
| `getFieldName(): string` | 获取关联字段名 |
| `setFieldName($fieldName)` | 设置关联字段名 |
| `withFieldName($fieldName): self` | 链式设置字段名 |

---

## 各异常用途

| 异常类 | 抛出场景 |
|--------|----------|
| `MandatoryValueMissingException` | DataProvider 中 mandatory 字段不存在 |
| `DataEmptyException` | StringValidator 不允许空字符串时值为空 |
| `InvalidDataTypeException` | 值的类型不符合验证器要求 |
| `InvalidArrayElementException` | 数组中某元素验证失败 |
| `InvalidValueException` | 值不在枚举列表中 |
| `ExistenceViolationException` | 存在性约束违反（通用） |
| `UniquenessViolationException` | 唯一性约束违反（通用） |
| `RegexNotMatchedException` | 正则验证不匹配 |
| `StringTooLongException` | 字符串超过最大长度 |
| `StringTooShortException` | 字符串短于最小长度 |
