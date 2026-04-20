# upgrade-to-php8（PRP-001）

将项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP >=8.2 + PHPUnit ^11.0。

---

## Changed

- `composer.json`：添加 `"php": ">=8.2"`，将 `phpunit/phpunit` 从 `^5.1` 升级为 `^11.0`
- `composer.lock`：在 PHP 8.x 环境下重新生成
- `phpunit.xml`：迁移到 PHPUnit 11.x 格式（移除 XSD 属性，添加 `cacheDirectory`）
- 17 个测试文件基类从 `PHPUnit_Framework_TestCase` 替换为 `\PHPUnit\Framework\TestCase`
- 14 个测试文件的 data provider 方法添加 `static` 关键字
- 2 个测试文件的 `setUp()` / `tearDown()` 添加 `: void` 返回类型
- `MlibDataProviderTest.php`：移除 `testNull()` 上的空 `@dataProvider` annotation
- `PROJECT.md`：更新 PHP 版本、测试框架版本和测试命令

## Added

- `ut/CaesarCipherTest.php` 加入 `phpunit.xml` test suite
- `.gitignore` 添加 `.phpunit.cache/` 条目

## Fixed

- `StringValidator.php`：修复 `method_exists($target, '__toString()')` 为 `method_exists($target, '__toString')`
- `TrimmedStringValidator.php`：修复 `method_exists($target, '__toString()')` 为 `method_exists($target, '__toString')`

---

## 测试覆盖

- 全量测试通过：318 tests, 2373 assertions，无 deprecation 警告
