# Implementation Plan: Release 2.0.0

## Overview

Execute the release workflow for `oasis/utils` v2.0.0. This release contains a single feature (`upgrade-to-php8`, PRP-001) and involves no source code changes — only verification, configuration, and documentation tasks.

Execution order: R1 (Manual Testing) → R2 (Code Review) → R3/R4 (parallel) → R5 (Final Validation). R6 (Immutability) is a cross-cutting constraint enforced throughout, not a separate task.

## Tasks

- [x] 1. Complete feature manual testing (R1)
  - [x] 1.1 Verify PHP runtime version
    - Run `php --version` and confirm output shows PHP >= 8.2
    - _Ref: R1 AC1_
  - [x] 1.2 Run full test suite
    - Run `php vendor/bin/phpunit` and verify: 318 tests, 2373 assertions, all pass, zero failures, zero errors, no PHP deprecation warnings, no PHPUnit deprecation warnings
    - _Ref: R1 AC2_
  - [x] 1.3 Verify CaesarCipherTest independent execution
    - Run `php vendor/bin/phpunit ut/CaesarCipherTest.php` and confirm it runs successfully as a standalone test file
    - _Ref: R1 AC3_
  - [x] 1.4 Verify Composer dependency declarations
    - Check `composer.json`: `require` section contains `"php": ">=8.2"`, `require-dev` section contains `"phpunit/phpunit": "^11.0"`
    - _Ref: R1 AC4_
  - [x] 1.5 Verify phpunit.xml format
    - Check `phpunit.xml`: contains `cacheDirectory` attribute, does NOT contain `xmlns:xsi` attribute, does NOT contain `xsi:noNamespaceSchemaLocation` attribute
    - _Ref: R1 AC5_
  - [x] 1.6 Verify .gitignore entry
    - Check `.gitignore` contains `.phpunit.cache/` entry
    - _Ref: R1 AC6_
  - [x] 1.7 Verify PROJECT.md content
    - Check `PROJECT.md` reflects: PHP >= 8.2, PHPUnit 11.x, test command `php vendor/bin/phpunit`
    - _Ref: R1 AC7_
  - [x] 1.8 Checkpoint: All 7 manual testing checks passed with no failures

- [x] 2. Complete feature Code Review (R2)
  - [x] 2.1 Delegate code review to code-reviewer sub-agent
    - Invoke `code-reviewer` sub-agent to review all changes introduced by the `upgrade-to-php8` feature
    - If code-reviewer reports issues: resolve all issues before proceeding to subsequent tasks
    - If issues require `src/` or `ut/` changes: escalate to user for confirmation per R7 AC4
    - _Ref: R2 AC1, R2 AC2_
  - [x] 2.2 Checkpoint: R1 manual testing passed all 7 checks and R2 Code Review is resolved. Ask the user if questions arise.

- [x] 3. Organize changelog (R3)
  - [x] 3.1 Create version CHANGELOG.md
    - Create `docs/changes/2.0.0/CHANGELOG.md` using simplified list format (no Changed/Added/Fixed sub-headings) per `docs/changes/README.md` template and design GK Q1 decision
    - Include all change entries from `docs/changes/unreleased/upgrade-to-php8.md` under the upgrade-to-php8 feature heading
    - Include sections: 包含的 Feature, 修复的 Issue, 工程变更, 测试覆盖
    - _Ref: R3 AC1, R3 AC2, R3 AC3_
  - [x] 3.2 Remove unreleased changelog file
    - Delete `docs/changes/unreleased/upgrade-to-php8.md`
    - _Ref: R3 AC4_
  - [x] 3.3 Create global CHANGELOG.md
    - Create `docs/changes/CHANGELOG.md` (does not currently exist) per `docs/changes/README.md` global format
    - Include v2.0.0 summary entry with actual date (YYYY-MM-DD format) and link to `2.0.0/CHANGELOG.md`
    - _Ref: R3 AC5_
  - [x] 3.4 Checkpoint: `docs/changes/2.0.0/CHANGELOG.md` exists with correct content, `docs/changes/unreleased/upgrade-to-php8.md` is removed, `docs/changes/CHANGELOG.md` exists with v2.0.0 entry

- [x] 4. Update SSOT engineering constraints (R4)
  - [x] 4.1 Create docs/state/engineering.md
    - Create `docs/state/engineering.md` with engineering constraints: PHP >= 8.2, PHPUnit 11.x, Composer, voku/portable-utf8 ^3.0
    - Include sections: 运行时要求, 依赖 (运行时/开发), 包管理, 测试
    - Do NOT modify any existing files in `docs/state/` (crypto.md, data-provider.md, exceptions.md, utils.md, validators.md)
    - _Ref: R4 AC1, R4 AC2, R4 AC3, R4 AC4_
  - [x] 4.2 Update docs/state/README.md file index
    - Append `engineering.md` row to the file index table in `docs/state/README.md`: `| engineering.md | 工程约束（PHP 版本、依赖、测试框架、包管理） |`
    - _Ref: R4 AC1_
  - [x] 4.3 Checkpoint: `docs/state/engineering.md` exists with correct content, `docs/state/README.md` file index includes `engineering.md` row, no existing state files modified

- [x] 5. Final release validation (R5)
  - [x] 5.1 Run full test suite
    - Run `php vendor/bin/phpunit` and verify: all tests pass, zero failures, zero deprecation warnings
    - _Ref: R5 AC1_
  - [x] 5.2 Verify version changelog exists
    - Check `docs/changes/2.0.0/CHANGELOG.md` exists and contains upgrade-to-php8 feature changelog content
    - _Ref: R5 AC2_
  - [x] 5.3 Verify unreleased file removed
    - Check `docs/changes/unreleased/upgrade-to-php8.md` has been removed
    - _Ref: R5 AC3_
  - [x] 5.4 Verify SSOT engineering constraints
    - Check `docs/state/engineering.md` exists and contains: PHP >= 8.2, PHPUnit 11.x, Composer, voku/portable-utf8 ^3.0
    - _Ref: R5 AC4_
  - [x] 5.5 Checkpoint: All 4 validation checks passed. The release branch is ready for merge and tag via gitflow-finisher. Ask the user if questions arise.

## Notes

- R6 (Immutability constraint) is enforced throughout: do NOT modify `src/`, `ut/`, `phpunit.xml`, or `composer.lock` during any task. If code-reviewer discovers issues requiring source code changes, escalate to user for confirmation (R6 AC4).
- Tasks 3 and 4 (R3 and R4) are independent and can run in parallel — no data dependency between them (per design GK Q2 decision)
- docs/state/README.md update is part of Task 4 (R4), not a separate task (per design GK Q3 decision)
- No property-based tests: this release involves no business logic changes
- Changelog uses simplified list format (no Changed/Added/Fixed sub-categories) per design GK Q1 decision
- All document content should follow project convention (Chinese)

## Socratic Review

### Q1: Tasks 是否完整覆盖了 design 中的所有实现项？

**A:** 是。Design 中 6 个 component（§1-§2, §4-§7）全部有对应 task：§1→Task 1, §2→Task 2, §4→Task 3, §5→Task 4, §6→Task 5, §7→Notes 中的 cross-cutting constraint。§3（版本号声明）已按用户决策移除。无遗漏。

### Q2: Task 之间的依赖顺序是否正确？

**A:** 是。执行顺序 Task 1→2→3/4(parallel)→5 与 design 中的执行顺序依赖图一致：R1→R2→R3/R4→R5。Task 3 和 4 无数据依赖，可并行。

### Q3: 每个 task 的粒度是否合适？

**A:** 合适。每个 sub-task 对应一个具体的可执行操作（运行命令、检查文件、创建文件、删除文件），不存在过粗（多个不相关操作合并）或过细（琐碎操作单独列出）的情况。

### Q4: Checkpoint 的设置是否覆盖了关键阶段？

**A:** 是。每个 top-level task 都有 checkpoint sub-task：Task 1（手工测试全部通过）、Task 2（Code Review 通过，含 R1+R2 综合确认）、Task 3（changelog 文件就位）、Task 4（SSOT 文件就位）、Task 5（全量验证通过，release ready）。

### Q5: 标注为可并行的 task 是否真的满足并行条件？

**A:** 是。Task 3（R3 changelog 整理）操作 `docs/changes/` 目录，Task 4（R4 SSOT 更新）操作 `docs/state/` 目录。两者不修改同一文件，不存在调用依赖。

### Q6: Design GK Clarification Round 的决策是否在 tasks 中得到体现？

**A:** 是。三个决策全部体现：
- Q1（简化纯列表格式）→ Task 4.1 明确 "simplified list format (no Changed/Added/Fixed sub-headings)"
- Q2（R3 和 R4 独立并行）→ Tasks 3 和 4 为独立 top-level task，Notes 中标注可并行
- Q3（README.md 更新作为 R4 一部分）→ Task 4.2 在 Task 4 内部完成 README.md 更新


## Gatekeep Log

**校验时间**: 2025-07-15
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 独立 checkpoint top-level task（原 Task 3、Task 8）合并为所属 top-level task 的最后一个 sub-task。Checkpoint 不应作为独立 top-level task 存在。
- [结构] 原 Task 1、4、5、6 缺少 checkpoint sub-task，已为每个 top-level task 补充 checkpoint 作为最后一个 sub-task。
- [格式] sub-task 引用格式从 `_Requirements: R1 AC1_` 统一修正为 `_Ref: R1 AC1_`，符合 steering 规定的引用格式。
- [结构] 合并 checkpoint 后 top-level task 从 8 个重编号为 6 个（Task 1-6），sub-task 编号同步更新。
- [结构] 补充 `## Socratic Review` section，覆盖 design 全覆盖、依赖顺序、粒度、checkpoint、并行条件、Design GK CR 决策体现等 6 个维度。
- [内容] Notes section 中 R7 说明补充了 AC4（源代码变更需升级确认）的具体描述，使 cross-cutting constraint 的执行指引更完整。
- [内容] Notes section 中补充了 Design GK Q1/Q2/Q3 决策的明确引用标注，便于追溯。

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（R1-R6 编号与 requirements.md 一致）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号（1-5），连续无跳号
- [x] sub-task 有层级序号（N.1, N.2...），连续无跳号
- [x] 每个实现类 sub-task 引用了具体的 requirements 条款（`_Ref: RX ACY_` 格式）
- [x] requirements.md 中 R1-R6 全部被 task 覆盖（R1→T1, R2→T2, R3→T3, R4→T4, R5→T5, R6→Notes）
- [x] 无悬空引用（所有引用的 R/AC 编号在 requirements.md 中存在）
- [x] top-level task 按依赖关系排序（T1→T2→T3/T4→T5）
- [x] 无循环依赖
- [x] 并行标注合理（T3 和 T4 不修改同一文件，无调用依赖）
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 包含具体的验证方式
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory
- [x] Socratic Review 存在且覆盖充分
- [x] Design GK CR 决策全部体现（Q1 简化列表格式、Q2 R3/R4 并行、Q3 README 更新归属 R4）
- [x] Design 全覆盖（§1-§2, §4-§7 全部有对应 task，§3 已按用户决策移除）
- [x] 验收闭环完整（checkpoint + R5 全量验证 + Code Review）
- [x] 执行路径无歧义
