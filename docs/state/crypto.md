# Crypto

本文件描述加密算法模块的当前接口与行为规则。

---

## CaesarCipher

基于查找表的分组置换加密，支持整数和字符串加解密。

### 构造参数

| 参数 | 类型 | 默认值 | readonly | 约束 |
|------|------|--------|----------|------|
| `$bits` | `int` | `32` | ✅ | 偶数，范围 (0, 64]，必须被 `$partition` 整除 |
| `$partition` | `int` | `8` | ✅ | 偶数 |
| `$strength` | `int` | `5` | ✅ | 加密轮数 |

### 公开方法

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `encrypt(int\|string $number)` | `int\|string` | `int\|string` | 加密整数或字符串 |
| `decrypt(int\|string $number)` | `int\|string` | `int\|string` | 解密整数或字符串 |
| `getBits()` | — | `int` | 获取位数 |
| `getPartition()` | — | `int` | 获取分区大小 |
| `getStrength()` | — | `int` | 获取加密轮数 |
| `getLookupTable()` | — | `array` | 获取查找表（首次调用时随机生成） |
| `setLookupTable(array $lookupTable)` | `array` | `void` | 设置查找表（同时生成反向表） |

### 行为规则

- `$lookupTable`、`$reverseTable` 为延迟初始化属性（非 readonly），首次使用时随机生成（`shuffle`），可通过 `setLookupTable` 固定
- 字符串加密要求 `$bits` 必须被 8 整除
- 字符串加密结果首字节为补偿字节（记录末尾填充长度）
- 加密过程：旋转 → 查表替换，重复 `$strength` 轮
- 解密过程：反向查表 → 反向旋转，重复 `$strength` 轮
- 依赖 `CommonUtils::unsignedRightShift()` 进行无符号右移

---

## Rc4

标准 RC4 流加密实现。

### 公开方法

| 方法 | 参数类型 | 返回类型 | 说明 |
|------|----------|----------|------|
| `static rc4(string $key, string $input)` | `string`, `string` | `string` | 对 `$input` 使用 `$key` 进行 RC4 加/解密 |

### 行为规则

- 纯静态方法，无状态
- 加密和解密使用同一方法（对称流密码）
- 输入输出均为二进制字符串
