# Crypto 使用指南

本文件说明 CaesarCipher 和 Rc4 的使用方法。

---

## Rc4

标准 RC4 流加密，加密和解密使用同一方法。

```php
use Oasis\Mlib\Utils\Rc4;

$key       = "my-secret-key";
$plaintext = "Hello, World!";

// 加密
$encrypted = Rc4::rc4($key, $plaintext);

// 解密（同一方法、同一 key）
$decrypted = Rc4::rc4($key, $encrypted); // "Hello, World!"
```

适用场景：简单的对称加密需求，不要求高安全性。

---

## CaesarCipher

基于查找表的分组置换加密，支持整数和字符串。适合对 ID 进行混淆（如公开 URL 中隐藏自增 ID）。

### 整数加密

```php
use Oasis\Mlib\Utils\CaesarCipher;

$cipher = new CaesarCipher(32, 8, 5);

// 固定查找表（确保可重复解密）
$table = $cipher->getLookupTable(); // 首次调用随机生成
$cipher->setLookupTable($table);    // 后续使用同一表

$encrypted = $cipher->encrypt(12345);
$decrypted = $cipher->decrypt($encrypted); // 12345
```

### 字符串加密

```php
$cipher = new CaesarCipher(32, 8, 5);
$cipher->setLookupTable($savedTable);

$encrypted = $cipher->encrypt("secret");
$decrypted = $cipher->decrypt($encrypted); // "secret"
```

字符串加密要求 `$bits` 必须被 8 整除。

### 参数选择

| 参数 | 说明 | 建议 |
|------|------|------|
| `$bits` | 数值空间位数 | 32 适合 int32 范围，64 适合更大数值 |
| `$partition` | 分组大小 | 8 是常用值 |
| `$strength` | 加密轮数 | ≥ 5，越大越安全但越慢 |

### 持久化查找表

查找表是加解密的核心，必须持久化保存：

```php
// 生成并保存
$table = $cipher->getLookupTable();
$json  = json_encode($table);
file_put_contents('cipher_table.json', $json);

// 恢复
$table = json_decode(file_get_contents('cipher_table.json'), true);
$cipher->setLookupTable($table);
```

> 注意：丢失查找表 = 无法解密。
