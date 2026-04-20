# Spec Goal: Upgrade to PHP 8

## 来源

- 分支: `feature/upgrade-to-php8`
- 需求文档: `docs/proposals/PRP-001-upgrade-to-php8.md`

## 背景摘要

项目 `oasis/utils` 当前运行在 PHP 7.4 上，使用 PHPUnit 5.x 作为测试框架，依赖 `voku/portable-utf8` ^3.0 处理 UTF-8 字符串。PHP 7.4 已于 2022-11 EOL，不再接收安全修复。

系统包含 DataProvider 子系统、Validators 验证器体系、加密模块（CaesarCipher / Rc4）、工具类（StringUtils / DataPacker / CommonUtils / AnsiColorizer）以及自定义异常体系。所有模块均有对应的单元测试覆盖。

升级到 PHP 8 是保持项目安全性与生态兼容性的必要步骤。PHPUnit 11.x 要求 PHP ≥ 8.2，与目标版本兼容。经调研，`voku/portable-utf8` 仍在活跃维护，CI 已在 PHP 8.5 上运行，`^3.0` 约束可直接兼容。

## 目标

- 在 `composer.json` 中添加 PHP 版本约束 `>=8.2`
- 升级 `phpunit/phpunit` 从 `^5.1` 到 `^11.0`
- 保持 `voku/portable-utf8` 约束为 `^3.0`（已验证兼容）
- 修复源代码（`src/`）中 PHP 8 下的 deprecation 和 incompatibility
- 适配测试代码（`ut/`）到 PHPUnit 11.x API（一步到位，不经中间版本）
- 更新 `PROJECT.md` 中的 PHP 版本信息和构建命令（改用系统默认 `php`）
- 更新 `docs/state/` 中的相关版本信息
- 重新生成 `composer.lock`
- 确保所有现有测试在 PHP 8.x + PHPUnit 11.x 下通过

## 不做的事情（Non-Goals）

- 不对现有代码做大规模 PHP 8 新语法重写（named arguments、match expression 等）
- 不改变项目功能或公共 API
- 不保留对 PHP 7.x 的向后兼容
- 不升级 `voku/portable-utf8` 大版本（当前版本已兼容）

## Clarification 记录

### Q1: PHP 版本约束设定方式

- 选项: A) `^8.5` / B) `>=8.2` / C) `~8.5.0` / D) 补充说明
- 回答: B — 宽松约束 `>=8.2`，兼容 PHPUnit 11 的最低要求，覆盖更多 8.x 用户

### Q2: `voku/portable-utf8` 兼容性策略

- 选项: A) 保持 ^3.0 不变先试 / B) 放宽约束 / C) 先调研再决定 / D) 补充说明
- 回答: C — 先调研
- 调研结论: 该库仍在活跃维护（2 天前有提交），AppVeyor CI 已迁移到 PHP 8.5，README 声明支持 PHP 7.1+/8.0+。保持 `^3.0` 约束即可。

### Q3: PHPUnit 升级策略

- 选项: A) 一步到位 PHPUnit 11.x / B) 分两步经 10.x / C) 允许临时 @todo / D) 补充说明
- 回答: A — 一步到位直接适配 PHPUnit 11.x

### Q4: 升级后 PHP 调用方式

- 选项: A) 系统默认 `php` / B) 新别名 `php85` / C) 直接 `vendor/bin/phpunit` / D) 补充说明
- 回答: A — 改为使用系统默认 `php`（系统默认已是 8.x）

## 约束与决策

- PHP 版本约束: `>=8.2`（宽松，覆盖 PHPUnit 11 最低要求）
- PHPUnit 版本: `^11.0`（一步到位，不经中间版本）
- `voku/portable-utf8`: 保持 `^3.0` 不变
- 构建命令: 从 `php74 vendor/bin/phpunit` 改为 `php vendor/bin/phpunit`
- 测试适配: 完整适配 PHPUnit 11.x API，不留 @todo
- 兼容性: 不兼容 PHP 7.x，不做旧数据迁移（本次无数据模型变更）
