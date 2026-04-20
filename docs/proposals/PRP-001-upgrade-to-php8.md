# PRP-001: Upgrade to PHP 8

## Status

`in-progress`

---

## Background

项目当前运行在 PHP 7.4 上（PHPUnit 5.x、无显式 PHP 版本约束）。PHP 7.4 已于 2022-11-28 结束官方支持（EOL），不再接收安全修复。升级到 PHP 8 是保持项目安全性与生态兼容性的必要步骤。

---

## Problem

- PHP 7.4 已 EOL，存在潜在安全风险
- 新版依赖库逐步放弃 PHP 7.x 支持，锁定旧版本将越来越难维护
- PHPUnit 5.x 同样已停止维护，无法利用新版测试框架特性
- 无法使用 PHP 8 的语言特性（named arguments、match expression、union types、attributes 等）

---

## Goals

- 将最低 PHP 版本要求提升至 8.5（当前最新稳定版，active support 至 2027-12）
- 升级 PHPUnit 至兼容 PHP 8.5 的版本（PHPUnit 11.x）
- 确保所有现有测试在新版本下通过
- 更新 `composer.json` 中的 PHP 版本约束
- 更新项目文档（PROJECT.md、docs/state/）反映新版本要求

---

## Non-Goals

- 不在本次升级中对现有代码做大规模 PHP 8 新语法重写（可后续逐步采用）
- 不改变项目功能或公共 API
- 不保留对 PHP 7.x 的向后兼容

---

## Scope

- `composer.json`：添加/更新 `php` 版本约束，升级 `phpunit/phpunit`
- `composer.lock`：重新生成
- 源代码（`src/`）：修复 PHP 8 下的 deprecation 和 incompatibility
- 测试代码（`ut/`）：适配新版 PHPUnit API（如有必要）
- 文档：更新 PROJECT.md 及 docs/state/ 中的版本信息
- CI / 构建命令：更新 php 调用方式

---

## References

- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [PHPUnit Version Compatibility](https://phpunit.de/supported-versions.html)

---

## Open Questions

1. 依赖 `voku/portable-utf8` ^3.0 是否兼容 PHP 8.5？是否需要升级大版本？
2. PHPUnit 11.x 的 API 变更幅度——是否需要大量改写测试代码？

---

## Notes

- PHP 8.5 于 2025-11 发布，active support 至 2027-12，security fixes 至 2029
- PHPUnit 11.x 要求 PHP ≥ 8.2，与 PHP 8.5 兼容
- 升级过程中需关注：`voku/portable-utf8` 的 PHP 8.5 兼容性、PHPUnit API 变更（annotations → attributes、`assertContains` 行为变化、TestCase 方法签名等）
- 从 PHPUnit 5 跨越到 11 变化较大，需逐一适配测试代码
