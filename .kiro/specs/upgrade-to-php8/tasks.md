# Implementation Plan: Upgrade to PHP 8

## Overview

将 `oasis/utils` 项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP >=8.2 + PHPUnit ^11.0。严格按依赖链顺序执行：composer.json → composer.lock → phpunit.xml → 测试代码 → 源代码 → 验证 → 文档。

## Tasks

- [x] 1. 更新 Composer 依赖声明
  - [x] 1.1 修改 `composer.json` 中的版本约束
    - 在 `require` 段添加 `"php": ">=8.2"`
    - 将 `require-dev` 中 `phpunit/phpunit` 从 `^5.1` 改为 `^11.0`
    - 保持 `voku/portable-utf8` 为 `^3.0` 不变
    - _Requirements: R1.1, R1.2, R1.3, R1.4_
  - [x] 1.2 重新生成 `composer.lock`
    - 在 PHP >=8.2 环境下执行 `composer update` 重新生成 lock 文件
    - 验证 `composer install` 成功完成且无错误
    - _Requirements: R8.1, R8.2, R8.3, R1.5_
  - [x] 1.3 Checkpoint: 验证 `composer install` 在 PHP >=8.2 下成功完成，无依赖解析错误

- [x] 2. 更新 PHPUnit 配置文件
  - [x] 2.1 迁移 `phpunit.xml` 到 PHPUnit 11.x 格式
    - 移除 `xmlns:xsi` 和 `xsi:noNamespaceSchemaLocation` 属性（PHPUnit 11 不再提供外部 XSD）
    - 在 `<phpunit>` 根元素添加 `cacheDirectory=".phpunit.cache"` 属性
    - 保留现有 `bootstrap="ut/bootstrap.php"` 不变
    - 保留现有 17 个 `<file>` 元素的 test suite 定义不变
    - _Requirements: R2.1, R2.2, R2.3, R2.4_
  - [x] 2.2 Checkpoint: 验证 `php vendor/bin/phpunit --version` 正常输出版本号，phpunit.xml 被正确解析

- [x] 3. 添加 `.phpunit.cache/` 到 `.gitignore`
  - [x] 3.1 在 `.gitignore` 中添加 `.phpunit.cache/` 条目
    - PHPUnit 11 会在项目根目录生成缓存目录，需排除出版本控制
    - _Requirements: R2.4_
  - [x] 3.2 Checkpoint: 确认 `.gitignore` 包含新条目

- [x] 4. 测试代码适配——基类替换
  - [x] 4.1 将全部 17 个 suite 内测试文件的基类从 `PHPUnit_Framework_TestCase` 替换为 `\PHPUnit\Framework\TestCase`
    - 在每个文件顶部添加 `use PHPUnit\Framework\TestCase;`
    - 将 `extends PHPUnit_Framework_TestCase` 改为 `extends TestCase`
    - 受影响文件：`ut/Rc4Test.php`, `ut/StringUtilsTest.php`, `ut/MlibDataProviderTest.php`, `ut/DataPackerTest.php`, `ut/StringValidatorTest.php`, `ut/IntegerValidatorTest.php`, `ut/FloatValidatorTest.php`, `ut/ObjectValidatorTest.php`, `ut/BooleanValidatorTest.php`, `ut/ArrayValidatorTest.php`, `ut/StringLengthValidatorTest.php`, `ut/TrimmedStringValidatorTest.php`, `ut/EmailValidatorTest.php`, `ut/UrlValidatorTest.php`, `ut/RegexValidatorTest.php`, `ut/EnumerationValidatorTest.php`, `ut/ChainedValidatorTest.php`
    - _Requirements: R3.1, R3.6_
  - [x] 4.2 Checkpoint: 确认所有 17 个文件均已完成基类替换，无遗漏

- [x] 5. 测试代码适配——Data Provider 方法 static 化
  - [x] 5.1 为全部使用 `@dataProvider` 的测试文件中的 data provider 方法添加 `static` 关键字
    - 将 `public function xxxProvider()` 改为 `public static function xxxProvider()`
    - 受影响文件及方法数：`ArrayValidatorTest`(6), `BooleanValidatorTest`(4), `ChainedValidatorTest`(2), `EmailValidatorTest`(2), `EnumerationValidatorTest`(2), `FloatValidatorTest`(4), `IntegerValidatorTest`(4), `MlibDataProviderTest`(1), `ObjectValidatorTest`(4), `RegexValidatorTest`(2), `StringLengthValidatorTest`(2), `StringValidatorTest`(6), `TrimmedStringValidatorTest`(2), `UrlValidatorTest`(2)
    - _Requirements: R3.4_
  - [x] 5.2 移除 `ut/MlibDataProviderTest.php` 中 `testNull()` 方法上的空 `@dataProvider` annotation
    - 该 annotation 无方法名引用，PHPUnit 11 会报错
    - _Requirements: R3.4, R3.6_
  - [x] 5.3 Checkpoint: 确认所有 data provider 方法已添加 `static`，空 annotation 已移除

- [x] 6. 测试代码适配——setUp/tearDown 签名修复
  - [x] 6.1 为 `setUp()` 和 `tearDown()` 方法添加 `: void` 返回类型声明
    - `ut/DataPackerTest.php`：`setUp()` 和 `tearDown()` 添加 `: void`
    - `ut/MlibDataProviderTest.php`：`setUp()` 添加 `: void`
    - _Requirements: R3.2_
  - [x] 6.2 Checkpoint: 确认受影响的 2 个文件中方法签名已修复

- [x] 7. CaesarCipherTest.php 适配并加入 test suite
  - [x] 7.1 适配 `ut/CaesarCipherTest.php` 的 PHPUnit 11 API
    - 添加 `use PHPUnit\Framework\TestCase;`
    - 将 `extends PHPUnit_Framework_TestCase` 改为 `extends TestCase`
    - 该文件无 data provider、无 setUp/tearDown，仅需基类替换
    - _Requirements: R3.1_
  - [x] 7.2 将 `ut/CaesarCipherTest.php` 加入 `phpunit.xml` 的 test suite 定义
    - 在 `<testsuite name="all">` 中添加 `<file>ut/CaesarCipherTest.php</file>`
    - _Requirements: R2.2（Design CR Q1 决策：单独 task 处理并加入 suite）_
  - [x] 7.3 Checkpoint: 验证 CaesarCipherTest 已适配且已加入 suite

- [x] 8. 源代码 PHP 8 兼容性修复
  - [x] 8.1 修复 `src/Validators/StringValidator.php` 中 `method_exists` 参数错误
    - 将 `method_exists($target, '__toString()')` 改为 `method_exists($target, '__toString')`
    - _Requirements: R4.1, R4.2, R4.5_
  - [x] 8.2 修复 `src/Validators/TrimmedStringValidator.php` 中 `method_exists` 参数错误
    - 将 `method_exists($target, '__toString()')` 改为 `method_exists($target, '__toString')`
    - _Requirements: R4.1, R4.2, R4.5_
  - [x] 8.3 Checkpoint: 确认 2 处源代码修复完成，公共 API 和方法签名未变（R4.3, R4.4）

- [x] 9. 全量测试验证
  - [x] 9.1 执行 `php vendor/bin/phpunit` 验证全部测试通过
    - 确认所有 18 个测试文件（含新加入的 CaesarCipherTest）通过
    - 确认无 PHP deprecation 警告
    - 确认无 PHPUnit deprecation 警告
    - 确认测试用例总数与预期一致（不删除、不跳过）
    - _Requirements: R5.1, R5.2, R5.3, R5.4_
  - [x] 9.2 Checkpoint: 全量测试通过，无警告，测试覆盖范围完整

- [x] 10. 文档更新
  - [x] 10.1 更新 `PROJECT.md` 中的版本信息和构建命令
    - PHP 版本：从 `≥ 5.6（推断自 PHPUnit 5.x）` 改为 `>=8.2`
    - 测试框架：从 `PHPUnit 5.x` 改为 `PHPUnit 11.x`
    - 测试命令：从 `php74 vendor/bin/phpunit` 改为 `php vendor/bin/phpunit`
    - 移除关于 `php74` 别名的注意事项说明
    - _Requirements: R6.1, R6.2, R6.3, R6.4_
  - [x] 10.2 确认 `docs/state/` 文件无需修改
    - 经 design 阶段分析，`docs/state/` 中的文件不包含 PHP 版本或 PHPUnit 版本的直接引用，无需修改
    - _Requirements: R7.1, R7.2_
  - [x] 10.3 Checkpoint: 确认 PROJECT.md 已更新，docs/state/ 确认无需变更

- [ ] 11. 手工测试
  - [ ] 11.1 手工验证升级结果
    - 执行 `php --version` 确认 PHP 版本 >=8.2
    - 执行 `php vendor/bin/phpunit` 确认全量测试通过且无警告
    - 执行 `php vendor/bin/phpunit ut/CaesarCipherTest.php` 确认新加入 suite 的文件可单独运行
    - 检查 `composer.json` 中版本约束正确
    - 检查 `phpunit.xml` 格式正确，无过时属性
    - 检查 `.gitignore` 包含 `.phpunit.cache/`
    - 检查 `PROJECT.md` 中命令和版本信息已更新

- [ ] 12. Code Review
  - [ ] 12.1 委托 code-reviewer sub-agent 执行全量 review
    - _Requirements: R3.3, R3.5, R3.7, R4.3, R4.4_

## Notes

- 严格按依赖链顺序执行：composer.json → composer.lock → phpunit.xml → 测试代码 → 源代码 → 验证 → 文档（Design CR Q3 决策）
- 测试代码适配按变更类型拆分为 3 个 task：基类替换、data provider static 化、setUp/tearDown 签名修复（Design CR Q2 决策）
- CaesarCipherTest.php 单独作为一个 task 处理并加入 phpunit.xml suite（Design CR Q1 决策）
- `.gitignore` 添加 `.phpunit.cache/` 作为独立小 task（Design CR Q4 决策）
- PBT 不适用于本次升级（配置迁移和兼容性修复任务，正确性由现有测试套件通过来保证）
- 所有 task 均为 mandatory，无 optional task

---

## Socratic Review

### Q1: Tasks 是否严格遵循了 Design CR Q3 的依赖链顺序？

**A:** 是。执行顺序为：Task 1（composer.json + composer.lock）→ Task 2（phpunit.xml）→ Task 3（.gitignore）→ Task 4-7（测试代码适配）→ Task 8（源代码修复）→ Task 9（全量验证）→ Task 10（文档）。完全符合 `composer.json → composer.lock → phpunit.xml → 测试代码 → 源代码 → 验证 → 文档` 的依赖链。

### Q2: Design CR Q1/Q2/Q4 的决策是否全部体现？

**A:** 是。
- CR Q1（CaesarCipherTest 单独 task + 加入 suite）→ Task 7
- CR Q2（按变更类型拆分）→ Task 4（基类替换）、Task 5（data provider static 化）、Task 6（setUp/tearDown 签名）
- CR Q4（.gitignore 独立小 task）→ Task 3

### Q3: 是否覆盖了 requirements 中的全部 8 个 requirement？

**A:** 是。
- R1（Composer 依赖）→ Task 1.1
- R2（PHPUnit 配置）→ Task 2.1, Task 7.2
- R3（测试代码适配）→ Task 4, 5, 6, 7.1
- R4（源代码兼容性）→ Task 8
- R5（全量测试通过）→ Task 9
- R6（构建命令更新）→ Task 10.1
- R7（状态文档同步）→ Task 10.2
- R8（composer.lock 重新生成）→ Task 1.2

### Q4: Task 结构是否符合 spec-planning 规则？

**A:** 是。
- 所有 task 均为 mandatory，无 optional 标记
- Checkpoint 作为每个 top-level task 的最后一个 sub-task
- 结构为：1-10 实现 task → 11 手工测试 → 12 Code Review
- 本次升级为配置迁移任务，Test First 原则不直接适用（不引入新业务逻辑，正确性由现有测试套件验证）

### Q5: 是否存在遗漏的变更点？

**A:** 无遗漏。Design 中识别的所有变更点均已覆盖：
- 2 处 `method_exists` 源代码修复 → Task 8
- 17+1 个测试文件基类替换 → Task 4 + Task 7
- 14 个文件的 data provider static 化 → Task 5
- 2 个文件的 setUp/tearDown 签名修复 → Task 6
- 1 处空 `@dataProvider` annotation 移除 → Task 5.2
- phpunit.xml 格式迁移 → Task 2
- .gitignore 更新 → Task 3
- PROJECT.md 更新 → Task 10
- composer.json + composer.lock → Task 1

---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] Code Review task (12.1) 展开了详细的 review checklist，违反 gk-tasks 规则（"不应在 task 描述中展开 review checklist 或 fix policy"）。已简化为委托 code-reviewer sub-agent 执行，并补充 R3.3、R3.5、R3.7、R4.3、R4.4 的引用以确保 no-op requirements 有 task 覆盖。

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、design 模块名）
- [x] checkbox 语法正确
- [x] 无 markdown 格式错误
- [x] `## Tasks` section 存在
- [x] 倒数第一个 top-level task 是 Code Review
- [x] 倒数第二个 top-level task 是手工测试
- [x] 自动化实现 task 排在手工测试和 Code Review 之前
- [x] 所有 task 使用 `- [ ]` checkbox 语法
- [x] top-level task 有序号，sub-task 有层级序号，序号连续无跳号
- [x] 每个实现类 sub-task 引用了 requirements 条款
- [x] requirements.md 中每条 requirement 至少被一个 task 引用
- [x] 引用的 requirement 编号在 requirements.md 中存在
- [x] top-level task 按依赖关系排序，无循环依赖
- [x] 每个 top-level task 的最后一个 sub-task 是 checkpoint
- [x] checkpoint 包含具体验证方式
- [x] Test-first 不适用（配置迁移任务，无新业务逻辑）
- [x] 每个 sub-task 足够具体，可独立执行
- [x] 所有 task 均为 mandatory
- [x] 手工测试覆盖关键用户场景
- [x] Code Review 委托 code-reviewer sub-agent，未展开 checklist
- [x] Socratic Review 覆盖充分
- [x] Design CR 决策全部体现（Q1→Task 7, Q2→Tasks 4/5/6, Q3→严格依赖链顺序, Q4→Task 3）
- [x] Design 全覆盖（所有变更点均有对应 task）
- [x] 验收闭环完整（checkpoint + 手工测试 + code review）
- [x] 执行路径无歧义
