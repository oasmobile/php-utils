# Validators

本文件描述验证器子系统的当前接口与行为规则。

---

## 接口

```php
interface ValidatorInterface {
    public function validate(mixed $target): mixed; // 返回验证后的值，失败抛出 DataValidationException 子类
}
```

---

## 验证器清单

### StringValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$strict` | `bool` | `false` | ✅ | strict 模式仅接受 string；宽松模式自动转换 bool/scalar/`__toString` 对象 |
| `$allowEmpty` | `bool` | `true` | ✅ | 为 `false` 时空字符串抛出 `DataEmptyException` |

### TrimmedStringValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$strict` | `bool` | `false` | ✅ | 同 StringValidator |
| `$direction` | `TrimDirection` | `TrimDirection::Both` | ✅ | trim 方向，见 TrimDirection enum |
| `$characters` | `string` | `" \n\t\r\0\x0B"` | ✅ | 要 trim 的字符集 |

返回 trim 后的字符串。内部使用 `match` 表达式基于 `TrimDirection` enum case 选择 `ltrim`/`rtrim`/`trim`。

### TrimDirection enum

纯 enum（无 backed value），定义 trim 方向。

| Case | 说明 |
|------|------|
| `Both` | 两端 trim（`trim()`） |
| `Left` | 左侧 trim（`ltrim()`） |
| `Right` | 右侧 trim（`rtrim()`） |

### IntegerValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$strict` | `bool` | `false` | ✅ | 宽松模式下 string/float 可解析为 int 时自动转换 |
| `$base` | `int` | `10` | ✅ | 整数解析进制（仅宽松模式有效） |

转换条件：`strval(intval($target, $base)) == strval($target)`。

### FloatValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$strict` | `bool` | `false` | ✅ | 宽松模式下 string/int 可解析为 float 时自动转换 |

转换条件：`strval(floatval($target)) == strval($target)`。

### BooleanValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$strict` | `bool` | `false` | ✅ | 宽松模式支持字符串和数值转换 |

宽松模式转换规则：

| 输入 | 结果 |
|------|------|
| `"true"` / `"on"` / `"1"` / `"yes"` | `true` |
| `"false"` / `"off"` / `"0"` / `"no"` / `""` | `false` |
| 数值 `1` / `1.0` | `true` |
| 数值 `0` / `0.0` | `false` |

字符串比较不区分大小写。

### ArrayValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$allowNull` | `bool` | `false` | ✅ | 为 `true` 时 `null` 返回 `[]` |
| `$elementValidator` | `?ValidatorInterface` | `null`（回退为 `DummyValidator`） | — | 对每个元素执行验证（手动赋值，有默认值回退逻辑） |

元素验证失败抛出 `InvalidArrayElementException`。

### Array2DValidator

继承 `ArrayValidator`，内部使用 `ArrayValidator` 作为 `$elementValidator`，实现二维数组验证。默认 `$allowNull = true`。

### ObjectValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$allowNull` | `bool` | `true` | ✅ | 为 `true` 时 `null` 返回 `null` |

### EmailValidator

- 输入必须为 string
- 禁止包含 `#!$%|&` 字符
- 使用 `filter_var(FILTER_VALIDATE_EMAIL)` 验证

### UrlValidator

- 输入必须为 string
- 使用 `filter_var(FILTER_VALIDATE_URL)` 验证

### RegexValidator

| 参数 | 类型 | readonly | 说明 |
|------|------|----------|------|
| `$pattern` | `string` | ✅ | 正则表达式（构造时校验合法性） |

- 输入必须为 string
- 不匹配时抛出 `RegexNotMatchedException`

### StringLengthValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$maxLength` | `int` | — | ✅ | 最大长度（必填） |
| `$minLength` | `int` | `0` | ✅ | 最小长度 |
| `$chopDown` | `bool` | `false` | ✅ | 为 `true` 时超长截断而非抛异常 |
| `$encoding` | `string` | `"UTF-8"` | ✅ | 字符编码 |

- 使用 `voku/portable-utf8` 计算字符长度
- 过短抛出 `StringTooShortException`
- 过长且不截断时抛出 `StringTooLongException`

### EnumerationValidator

| 参数 | 类型 | 默认值 | readonly | 说明 |
|------|------|--------|----------|------|
| `$values` | `array` | — | — | 允许值列表（必填，手动赋值；`!$caseSensitive` 时转小写） |
| `$strictType` | `bool` | `false` | ✅ | 是否严格类型比较 |
| `$caseSensitive` | `bool` | `true` | ✅ | 是否区分大小写（仅对 string 生效） |

不在列表中抛出 `InvalidValueException`。返回原始输入值（不做大小写转换）。

### ChainedValidator

接收多个 `ValidatorInterface` 实例（variadic 参数），按顺序依次执行验证，前一个的输出作为后一个的输入。`$validators` 属性类型为 `array`，不使用 constructor promotion（variadic 参数需先校验再赋值）。

### DummyValidator

直接返回输入值，不做任何验证。用于 `DataType::Mixed` 场景。
