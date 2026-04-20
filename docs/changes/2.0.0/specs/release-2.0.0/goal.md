# Spec Goal: Release 2.0.0

## 来源

- 分支: `release/2.0.0`
- 需求文档: `docs/proposals/PRP-001-upgrade-to-php8.md`

## 背景摘要

`oasis/utils` 是一个 PHP 工具库，提供数据读取与校验（DataProvider / Validators）、加密算法（CaesarCipher / Rc4）、字符串工具（StringUtils）、数据打包（DataPacker）等功能模块。项目此前运行在 PHP 7.4 + PHPUnit 5.x 上。

Feature `upgrade-to-php8`（PRP-001）已在 `feature/upgrade-to-php8` 分支完成并合并到 develop。该 feature 将项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP >=8.2 + PHPUnit 11.x，涉及 composer 依赖更新、phpunit.xml 格式迁移、测试代码适配（基类替换、data provider static 化、setUp/tearDown 签名修复）、源代码兼容性修复（2 处 `method_exists` 参数错误）以及 PROJECT.md 更新。全量测试已通过（318 tests, 2373 assertions，无 deprecation 警告）。

由于本次升级放弃了 PHP 7.x 的向后兼容，构成破坏性变更，因此以主版本号 2.0.0 发布。Feature spec 中的手工测试（Task 11）和 Code Review（Task 12）尚未执行，将纳入 release spec 统一编排。

## 目标

- 完成 upgrade-to-php8 feature 遗留的手工测试和 Code Review
- 在 `composer.json` 中显式添加 `"version": "2.0.0"`
- 将 `docs/changes/unreleased/upgrade-to-php8.md` 整理到 `docs/changes/2.0.0/` 作为正式 changelog
- 在 `docs/state/` 中补充工程约束信息（PHP >=8.2、PHPUnit 11.x 等）
- 完成 release 分支的验证、文档收尾，确保可合并到 master 并打 tag

## 不做的事情（Non-Goals）

- 不在 release 中引入新功能或对现有代码做 PHP 8 新语法重写
- 不改变项目功能或公共 API
- 不保留对 PHP 7.x 的向后兼容（这是 feature 阶段已确定的决策）
- 不编写 CI/CD 流水线配置

## Clarification 记录

### Q1: Release 2.0.0 的范围确认

- 选项: A) 仅包含 upgrade-to-php8 feature，release spec 中编排剩余手工测试和 Code Review 及 release 收尾工作 / B) 还有其他变更 / C) 补充说明
- 回答: A

### Q2: Feature spec 未完成 task 的处理方式

- 选项: A) 在 release spec 中重新编排，feature spec 保持未勾选 / B) 先回 feature spec 完成 / C) 跳过，由 release 自身验证覆盖 / D) 补充说明
- 回答: A

### Q3: 版本号管理和 changelog 策略

- 选项: A) 不加 version 字段，仅整理 changelog / B) 显式添加 version 字段并整理 changelog / C) 补充说明
- 回答: B

### Q4: SSOT 中是否补充工程约束信息

- 选项: A) 不需要，由 PROJECT.md 承载 / B) 需要，在 docs/state/ 中补充 / C) 补充说明
- 回答: B

## 约束与决策

- **范围**: 本次 release 仅包含 upgrade-to-php8 一个 feature，无其他新增变更
- **遗留 task 处理**: Feature spec 的 Task 11（手工测试）和 Task 12（Code Review）在 release spec 中重新编排，feature spec 中保持未勾选状态
- **版本号**: 在 `composer.json` 中显式声明 `"version": "2.0.0"`，同时由 VCS tag `v2.0.0` 管理
- **Changelog**: `docs/changes/unreleased/upgrade-to-php8.md` 移动到 `docs/changes/2.0.0/` 目录
- **SSOT 更新**: 在 `docs/state/` 中补充工程约束（PHP 版本、测试框架版本等），使 state 文档完整反映系统当前技术要求
