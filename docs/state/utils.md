# Utils

本文件描述工具类模块的当前接口与行为规则。

---

## StringUtils

UTF-8 安全的字符串工具方法集合。

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `static stringChopdown(string $str, int $maxLength, bool $lengthUnitInByte = false)` | `string`, `int`, `bool` | `string` | 截断字符串至指定最大长度 |
| `static stringStartsWith(string $haystack, string $needle)` | `string`, `string` | `bool` | 判断字符串是否以指定前缀开头 |
| `static stringEndsWith(string $haystack, string $needle)` | `string`, `string` | `bool` | 判断字符串是否以指定后缀结尾 |

### 行为规则

- `stringChopdown`：默认按字符数截断（UTF-8），`$lengthUnitInByte = true` 时按字节截断
- `stringStartsWith`：内部使用 `str_starts_with()` 实现
- `stringEndsWith`：内部使用 `str_ends_with()` 实现
- 空 `$needle` 始终返回 `true`
- 依赖 `voku/portable-utf8` 处理 UTF-8 字符串

---

## DataPacker

基于长度前缀的二进制数据打包/解包工具，支持流式操作。

### 构造参数

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `$serializer` | `?callable` | `null` | 序列化函数（回退到 `igbinary_serialize` 或 `serialize`） |
| `$unserializer` | `?callable` | `null` | 反序列化函数（回退到 `igbinary_unserialize` 或 `unserialize`） |

构造函数有 callable 回退逻辑，不使用 constructor promotion。

### 公开方法

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `pack(mixed $dataObject)` | `mixed` | `string` | 序列化并添加 4 字节大端长度头 |
| `unpack(string $data)` | `string` | `mixed` | 解析长度头并反序列化 |
| `attachStream(mixed $stream)` | `mixed` | `void` | 绑定流资源 |
| `packToStream(mixed $dataObject)` | `mixed` | `void` | 打包并写入流 |
| `unpackFromStream()` | — | `mixed` | 从流中读取并解包（返回 `null` 表示流结束） |

### 行为规则

- 长度头格式：4 字节网络字节序（big-endian）无符号整数
- 解包时校验 payload 长度与头部声明一致，不一致抛出 `\UnexpectedValueException`
- 流操作内部维护 buffer，支持分片读取
- 默认使用 igbinary 序列化，可通过构造函数替换为任意 callable

---

## CommonUtils

通用工具方法。

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `static setLogger(?LoggerInterface $logger)` | `?LoggerInterface` | `void` | 注入 PSR-3 Logger（默认 `null`，静默） |
| `static isRunningFromCommandLine()` | — | `bool` | 检测当前是否在 CLI 模式运行（结果缓存） |
| `static monitorMemoryUsage(int $minUsage = 128000000, int $lowerThreshold = 10, int $upperThreshold = 70)` | `int`, `int`, `int` | `void` | 动态调整 `memory_limit` |
| `static registerMemoryMonitorForTick()` | — | `void` | 注册 tick 函数自动监控内存 |
| `static unsignedRightShift(int $num, int $bits)` | `int`, `int` | `int` | 无符号右移（模拟 Java `>>>` 运算符） |

### 日志

- `CommonUtils` 持有静态 `?LoggerInterface` 属性，通过 `setLogger()` 注入
- 默认为 `null`，所有日志调用使用 null-safe 操作符（`?->`），无 logger 时静默
- 内存调整时通过 `$logger->info()` 输出日志，不再使用 `fprintf(STDERR)`

### 内存监控行为

- `monitorMemoryUsage()` 内部使用 `match` 表达式解析 memory limit 后缀（`g`/`m`/`k`，含 `default` 分支）
- 当使用率超过 `$upperThreshold`%：扩大 limit
- 当使用率低于 `$lowerThreshold`% 且非首次：缩小 limit（不低于 `$minUsage`）
- 调整时通过 PSR-3 Logger 输出日志（如已注入）

---

## AnsiColorizer

ANSI 终端颜色输出工具。

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `static bold(string $text)` | `string` | `string` | 加粗 |
| `static underline(string $text)` | `string` | `string` | 下划线 |
| `static reverse(string $text)` | `string` | `string` | 反色 |
| `static foreground(string $text, AnsiColor\|int $color)` | `string`, `AnsiColor\|int` | `string` | 设置前景色 |
| `static background(string $text, AnsiColor\|int $color)` | `string`, `AnsiColor\|int` | `string` | 设置背景色 |

### AnsiColor enum

Int-backed enum，定义 ANSI 终端颜色。

| Case | Backed Value | 说明 |
|------|-------------|------|
| `Black` | `0` | 基础色 |
| `Red` | `1` | 基础色 |
| `Green` | `2` | 基础色 |
| `Yellow` | `3` | 基础色 |
| `Blue` | `4` | 基础色 |
| `Magenta` | `5` | 基础色 |
| `Cyan` | `6` | 基础色 |
| `White` | `7` | 基础色 |
| `LightBlack` | `100` | 亮色变体 |
| `LightRed` | `101` | 亮色变体 |
| `LightGreen` | `102` | 亮色变体 |
| `LightYellow` | `103` | 亮色变体 |
| `LightBlue` | `104` | 亮色变体 |
| `LightMagenta` | `105` | 亮色变体 |
| `LightCyan` | `106` | 亮色变体 |
| `LightWhite` | `107` | 亮色变体 |

### 颜色支持

- `AnsiColor` 基础色 case（`Black`–`White`）：ANSI 码为 `30+value`（前景）/ `40+value`（背景）
- `AnsiColor` 亮色 case（`LightBlack`–`LightWhite`）：通过 bold 包裹对应基础色实现（`getBaseColor()` 辅助方法映射）
- `int` 参数：256 色模式（`38;5;N` / `48;5;N`）
- 所有输出自动追加 `\e[0m` 关闭标签（`CLOSE_TAG` 为 `private const`）
- `close()` 方法使用 `str_ends_with()` 检查是否已有关闭标签
