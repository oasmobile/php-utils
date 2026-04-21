# Utils 使用指南

本文件说明 StringUtils、DataPacker、CommonUtils、AnsiColorizer 的使用方法。

---

## StringUtils

UTF-8 安全的字符串工具。

```php
use Oasis\Mlib\Utils\StringUtils;

// 截断字符串（按字符数）
StringUtils::stringChopdown("你好世界Hello", 4); // "你好世界"

// 按字节截断
StringUtils::stringChopdown("你好世界", 6, true); // "你好"（UTF-8 中文每字 3 字节）

// 前缀/后缀判断
StringUtils::stringStartsWith("hello world", "hello"); // true
StringUtils::stringEndsWith("file.php", ".php");       // true
```

---

## DataPacker

基于长度前缀的二进制打包工具，适合进程间通信或持久化结构化数据。

### 内存中打包/解包

```php
use Oasis\Mlib\Utils\DataPacker;

// 默认使用 igbinary（需安装 igbinary 扩展）
$packer = new DataPacker();

// 使用 PHP 原生序列化
$packer = new DataPacker('serialize', 'unserialize');

$data   = ['key' => 'value', 'count' => 42];
$packed = $packer->pack($data);

$result = $packer->unpack($packed); // ['key' => 'value', 'count' => 42]
```

### 流式操作

```php
$stream = fopen('data.bin', 'w+b');
$packer = new DataPacker('serialize', 'unserialize');
$packer->attachStream($stream);

// 写入多条记录
$packer->packToStream(['record' => 1]);
$packer->packToStream(['record' => 2]);

// 读取
rewind($stream);
$packer->attachStream($stream);
$r1 = $packer->unpackFromStream(); // ['record' => 1]
$r2 = $packer->unpackFromStream(); // ['record' => 2]
$r3 = $packer->unpackFromStream(); // null（流结束）

fclose($stream);
```

---

## CommonUtils

### CLI 检测

```php
use Oasis\Mlib\Utils\CommonUtils;

if (CommonUtils::isRunningFromCommandLine()) {
    echo "Running in CLI mode\n";
}
```

### 内存动态监控

适合长时间运行的 CLI 进程（如 daemon、队列消费者）：

```php
// 方式一：注册 tick 函数自动监控
declare(ticks=1);
CommonUtils::registerMemoryMonitorForTick();

// 方式二：手动调用
CommonUtils::monitorMemoryUsage(
    128000000, // 最低 limit（128MB）
    10,        // 低于 10% 时缩小
    70         // 超过 70% 时扩大
);
```

### 内存监控开关

运行时控制内存监控的启用/禁用，无需修改调用点或取消 tick function 注册：

```php
// 禁用内存监控（monitorMemoryUsage() 将直接返回，不执行任何操作）
CommonUtils::disableMemoryMonitor();

// 重新启用（默认状态即为启用）
CommonUtils::enableMemoryMonitor();
```

禁用期间内部状态保留，重新启用后从上次状态继续。

### 无符号右移

```php
// 模拟 Java 的 >>> 运算符
// 结果取决于 PHP_INT_SIZE：32 位系统为 65535，64 位系统为 281474976710655
$result = CommonUtils::unsignedRightShift(-1, 16);

// 更常见的用法：配合 CaesarCipher 内部使用
$result = CommonUtils::unsignedRightShift(0xFF00, 8); // 255
```

---

## AnsiColorizer

终端彩色输出，仅在 CLI 环境下有意义。

```php
use Oasis\Mlib\Utils\AnsiColorizer;

echo AnsiColorizer::foreground("Error!", "RED") . "\n";
echo AnsiColorizer::foreground("Success", "GREEN") . "\n";
echo AnsiColorizer::bold("Important") . "\n";
echo AnsiColorizer::underline("Underlined") . "\n";

// 亮色（通过 LIGHT- 前缀）
echo AnsiColorizer::foreground("Warning", "LIGHT-YELLOW") . "\n";

// 256 色模式（传数字）
echo AnsiColorizer::foreground("Custom", "208") . "\n";

// 背景色
echo AnsiColorizer::background("Highlighted", "BLUE") . "\n";
```

支持的命名颜色：`BLACK` / `RED` / `GREEN` / `YELLOW` / `BLUE` / `MAGENTA` / `CYAN` / `WHITE`，加 `LIGHT-` 前缀为亮色变体。
