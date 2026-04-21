# Tasks

## Tasks

- [x] 1. Write bug condition exploration test
  - [x] 1.1 Create test file and implement bug condition test cases
    - **Property 1: Bug Condition** — M/G Unit_Conversion 产生非整数值
    - **CRITICAL**: This test MUST FAIL on unfixed code — failure confirms the bug exists
    - **DO NOT attempt to fix the test or the code when it fails**
    - **NOTE**: This test encodes the expected behavior — it will validate the fix when it passes after implementation
    - **GOAL**: Surface counterexamples that demonstrate the bug exists
    - **Scoped PBT Approach**: Scope the property to concrete failing cases — construct `newLimitBytes` values where K→M or M→G conversion produces non-integer results
    - Create test file `ut/MemoryMonitorBugConditionTest.php`
    - Extract the Unit_Conversion logic into a testable helper or test the conversion inline:
      - K→M bug case: construct `newLimitBytes` where `ceil(bytes/1024)` (K value) is NOT a multiple of 1024, e.g. `324_534_272` → K=`316928` → M=`ceil(316928/1024*100)/100` = `309.50` (non-integer)
      - M→G bug case: construct `newLimitBytes` where M value is NOT a multiple of 1024, e.g. `1_500_000_000` → eventually M=`1430.51` → G=`ceil(1430.51/1024*100)/100` = `1.40` (non-integer)
    - Test assertions should match Expected Behavior: converted value MUST match `/^\d+[MG]$/` (integer + unit suffix, no decimals)
    - Register test file in `phpunit.xml` testsuite
    - _Requirements: 1.1, 1.2, 2.1, 2.2_
  - [x] 1.2 Run test on unfixed code and document counterexamples
    - Run `ut/MemoryMonitorBugConditionTest.php` on UNFIXED code
    - **EXPECTED OUTCOME**: Test FAILS (this is correct — it proves the bug exists via non-integer M/G values)
    - Document counterexamples found to confirm root cause: `ceil($x / 1024 * 100) / 100` produces decimals
    - Mark task complete when test is written, run, and failure is documented
    - _Requirements: 1.1, 1.2_
  - [x] 1.3 Checkpoint: 测试文件已创建、已注册到 phpunit.xml、运行失败已记录（确认 bug 存在）

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - [x] 2.1 Create test file and implement preservation tests
    - **Property 2: Preservation** — Non-M/G Unit_Conversion 行为不变
    - **IMPORTANT**: Follow observation-first methodology
    - Create test file `ut/MemoryMonitorPreservationTest.php`
    - Observe on UNFIXED code:
      - K range (1024 < value ≤ 1024²): e.g. `500_000` → `ceil(500000/1024)` = `489` → `"489K"` — integer, no bug
      - No-unit range (value ≤ 1024): e.g. `800` → `"800"` — no suffix
      - K range boundary: `1_048_576` (= 1024²) → K=`1024` → triggers M conversion → `"1M"` (integer, edge case)
    - Write property-based tests (using PHPUnit data providers with representative + random values):
      - For K-range inputs: assert conversion result matches `/^\d+K$/` and equals `ceil(bytes/1024) . "K"`
      - For no-unit inputs: assert conversion result matches `/^\d+$/` (no suffix)
      - For M/G integer-multiple inputs: assert conversion still produces valid integer results
    - Register test file in `phpunit.xml` testsuite
    - _Requirements: 3.3, 3.4_
  - [x] 2.2 Run preservation tests on unfixed code
    - Verify tests PASS on UNFIXED code (these paths are not affected by the bug)
    - **EXPECTED OUTCOME**: Tests PASS (confirms baseline behavior to preserve)
    - _Requirements: 3.3, 3.4_
  - [x] 2.3 Checkpoint: 测试文件已创建、已注册到 phpunit.xml、在未修复代码上运行通过

- [x] 3. Fix Unit_Conversion formula (core bug fix)
  - [x] 3.1 Fix M/G conversion in `monitorMemoryUsage()`
    - In `src/CommonUtils.php`, method `monitorMemoryUsage()`
    - Change M conversion: `$newLimit = ceil($newLimit / 1024 * 100) / 100;` → `$newLimit = ceil($newLimit / 1024);`
    - Change G conversion: `$newLimit = ceil($newLimit / 1024 * 100) / 100;` → `$newLimit = ceil($newLimit / 1024);`
    - K conversion `ceil($newLimit / 1024)` remains unchanged
    - No other changes to this method in this sub-task
    - _Bug_Condition: isBugCondition(input) where ceil(value/1024*100)/100 != floor(ceil(value/1024*100)/100)_
    - _Expected_Behavior: Unit_Conversion result always matches `/^\d+[KMG]?$/` — integer value + optional unit suffix_
    - _Preservation: K range conversion, no-unit conversion, threshold logic, CLI log output unchanged_
    - _Requirements: 2.1, 2.2, 2.3_
  - [x] 3.2 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** — M/G Unit_Conversion 产生合法整数值
    - **IMPORTANT**: Re-run the SAME test from task 1 — do NOT write a new test
    - The test from task 1 encodes the expected behavior (integer M/G values)
    - When this test passes, it confirms the expected behavior is satisfied
    - Run `ut/MemoryMonitorBugConditionTest.php`
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1, 2.2_
  - [x] 3.3 Verify preservation tests still pass
    - **Property 2: Preservation** — Non-M/G Unit_Conversion 行为不变
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Run `ut/MemoryMonitorPreservationTest.php`
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all preservation tests still pass after fix
    - _Requirements: 3.3, 3.4_
  - [x] 3.4 Checkpoint: bug condition 测试通过、preservation 测试通过，执行 `vendor/bin/phpunit` 全量测试确认无回归

- [x] 4. Add Global_Switch (enable/disable memory monitor)
  - [x] 4.1 Add static property and methods to `CommonUtils`
    - In `src/CommonUtils.php`:
    - Add `private static bool $memoryMonitorEnabled = true;` static property
    - Add `public static function enableMemoryMonitor(): void` — sets `$memoryMonitorEnabled = true`
    - Add `public static function disableMemoryMonitor(): void` — sets `$memoryMonitorEnabled = false`
    - Add early return at the top of `monitorMemoryUsage()` BEFORE `static $isLowest` declaration:
      ```php
      if (!self::$memoryMonitorEnabled) {
          return;
      }
      ```
    - enable/disable 仅控制是否执行，不影响 `$isLowest`、`$neverReset` 等内部状态
    - _Requirements: 1.4, 2.4, 3.6_
  - [x] 4.2 Write Global_Switch unit tests
    - Create test file `ut/MemoryMonitorGlobalSwitchTest.php`
    - Test cases:
      - `disableMemoryMonitor()` 后 `monitorMemoryUsage()` 不执行 `ini_set`（memory_limit 不变）
      - `enableMemoryMonitor()` 后恢复正常执行
      - 默认状态（未调用 disable）时正常执行监控逻辑
      - enable/disable 不重置 `$isLowest`、`$neverReset` 内部状态
    - Register test file in `phpunit.xml` testsuite
    - _Requirements: 2.4, 3.6_
  - [x] 4.3 Checkpoint: 执行 `vendor/bin/phpunit` 全量测试，所有测试通过，无 warnings、无 deprecations

- [x] 5. Update state documentation
  - [x] 5.1 Update `docs/state/utils.md`
    - CommonUtils section:
      - Add `enableMemoryMonitor()` / `disableMemoryMonitor()` to method table
      - Add Global_Switch behavior description to 内存监控行为 section
      - Note that M/G Unit_Conversion now produces integer values (ceil)
    - _Requirements: 2.4, 3.6_
  - [x] 5.2 Checkpoint: 文档更新完成，内容与实现一致

- [x] 6. Manual testing
  - [x] 6.1 验证 M 范围非整数倍场景
    - 设置 `memory_limit` 为较低值，构造高内存使用率触发 Memory_Monitor 扩容
    - 确认 `ini_get('memory_limit')` 返回整数值 + M 后缀（如 `318M`），无 `E_WARNING`
    - 确认 `error_get_last()` 未被 warning 覆盖
    - _Requirements: 1.3, 2.1, 2.3_
  - [x] 6.2 验证 Global_Switch 禁用场景
    - 调用 `CommonUtils::disableMemoryMonitor()`
    - 触发高内存使用率条件
    - 确认 `memory_limit` 未被调整
    - 调用 `CommonUtils::enableMemoryMonitor()` 后确认恢复正常
    - _Requirements: 2.4, 3.6_
  - [x] 6.3 验证现有行为保留
    - 在 K 范围内触发 Memory_Monitor，确认输出格式不变（整数 + K 后缀）
    - 确认 CLI 模式下 stderr 日志输出正常
    - _Requirements: 3.1, 3.2, 3.4, 3.5_

- [x] 7. Code review
  - Delegate to `code-reviewer` sub-agent
  - Review all changes in this spec: `src/CommonUtils.php`, new test files, `docs/state/utils.md`, `phpunit.xml`

---

## Socratic Review

**Q: tasks 是否完整覆盖了 design 中的所有实现项？**
A: 是。Design 中的四项实现（M 转换修复、G 转换修复、Global_Switch 属性/方法、入口开关检查）分别对应 task 3.1 和 task 4.1。Testing Strategy 中的三类测试（Bug Condition、Preservation、Global_Switch）对应 tasks 1、2、4.2。State 文档更新对应 task 5。

**Q: task 之间的依赖顺序是否正确？**
A: 是。遵循 design CR 中用户选择的 A）顺序：先确认 bug 存在（task 1）→ 建立 preservation 基线（task 2）→ 修复核心 bug（task 3）→ 添加 Global_Switch（task 4）→ 更新文档（task 5）→ 手工测试（task 6）→ code review（task 7）。每个 task 的前置依赖都已完成。

**Q: 每个 task 的粒度是否合适？**
A: 合适。每个 sub-task 聚焦单一职责：创建测试文件、运行验证、修改代码、更新文档。无过粗（多个不相关实现合并）或过细（琐碎操作单独列出）的情况。

**Q: checkpoint 的设置是否覆盖了关键阶段？**
A: 是。每个 top-level task 末尾都有 checkpoint，覆盖了：bug 确认（task 1）、基线建立（task 2）、修复验证（task 3）、功能完整性（task 4）、文档同步（task 5）。

**Q: 手工测试是否覆盖了 requirements 中的关键用户场景？**
A: 是。手工测试覆盖了三个关键场景：M 范围非整数倍（核心 bug 场景）、Global_Switch 禁用/启用、现有行为保留（K 范围 + CLI 日志）。

**Q: Design CR 中的决策是否在 tasks 编排中体现？**
A: 是。CR Q1 选择 A）先修复再 Global_Switch → tasks 3 在 4 之前。CR Q2 选择 A）独立 task 确认 bug → task 1 独立。CR Q3 选择 B）独立收尾 task 更新文档 → task 5 在所有代码完成后。

---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 一级标题从 `# Implementation Plan` 改为 `# Tasks`，补充 `## Tasks` section header
- [结构] 将独立的 Checkpoint top-level task（原 task 5）拆解为各 top-level task 的最后一个 sub-task
- [结构] 补充手工测试 top-level task（task 6），覆盖 M 范围非整数倍、Global_Switch、现有行为保留三个场景
- [格式] 为 tasks 1、2 补充 sub-task 层级序号（1.1/1.2/1.3、2.1/2.2/2.3）
- [结构] 补充 `## Socratic Review` section
- [内容] 补充遗漏的 requirement 引用（1.3→手工测试 6.1、1.4→task 4.1、3.2→手工测试 6.3）

### 合规检查
- [x] 无 TBD / TODO / 占位符
- [x] 无空 section
- [x] 内部引用一致（requirement 编号与 bugfix.md 对应）
- [x] checkbox 语法正确（`- [ ]`）
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 倒数第一个 top-level task 是 Code Review
- [x] 倒数第二个 top-level task 是手工测试
- [x] 自动化实现 task 排在手工测试和 Code Review 之前
- [x] top-level task 有序号，sub-task 有层级序号
- [x] 序号连续无跳号
- [x] 每个实现类 sub-task 引用了 requirements 条款
- [x] requirements 中每条 requirement 至少被一个 task 引用
- [x] 引用的 requirement 编号在 bugfix.md 中存在
- [x] top-level task 按依赖关系排序
- [x] 无循环依赖
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 包含具体验证命令或验证方式
- [x] 遵循 test-first 编排（tasks 1、2 先写测试，task 3 先修复再验证）
- [x] 每个 sub-task 可独立执行
- [x] 无过粗或过细的 task
- [x] 所有 task 均为 mandatory
- [x] 手工测试覆盖关键用户场景
- [x] Code Review 委托给 code-reviewer sub-agent
- [x] Socratic Review 覆盖充分
- [x] Design CR 决策已在 task 编排中体现
- [x] Design 全覆盖
- [x] 验收闭环完整（checkpoint + 手工测试 + code review）
