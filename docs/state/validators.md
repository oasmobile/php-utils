# Validators

本文件描述验证器子系统的当前接口与行为规则。

---

## 接口

```php
interface ValidatorInterface {
    public function validate($target); // 返回验证后的值，失败抛出 DataValidationException 子类
}
```

---

## 验证器清单

### StringValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$strict` | `false` | strict 模式仅接受 string；宽松模式自动转换 bool/scalar/`__toString` 对象 |
| `$allowEmpty` | `true` | 为 `false` 时空字符串抛出 `DataEmptyException` |

### TrimmedStringValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$strict` | `false` | 同 StringValidator |
| `$direction` | `TRIM_BOTH` | 可选 `TRIM_LEFT` / `TRIM_RIGHT` / `TRIM_BOTH` |
| `$characters` | `" \n\t\r\0\x0B"` | 要 trim 的字符集 |

返回 trim 后的字符串。

### IntegerValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$strict` | `false` | 宽松模式下 string/float 可解析为 int 时自动转换 |
| `$base` | `10` | 整数解析进制（仅宽松模式有效） |

转换条件：`strval(intval($target, $base)) == strval($target)`。

### FloatValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$strict` | `false` | 宽松模式下 string/int 可解析为 float 时自动转换 |

转换条件：`strval(floatval($target)) == strval($target)`。

### BooleanValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$strict` | `false` | 宽松模式支持字符串和数值转换 |

宽松模式转换规则：

| 输入 | 结果 |
|------|------|
| `"true"` / `"on"` / `"1"` / `"yes"` | `true` |
| `"false"` / `"off"` / `"0"` / `"no"` / `""` | `false` |
| 数值 `1` / `1.0` | `true` |
| 数值 `0` / `0.0` | `false` |

字符串比较不区分大小写。

### ArrayValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$allowNull` | `false` | 为 `true` 时 `null` 返回 `[]` |
| `$elementValidator` | `DummyValidator` | 对每个元素执行验证 |

元素验证失败抛出 `InvalidArrayElementException`。

### Array2DValidator

继承 `ArrayValidator`，内部使用 `ArrayValidator` 作为 `$elementValidator`，实现二维数组验证。默认 `$allowNull = true`。

### ObjectValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$allowNull` | `true` | 为 `true` 时 `null` 返回 `null` |

### EmailValidator

- 输入必须为 string
- 禁止包含 `#!$%|&` 字符
- 使用 `filter_var(FILTER_VALIDATE_EMAIL)` 验证

### UrlValidator

- 输入必须为 string
- 使用 `filter_var(FILTER_VALIDATE_URL)` 验证

### RegexValidator

| 参数 | 说明 |
|------|------|
| `$pattern` | 正则表达式（构造时校验合法性） |

- 输入必须为 string
- 不匹配时抛出 `RegexNotMatchedException`

### StringLengthValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$maxLength` | — | 最大长度（必填） |
| `$minLength` | `0` | 最小长度 |
| `$chopDown` | `false` | 为 `true` 时超长截断而非抛异常 |
| `$encoding` | `"UTF-8"` | 字符编码 |

- 使用 `voku/portable-utf8` 计算字符长度
- 过短抛出 `StringTooShortException`
- 过长且不截断时抛出 `StringTooLongException`

### EnumerationValidator

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$values` | — | 允许值列表（必填） |
| `$strictType` | `false` | 是否严格类型比较 |
| `$caseSensitive` | `true` | 是否区分大小写（仅对 string 生效） |

不在列表中抛出 `InvalidValueException`。返回原始输入值（不做大小写转换）。

### ChainedValidator

接收多个 `ValidatorInterface` 实例，按顺序依次执行验证，前一个的输出作为后一个的输入。

### DummyValidator

直接返回输入值，不做任何验证。用于 `MIXED_TYPE` 场景。
