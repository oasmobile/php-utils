# Changelog

## v3.2.0

修复 `DataValidationException::$fieldName` 未初始化 bug，清理死代码，PBT 重构扩展至 23 个 property，行覆盖率 99.45%。详见 [3.2.0/CHANGELOG.md](3.2.0/CHANGELOG.md)。

## v3.1.0

修复 `CommonUtils::monitorMemoryUsage()` PHP 8.5 兼容性问题，`fprintf(STDERR)` 替换为 PSR-3 Logger，PropertyTest 改用 Eris。详见 [3.1.0/CHANGELOG.md](3.1.0/CHANGELOG.md)。

## v3.0.2

新增 1.x → ^3.0 migration guide。详见 [3.0.2/CHANGELOG.md](3.0.2/CHANGELOG.md)。

## v3.0.1

全文件添加 `declare(strict_types=1)`，测试覆盖率提升至 95%。详见 [3.0.1/CHANGELOG.md](3.0.1/CHANGELOG.md)。

## v3.0.0

PHP 8.2+ 语法全量改造：enum 替代常量、constructor promotion、readonly、类型声明补齐、match 表达式、新字符串函数。详见 [3.0.0/CHANGELOG.md](3.0.0/CHANGELOG.md)。

## v2.0.0 - 2026-04-21

升级到 PHP >=8.2 + PHPUnit 11.x，放弃 PHP 7.x 向后兼容。详见 [2.0.0/CHANGELOG.md](2.0.0/CHANGELOG.md)。
