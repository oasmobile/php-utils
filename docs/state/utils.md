# Utils

本文件描述工具类模块的当前接口与行为规则。

---

## StringUtils

UTF-8 安全的字符串工具方法集合。

| 方法 | 说明 |
|------|------|
| `static stringChopdown($str, $maxLength, $lengthUnitInByte = false)` | 截断字符串至指定最大长度 |
| `static stringStartsWith($haystack, $needle)` | 判断字符串是否以指定前缀开头 |
| `static stringEndsWith($haystack, $needle)` | 判断字符串是否以指定后缀结尾 |

### 行为规则

- `stringChopdown`：默认按字符数截断（UTF-8），`$lengthUnitInByte = true` 时按字节截断
- `stringStartsWith` / `stringEndsWith`：空 `$needle` 始终返回 `true`
- 依赖 `voku/portable-utf8` 处理 UTF-8 字符串

---

## DataPacker

基于长度前缀的二进制数据打包/解包工具，支持流式操作。

### 构造参数

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `$serializer` | `igbinary_serialize` | 序列化函数（callable） |
| `$unserializer` | `igbinary_unserialize` | 反序列化函数（callable） |

### 公开方法

| 方法 | 说明 |
|------|------|
| `pack($dataObject)` | 序列化并添加 4 字节大端长度头 |
| `unpack($data)` | 解析长度头并反序列化 |
| `attachStream($stream)` | 绑定流资源 |
| `packToStream($dataObject)` | 打包并写入流 |
| `unpackFromStream()` | 从流中读取并解包（返回 `null` 表示流结束） |

### 行为规则

- 长度头格式：4 字节网络字节序（big-endian）无符号整数
- 解包时校验 payload 长度与头部声明一致，不一致抛出 `\UnexpectedValueException`
- 流操作内部维护 buffer，支持分片读取
- 默认使用 igbinary 序列化，可通过构造函数替换为任意 callable

---

## CommonUtils

通用工具方法。

| 方法 | 说明 |
|------|------|
| `static isRunningFromCommandLine()` | 检测当前是否在 CLI 模式运行（结果缓存） |
| `static monitorMemoryUsage($minUsage, $lowerThreshold, $upperThreshold)` | 动态调整 `memory_limit` |
| `static registerMemoryMonitorForTick()` | 注册 tick 函数自动监控内存 |
| `static enableMemoryMonitor()` | 启用内存监控（默认状态） |
| `static disableMemoryMonitor()` | 禁用内存监控，`monitorMemoryUsage()` 将直接返回 |
| `static unsignedRightShift($num, $bits)` | 无符号右移（模拟 Java `>>>` 运算符） |

### 内存监控行为

- 当使用率超过 `$upperThreshold`%：扩大 limit
- 当使用率低于 `$lowerThreshold`% 且非首次：缩小 limit（不低于 `$minUsage`）
- 单位转换使用 `ceil` 向上取整，K/M/G 各级均产生整数值
- CLI 模式下调整时输出到 stderr

### Global_Switch

- `enableMemoryMonitor()` / `disableMemoryMonitor()` 控制 `monitorMemoryUsage()` 是否执行
- 默认启用；禁用时 `monitorMemoryUsage()` 直接返回，不执行内存检测、不调用 `ini_set`、不输出日志
- enable/disable 不重置 `$isLowest`、`$neverReset` 等内部 static 变量，重新启用后从上次状态继续

---

## AnsiColorizer

ANSI 终端颜色输出工具。

| 方法 | 说明 |
|------|------|
| `static bold($text)` | 加粗 |
| `static underline($text)` | 下划线 |
| `static reverse($text)` | 反色 |
| `static foreground($text, $color)` | 设置前景色 |
| `static background($text, $color)` | 设置背景色 |

### 颜色支持

- 命名颜色：`BLACK` / `RED` / `GREEN` / `YELLOW` / `BLUE` / `MAGENTA` / `CYAN` / `WHITE`
- `LIGHT-` 前缀：通过 bold 实现亮色（如 `LIGHT-RED`）
- 数字颜色：256 色模式（`38;5;N` / `48;5;N`）
- 不支持的颜色名返回原始文本（不报错）
- 所有输出自动追加 `\e[0m` 关闭标签
