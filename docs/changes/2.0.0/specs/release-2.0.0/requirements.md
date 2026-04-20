# Release 2.0.0 Requirements

本文件定义 `oasis/utils` v2.0.0 release 的需求与发布判定标准。

---

## 发布范围

| Feature 名称 | Spec 路径 | Proposal 引用 | Proposal Status | Tasks 完成状态 |
|--------------|-----------|---------------|-----------------|---------------|
| upgrade-to-php8 | `.kiro/specs/upgrade-to-php8/` | `docs/proposals/PRP-001-upgrade-to-php8.md` | `implemented` | 10/12（Task 11 手工测试、Task 12 Code Review 未执行） |

---

## Feature 概要

### upgrade-to-php8（PRP-001）

将项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP >=8.2 + PHPUnit 11.x。核心变更包括：composer 依赖更新、phpunit.xml 格式迁移、17+1 个测试文件的 PHPUnit 11 API 适配（基类替换、data provider static 化、setUp/tearDown 签名修复）、2 处源代码 `method_exists` 参数修复，以及 PROJECT.md 文档更新。全量测试已通过（318 tests, 2373 assertions，无 deprecation 警告）。

由于放弃 PHP 7.x 向后兼容，构成破坏性变更，以主版本号 2.0.0 发布。

Feature spec 中 Task 11（手工测试）和 Task 12（Code Review）尚未执行，纳入本 release spec 统一编排。

---

## Release 工作项

本次 release 除完成 feature 遗留验证外，还需执行 changelog 整理、SSOT 更新等 release 收尾工作。以下为各工作项的需求定义。

### Requirement 1: Feature 遗留手工测试

**User Story:** 作为 Release_Manager，我希望完成 feature spec 遗留的手工测试，以便在发布前验证升级质量。

#### Acceptance Criteria

1. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `php --version` 报告 PHP 版本 >=8.2
2. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `php vendor/bin/phpunit` 通过全部 318 个测试、2373 个断言，且无 deprecation 警告
3. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `php vendor/bin/phpunit ut/CaesarCipherTest.php` 可作为独立测试文件成功运行
4. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 Composer_Config 在正确的 section 中包含 `"php": ">=8.2"` 和 `"phpunit/phpunit": "^11.0"`
5. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `phpunit.xml` 包含 `cacheDirectory` 属性且不包含 `xmlns:xsi` 或 `xsi:noNamespaceSchemaLocation` 属性
6. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `.gitignore` 包含 `.phpunit.cache/` 条目
7. WHEN Release_Manager 执行手工测试，THE Release_Manager SHALL 验证 `PROJECT.md` 反映 PHP >=8.2、PHPUnit 11.x 和命令 `php vendor/bin/phpunit`

### Requirement 2: Feature 遗留 Code Review

**User Story:** 作为 Release_Manager，我希望完成 feature spec 遗留的 Code Review，以便在发布前确认代码质量和正确性。

#### Acceptance Criteria

1. WHEN Release_Manager 发起 Code Review，THE Release_Manager SHALL 委托 code-reviewer sub-agent 对 upgrade-to-php8 feature 引入的全部变更执行完整 review
2. WHEN code-reviewer sub-agent 报告问题，THE Release_Manager SHALL 在继续后续 release 工作前解决所有报告的问题

### Requirement 3: Changelog 整理

**User Story:** 作为 Release_Manager，我希望将 changelog 从 unreleased 整理到版本目录，以便发布历史遵循项目的 changelog 约定。

#### Acceptance Criteria

1. WHEN Release_Manager 整理 changelog，THE Changelog_System SHALL 包含 `docs/changes/2.0.0/` 目录
2. WHEN Release_Manager 整理 changelog，THE Changelog_System SHALL 包含 `docs/changes/2.0.0/CHANGELOG.md` 文件，格式遵循 `docs/changes/README.md` 中定义的版本 CHANGELOG.md 模板
3. THE `docs/changes/2.0.0/CHANGELOG.md` SHALL 在 upgrade-to-php8 feature 标题下包含 `docs/changes/unreleased/upgrade-to-php8.md` 中的全部变更条目
4. WHEN 版本化 changelog 创建完成，THE Release_Manager SHALL 移除 `docs/changes/unreleased/upgrade-to-php8.md`
5. WHEN 全局 `docs/changes/CHANGELOG.md` 存在，THE Release_Manager SHALL 在顶部追加 v2.0.0 摘要条目；IF 全局文件不存在，THEN THE Release_Manager SHALL 按 `docs/changes/README.md` 中定义的格式创建该文件并写入 v2.0.0 条目

### Requirement 4: SSOT 工程约束更新

**User Story:** 作为 Release_Manager，我希望在 SSOT 文档中更新工程约束信息，以便 `docs/state/` 准确反映系统当前的技术要求。

#### Acceptance Criteria

1. WHEN Release_Manager 更新 SSOT，THE SSOT SHALL 包含工程约束信息，包括：PHP 版本要求（>=8.2）、测试框架（PHPUnit 11.x）和包管理器（Composer）
2. THE SSOT 更新 SHALL 放置在新建的 `docs/state/engineering.md` 文件中
3. THE SSOT 更新 SHALL 包含运行时依赖约束 `voku/portable-utf8` ^3.0
4. THE SSOT 更新 SHALL NOT 修改 `docs/state/` 文件中任何现有的接口、行为规则或数据模型描述

### Requirement 5: Release 分支全量验证

**User Story:** 作为 Release_Manager，我希望在 release 分支上执行最终全量验证，以便确认 release 可合并和打 tag。

#### Acceptance Criteria

1. WHEN Release_Manager 执行 release 验证，THE Test_Suite SHALL 通过全部测试，零失败、零 deprecation 警告
2. WHEN Release_Manager 执行 release 验证，THE Release_Manager SHALL 验证 `docs/changes/2.0.0/CHANGELOG.md` 存在且包含 upgrade-to-php8 feature changelog
3. WHEN Release_Manager 执行 release 验证，THE Release_Manager SHALL 验证 `docs/changes/unreleased/upgrade-to-php8.md` 已被移除
4. WHEN Release_Manager 执行 release 验证，THE Release_Manager SHALL 验证 `docs/state/` 包含工程约束信息

### Requirement 6: 功能与 API 不变性

**User Story:** 作为 Release_Manager，我希望确保 release 过程中不引入功能或 API 变更，以便发布范围严格限定在升级和 release 收尾工作。

#### Acceptance Criteria

1. THE Release_Manager SHALL NOT 在 release 过程中修改 `src/` 下的任何源文件
2. THE Release_Manager SHALL NOT 在 release 过程中修改 `ut/` 下的任何测试文件
3. THE Release_Manager SHALL NOT 在 release 过程中修改 `phpunit.xml` 或 `composer.lock`
4. IF code-reviewer sub-agent 发现需要源代码变更的问题，THEN THE Release_Manager SHALL 在做出变更前向用户升级确认

---

## 已知 Issue 评估

### 项目级 Issue

`issues/` 目录下无未解决的项目级 issue。

### Release 系列 Issue

本次 release 尚未进入 stabilize 阶段，无 release 系列 issue。

---

## 发布判定

| 检查项 | 状态 | 说明 |
|--------|------|------|
| Feature spec tasks 完成度 | ⚠️ 待完成 | Task 11（手工测试）、Task 12（Code Review）待 release 阶段执行 |
| 全量测试通过 | ✅ 已通过 | 318 tests, 2373 assertions，无 deprecation 警告（feature 阶段验证） |
| Changelog 整理 | ⏳ 待执行 | `docs/changes/unreleased/` → `docs/changes/2.0.0/` |
| SSOT 更新 | ⏳ 待执行 | `docs/state/` 补充工程约束 |
| 项目级 Issue | ✅ 无阻塞 | 无未解决 issue |
| 功能与 API 不变性 | ✅ 已确认 | release 过程不修改 src/、ut/、phpunit.xml、composer.lock |

### 结论

Feature 代码变更已完成并通过全量测试。Release 可在完成遗留手工测试、Code Review、changelog 整理和 SSOT 更新后，进入最终验证并合并打 tag。

---

## Glossary

- **Release_Manager**: 执行 release 流程的 agent 或开发者
- **Composer_Config**: 项目根目录下的 `composer.json` 文件，声明依赖与元数据
- **Changelog_System**: `docs/changes/` 目录下的变更日志体系，包含 `unreleased/` 和版本目录
- **SSOT**: `docs/state/` 目录下的系统状态文档（Single Source of Truth）
- **Test_Suite**: 由 `phpunit.xml` 定义的全量测试集合（18 个测试文件，318 tests, 2373 assertions）


---

## Socratic Review

### Q1: 文档结构是否符合 release spec 标准？

**A:** 是。原文档使用 feature spec 结构（Requirements Document + Introduction + Glossary + Requirements），已重构为 release spec 结构（Release <version> Requirements + 发布范围 + Feature 概要 + 已知 Issue 评估 + 发布判定）。Release 工作项（7 个 Requirement）保留在 Feature 概要与已知 Issue 评估之间，作为 release 收尾工作的需求定义。

### Q2: 各 Requirement 是否都在描述外部可观察行为？是否混入了实现细节？

**A:** 是，均为外部可观察行为。R1 的 AC 描述的是手工验证步骤（命令输出、文件内容检查），R2 描述 Code Review 流程，R3-R4 描述文档变更的预期结果，R5 描述最终验证检查项，R6 描述不变性约束。无实现细节混入。

### Q3: 是否有遗漏的场景？

**A:** 有一个潜在遗漏：R3（Changelog 整理）AC4 要求移除 `unreleased/upgrade-to-php8.md`，但未明确 `docs/changes/unreleased/` 目录在移除该文件后如果为空是否保留。不过这属于实现细节，不需要在 requirements 中规定。Changelog 整理的流程已由 `docs/changes/README.md` 定义，requirements 引用该规范即可。

### Q4: 各 Requirement 之间是否存在矛盾或重叠？

**A:** R1（手工测试）和 R5（全量验证）存在部分重叠——两者都要求全量测试通过。但这是合理的：R1 是 feature 遗留验证（在 release 工作开始前执行），R5 是 release 最终验证（在所有 release 工作完成后执行）。两次验证的时间点和目的不同，不构成矛盾。

### Q5: 与 goal.md 的 scope / non-goals 是否一致？

**A:** 一致。goal.md 的目标全部有对应 requirement 覆盖：遗留手工测试（R1）、遗留 Code Review（R2）、changelog 整理（R3）、SSOT 更新（R4）、release 验证（R5）。Non-goals（不引入新功能、不改 API、不保留 PHP 7.x 兼容、不编写 CI/CD）由 R6 的不变性约束覆盖。goal.md 中 Q1-Q4 的 clarification 决策全部体现。

### Q6: 已知 Issue 评估是否准确？

**A:** 是。`issues/` 目录下仅有 README.md，无未解决的项目级 issue。本次 release 尚未进入 stabilize 阶段，无 release 系列 issue。评估结论准确。

---

## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 原文档使用 feature spec 结构（`# Requirements Document` + `## Introduction` + `## Glossary` + `## Requirements`），已重构为 release spec 结构（`# Release 2.0.0 Requirements` + `## 发布范围` + `## Feature 概要` + `## Release 工作项` + `## 已知 Issue 评估` + `## 发布判定` + `## Glossary`）
- [语体] 7 个 Requirement 的 User Story 从英文改为中文（`As a ... I want ... so that ...` → `作为 ... 我希望 ... 以便 ...`）
- [内容] 补充 `## 已知 Issue 评估` section，含项目级 Issue 和 Release 系列 Issue 两个子 section（原文档缺失）
- [内容] 补充 `## 发布判定` section，含检查项表格和结论（原文档缺失）
- [内容] 补充 `## 发布范围` 表格，列出 feature 名称、spec 路径、proposal 引用、status 和 tasks 完成状态（原文档缺失）
- [内容] 补充 `## Feature 概要` section（原文档缺失）
- [内容] 补充 `## Socratic Review` section（原文档缺失）
- [术语] 移除 Glossary 中的孤立术语 `Feature_Spec`（未在任何 AC 中作为 Subject 使用）
- [内容] 将 steering 模板中的 "D 系列 Issue" 调整为 "Release 系列 Issue"，与项目 `issues/README.md` 定义的 issue 分类体系一致

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致
- [x] 无 markdown 格式错误
- [x] 一级标题为 `# Release 2.0.0 Requirements`
- [x] `## 发布范围` 存在，含 feature 表格
- [x] `## Feature 概要` 存在，含 feature 概述
- [x] `## 已知 Issue 评估` 存在，含项目级 Issue 和 Release 系列 Issue
- [x] `## 发布判定` 存在，含检查项表格和结论
- [x] Release 工作项（Requirements）存在且包含 6 条 requirement
- [x] Glossary 存在且无孤立术语
- [x] 所有 User Story 使用中文行文
- [x] AC 使用 WHEN/THE/SHALL 语体
- [x] AC Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续无跳号
- [x] AC 不包含实现细节
- [x] Socratic Review 覆盖充分
- [x] goal.md 中 Q1-Q4 决策全部体现
- [x] goal.md 的 5 个目标全部有对应 requirement 覆盖
- [x] Non-goals 由 R6 不变性约束覆盖

### Clarification Round

**状态**: 已完成

**Q1:** R3（Changelog 整理）要求将 feature spec 从 `.kiro/specs/upgrade-to-php8/` 归档到 `docs/changes/2.0.0/specs/`。`docs/changes/README.md` 的流程中明确包含 spec 归档步骤，但当前 R3 的 AC 仅覆盖了 changelog 文件的整理，未包含 spec 归档。是否需要在 R3 中补充 spec 归档的 AC？
- A) 需要，在 R3 中补充 spec 归档 AC（将 `.kiro/specs/upgrade-to-php8/` 移入 `docs/changes/2.0.0/specs/`）
- B) 不需要，spec 归档由 gitflow-finisher 在 release finish 阶段处理，不纳入 release spec 的 requirements
- C) 需要，但作为独立的 Requirement 8 而非 R4 的 AC
- D) 其他（请说明）

**A:** B

**Q2:** R4（SSOT 工程约束更新）的 AC2 将文件选择权交给 Release_Manager 自行判断。`docs/state/` 当前有 5 个文件（crypto.md、data-provider.md、exceptions.md、utils.md、validators.md），均按功能模块组织，没有专门的工程约束文件。工程约束信息（PHP 版本、PHPUnit 版本、Composer、voku/portable-utf8）应放在哪里？
- A) 新建 `docs/state/engineering.md`（或类似名称），集中存放工程约束
- B) 放入 `docs/state/README.md`，作为 state 目录的总览信息
- C) 分散到各模块文件中（如 validators.md 中注明 PHP 版本要求）
- D) 其他（请说明）

**A:** A

**Q3:** R5（Release 分支全量验证）是否需要包含 `composer validate` 检查？当前 R5 作为最终验证未包含此项。如果 R5 之前的步骤（如 changelog 整理）意外影响了 composer.json，最终验证可能遗漏。
- A) 需要，在 R5 中补充 `composer validate` 检查作为最终验证的一部分
- B) 不需要，R6 约束了 release 过程中不修改 composer.lock，composer.json 在本次 release 中不做变更
- C) 其他（请说明）

**A:** B
