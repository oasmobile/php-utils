# Data Provider

本文件描述 DataProvider 子系统的当前接口与行为规则。

---

## 接口层次

```
DataProviderInterface
├── AbstractDataProvider (abstract)
│   └── ArrayDataProvider
└── HierarchicalDataProviderInterface (extends DataProviderInterface)
    └── ArrayDataProvider
```

---

## DataProviderInterface

统一的键值数据读取接口，支持类型校验。

### 类型常量

| 常量 | 值 | 对应 Validator |
|------|-----|----------------|
| `INT_TYPE` | `"requireInt"` | `IntegerValidator` |
| `FLOAT_TYPE` | `"requireFloat"` | `FloatValidator` |
| `STRING_TYPE` | `"requireString"` | `StringValidator`（默认宽松模式） |
| `NON_EMPTY_STRING_TYPE` | `"requireNonEmptyString"` | `StringValidator(false, false)` |
| `TRIMMED_STRING_TYPE` | `"requireTrimmedString"` | `TrimmedStringValidator(false)` |
| `ARRAY_TYPE` | `"requireArray"` | `ArrayValidator` |
| `ARRAY_2D_TYPE` | `"requireArray2D"` | `Array2DValidator` |
| `BOOL_TYPE` | `"requireBool"` | `BooleanValidator` |
| `OBJECT_TYPE` | `"requireObject"` | `ObjectValidator` |
| `MIXED_TYPE` | `"requireMixed"` | `DummyValidator` |

### 方法签名

| 方法 | 说明 |
|------|------|
| `has($key, $validator = MIXED_TYPE)` | 检查 key 是否存在且通过验证；值为 `null` 或验证失败返回 `false` |
| `get($key, $validator, $isMandatory, $default)` | 通用读取方法 |
| `getMandatory($key, $validator)` | 等价于 `get($key, $validator, true)` |
| `getOptional($key, $validator, $default)` | 等价于 `get($key, $validator, false, $default)` |

---

## AbstractDataProvider

实现 `DataProviderInterface` 的核心逻辑，子类只需实现 `getValue($key): mixed|null`。

### 行为规则

1. `getValue()` 返回 `null` 视为"值不存在"
2. `$validator` 参数可传入 `ValidatorInterface` 实例或类型常量字符串
3. 字符串类型常量通过 `getValidatorByLegacyString()` 映射为对应 Validator 实例
4. 验证失败时抛出 `DataValidationException`（子类），并自动附加 `fieldName`
5. mandatory 模式下值不存在抛出 `MandatoryValueMissingException`
6. optional 模式下值不存在返回 `$default`

---

## HierarchicalDataProviderInterface

扩展 `DataProviderInterface`，增加路径导航能力。

| 方法 | 说明 |
|------|------|
| `getCurrentPath()` | 获取当前路径 |
| `setCurrentPath($path)` | 设置绝对路径（空值重置到根） |
| `pushPath($relativePath)` | 压入相对路径 |
| `popPath()` | 弹出最后一级路径 |
| `getPathDelimiter()` | 获取路径分隔符 |
| `setPathDelimiter($delimiter)` | 设置路径分隔符（必须为单字符） |

---

## ArrayDataProvider

基于 PHP 数组的具体实现，同时实现 `HierarchicalDataProviderInterface`。

### 行为规则

- 构造函数接收 `array $data`
- 默认路径分隔符为 `"."`
- 支持嵌套数组的点号路径访问（如 `"a.b.c"`）
- 路径解析策略：优先匹配完整 key，再逐级拆分查找嵌套数组
- `pushPath` / `popPath` 维护相对路径栈，`getValue()` 在当前路径下查找
