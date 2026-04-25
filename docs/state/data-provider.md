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

### DataType enum

String-backed enum，替代原类型常量。用于 `$validator` 参数的类型安全传递。

| Case | Backed Value | 对应 Validator |
|------|-------------|----------------|
| `Int` | `"requireInt"` | `IntegerValidator` |
| `Float` | `"requireFloat"` | `FloatValidator` |
| `String` | `"requireString"` | `StringValidator`（默认宽松模式） |
| `NonEmptyString` | `"requireNonEmptyString"` | `StringValidator(false, false)` |
| `TrimmedString` | `"requireTrimmedString"` | `TrimmedStringValidator(false)` |
| `Array` | `"requireArray"` | `ArrayValidator` |
| `Array2D` | `"requireArray2D"` | `Array2DValidator` |
| `Bool` | `"requireBool"` | `BooleanValidator` |
| `Object` | `"requireObject"` | `ObjectValidator` |
| `Mixed` | `"requireMixed"` | `DummyValidator` |

### 方法签名

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `has(string $key, ValidatorInterface\|DataType $validator = DataType::Mixed)` | `string`, `ValidatorInterface\|DataType` | `bool` | 检查 key 是否存在且通过验证；值为 `null` 或验证失败返回 `false` |
| `get(string $key, ValidatorInterface\|DataType $validator = DataType::String, bool $isMandatory = false, mixed $default = null)` | `string`, `ValidatorInterface\|DataType`, `bool`, `mixed` | `mixed` | 通用读取方法 |
| `getMandatory(string $key, ValidatorInterface\|DataType $validator = DataType::String)` | `string`, `ValidatorInterface\|DataType` | `mixed` | 等价于 `get($key, $validator, true)` |
| `getOptional(string $key, ValidatorInterface\|DataType $validator = DataType::String, mixed $default = null)` | `string`, `ValidatorInterface\|DataType`, `mixed` | `mixed` | 等价于 `get($key, $validator, false, $default)` |

---

## AbstractDataProvider

实现 `DataProviderInterface` 的核心逻辑，子类只需实现 `getValue($key): mixed|null`。

### 行为规则

1. `getValue()` 返回 `null` 视为"值不存在"
2. `$validator` 参数可传入 `ValidatorInterface` 实例或 `DataType` enum case
3. `DataType` enum case 通过 `getValidatorByLegacyString()` 映射为对应 Validator 实例（内部使用 `match` 表达式）
4. 验证失败时抛出 `DataValidationException`（子类），并自动附加 `fieldName`
5. mandatory 模式下值不存在抛出 `MandatoryValueMissingException`
6. optional 模式下值不存在返回 `$default`

---

## HierarchicalDataProviderInterface

扩展 `DataProviderInterface`，增加路径导航能力。

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `getCurrentPath()` | — | `string` | 获取当前路径 |
| `setCurrentPath(string $path)` | `string` | `void` | 设置绝对路径（空值重置到根） |
| `pushPath(string $relativePath)` | `string` | `void` | 压入相对路径 |
| `popPath()` | — | `void` | 弹出最后一级路径 |
| `getPathDelimiter()` | — | `string` | 获取路径分隔符 |
| `setPathDelimiter(string $delimiter)` | `string` | `void` | 设置路径分隔符（必须为单字符） |

---

## ArrayDataProvider

基于 PHP 数组的具体实现，同时实现 `HierarchicalDataProviderInterface`。

### 行为规则

- 构造函数接收 `array $data`（`readonly` promoted property）
- 默认路径分隔符为 `"."`（`$delimeter` 运行时可变，非 readonly）
- 支持嵌套数组的点号路径访问（如 `"a.b.c"`）
- 路径解析策略：优先匹配完整 key，再逐级拆分查找嵌套数组
- `pushPath` / `popPath` 维护相对路径栈（`$paths` 运行时可变，非 readonly），`getValue()` 在当前路径下查找
