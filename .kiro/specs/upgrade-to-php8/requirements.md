# Requirements Document

本文件定义 `oasis/utils` 项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP 8 (>=8.2) + PHPUnit 11.x 的需求。

---

## Introduction

项目 `oasis/utils` 当前运行在 PHP 7.4 上，使用 PHPUnit 5.x 作为测试框架，依赖 `voku/portable-utf8` ^3.0。PHP 7.4 已于 2022-11 EOL，不再接收安全修复。本次升级的目标是将 PHP 最低版本提升至 >=8.2，PHPUnit 升级至 ^11.0，修复所有兼容性问题，确保全量测试通过，同时不改变项目功能和公共 API。

**不涉及的内容**：不对现有代码做大规模 PHP 8 新语法重写（named arguments、match expression 等）；不改变项目功能或公共 API；不保留对 PHP 7.x 的向后兼容；不升级 `voku/portable-utf8` 大版本。

---

## Glossary

- **Composer_Config**: `composer.json` 文件，定义项目依赖和约束
- **Source_Code**: `src/` 目录下的 PHP 源代码文件
- **Test_Code**: `ut/` 目录下的 PHPUnit 测试文件
- **PHPUnit_Config**: `phpunit.xml` 文件，PHPUnit 测试框架配置
- **Project_Doc**: `PROJECT.md` 文件，项目级元信息文档
- **State_Doc**: `docs/state/` 目录下的系统状态文档
- **Build_System**: Composer 包管理器及 PHPUnit 测试执行环境
- **Deprecation**: PHP 8 中标记为废弃的语法或函数调用
- **Incompatibility**: PHP 8 中行为变更导致的不兼容问题（如类型更严格、函数签名变更等）
- **TestCase_Base_Class**: PHPUnit 测试用例基类，PHPUnit 5.x 为 `PHPUnit_Framework_TestCase`，PHPUnit 11.x 为 `PHPUnit\Framework\TestCase`

---

## Requirements

### Requirement 1: Composer 依赖版本升级

**User Story:** 作为开发者，我希望项目的 Composer 依赖声明反映 PHP 8 和 PHPUnit 11 的版本要求，以便在新环境下正确安装和运行。

#### Acceptance Criteria

1. THE Composer_Config SHALL 在 `require` 段中声明 PHP 版本约束为 `>=8.2`
2. THE Composer_Config SHALL 在 `require-dev` 段中声明 `phpunit/phpunit` 版本约束为 `^11.0`
3. THE Composer_Config SHALL 保持 `voku/portable-utf8` 版本约束为 `^3.0`
4. THE Composer_Config SHALL 不包含对 PHP 7.x 的兼容性约束
5. WHEN `composer install` 在 PHP >=8.2 环境下执行时，THE Build_System SHALL 成功安装所有依赖且无错误

---

### Requirement 2: PHPUnit 配置升级

**User Story:** 作为开发者，我希望 PHPUnit 配置文件兼容 PHPUnit 11.x 格式，以便测试框架能正确加载和执行测试。

#### Acceptance Criteria

1. THE PHPUnit_Config SHALL 使用与 PHPUnit 11.x 兼容的 XML schema 声明
2. THE PHPUnit_Config SHALL 保留现有的 test suite 定义（包含 `ut/` 目录下的全部 17 个测试文件）
3. THE PHPUnit_Config SHALL 保留 `ut/bootstrap.php` 作为 bootstrap 文件
4. WHEN PHPUnit 11.x 执行时，THE PHPUnit_Config SHALL 被正确解析且无 deprecation 警告

---

### Requirement 3: 测试代码适配 PHPUnit 11.x API

**User Story:** 作为开发者，我希望所有测试代码完整适配 PHPUnit 11.x API，以便测试能在新框架下正确执行。

#### Acceptance Criteria

1. THE Test_Code SHALL 使用 `PHPUnit\Framework\TestCase` 替代 `PHPUnit_Framework_TestCase` 作为 TestCase_Base_Class
2. THE Test_Code SHALL 为 `setUp()` 和 `tearDown()` 方法添加 `void` 返回类型声明
3. THE Test_Code SHALL 使用 PHPUnit 11.x 支持的 assertion 方法（替换已移除的 assertion）
4. THE Test_Code SHALL 使用 PHPUnit 11.x 支持的 `@dataProvider` annotation 格式或 attribute 语法
5. THE Test_Code SHALL 使用 PHPUnit 11.x 支持的异常测试方式（`expectException()` 等方法）
6. THE Test_Code SHALL 不包含任何 PHPUnit 5.x 专属的已废弃 API 调用
7. THE Test_Code SHALL 不包含适配遗留的 `@todo` 标记

---

### Requirement 4: 源代码 PHP 8 兼容性修复

**User Story:** 作为开发者，我希望源代码在 PHP 8.x 下无 deprecation 警告和 incompatibility 错误，以便项目在新运行时环境下稳定运行。

#### Acceptance Criteria

1. THE Source_Code SHALL 在 PHP >=8.2 下执行时不产生 Deprecation 警告
2. THE Source_Code SHALL 在 PHP >=8.2 下执行时不产生 Incompatibility 错误
3. THE Source_Code SHALL 保持现有公共 API 不变（方法签名、类名、命名空间不变）
4. THE Source_Code SHALL 保持现有功能行为不变（输入输出关系不变）
5. IF Source_Code 中存在 PHP 8 下行为变更的函数调用，THEN THE Source_Code SHALL 使用 PHP 8 兼容的替代方式

---

### Requirement 5: 全量测试通过

**User Story:** 作为开发者，我希望升级完成后所有现有测试在 PHP 8.x + PHPUnit 11.x 下通过，以确认升级未引入回归。

#### Acceptance Criteria

1. WHEN `php vendor/bin/phpunit` 在 PHP >=8.2 环境下执行时，THE Build_System SHALL 报告全部 17 个测试文件通过
2. WHEN 测试执行时，THE Build_System SHALL 不产生 PHP deprecation 警告
3. WHEN 测试执行时，THE Build_System SHALL 不产生 PHPUnit deprecation 警告
4. THE Test_Code SHALL 保持现有测试覆盖范围不变（不删除、不跳过现有测试用例）

---

### Requirement 6: 构建命令更新

**User Story:** 作为开发者，我希望构建命令使用系统默认 `php` 而非 `php74` 别名，以便与新的 PHP 8 环境一致。

#### Acceptance Criteria

1. THE Project_Doc SHALL 将测试执行命令从 `php74 vendor/bin/phpunit` 更新为 `php vendor/bin/phpunit`
2. THE Project_Doc SHALL 移除关于使用 `php74` 别名的注意事项说明
3. THE Project_Doc SHALL 更新 PHP 版本信息为 `>=8.2`
4. THE Project_Doc SHALL 更新测试框架信息为 `PHPUnit 11.x`

---

### Requirement 7: 状态文档同步更新

**User Story:** 作为开发者，我希望 `docs/state/` 中的文档反映升级后的版本信息，以保持 SSOT 的准确性。

#### Acceptance Criteria

1. IF State_Doc 中包含 PHP 版本或 PHPUnit 版本的引用，THEN THE State_Doc SHALL 更新为升级后的版本信息
2. THE State_Doc SHALL 保持现有模块接口和行为描述不变（除版本信息外）

---

### Requirement 8: Composer Lock 文件重新生成

**User Story:** 作为开发者，我希望 `composer.lock` 与更新后的 `composer.json` 保持一致，以确保依赖版本锁定正确。

#### Acceptance Criteria

1. WHEN Composer_Config 更新完成后，THE Build_System SHALL 重新生成 `composer.lock` 文件
2. THE `composer.lock` SHALL 反映 PHP >=8.2 和 PHPUnit ^11.0 的依赖解析结果
3. WHEN `composer install` 基于新的 `composer.lock` 执行时，THE Build_System SHALL 成功安装所有依赖

---

## Socratic Review

### Q1: 需求是否完整覆盖了 goal.md 中的所有目标？

**A:** 是。goal.md 列出 9 个目标项，本文档 8 个 Requirement 逐一覆盖：
- Composer 版本约束 → R1
- PHPUnit 升级 → R1 + R2
- voku/portable-utf8 保持不变 → R1.3
- 源代码兼容性修复 → R4
- 测试代码适配 → R3
- PROJECT.md 更新 → R6
- docs/state/ 更新 → R7
- composer.lock 重新生成 → R8
- 全量测试通过 → R5

### Q2: Clarification 记录中的决策是否全部体现？

**A:** 是。
- Q1 PHP `>=8.2` → R1.1
- Q2 voku/portable-utf8 保持 `^3.0` → R1.3
- Q3 PHPUnit 一步到位 `^11.0` → R1.2，R3 整体
- Q4 构建命令改用系统默认 `php` → R6.1, R6.2

### Q3: Non-Goals 是否被正确排除？

**A:** 是。
- 不做大规模 PHP 8 语法重写 → R4.3, R4.4 明确保持现有 API 和行为不变
- 不改变功能或公共 API → R4.3
- 不兼容 PHP 7.x → R1.4
- 不升级 voku 大版本 → R1.3

### Q4: 测试代码适配的 AC 是否足够具体？

**A:** R3 列出了 PHPUnit 5→11 的主要 API 变更点：基类名变更（R3.1）、方法签名变更（R3.2）、assertion 方法变更（R3.3）、data provider 语法（R3.4）、异常测试方式（R3.5）。这些覆盖了代码中实际使用的 PHPUnit API 模式（从测试文件中观察到 `PHPUnit_Framework_TestCase`、无返回类型的 `setUp()`/`tearDown()`、`@dataProvider` annotation、`expectException()` 等）。

### Q5: 是否存在遗漏的风险点？

**A:** `phpunit.xml` 的 schema 从 5.7 升级到 11.x 格式变化较大，R2 已覆盖。`composer.lock` 重新生成可能引入其他依赖的版本变化，R8 已覆盖。源代码中 `CommonUtils::monitorMemoryUsage()` 使用了字符串下标访问和隐式类型转换，这些在 PHP 8 下可能产生 deprecation，R4 已覆盖。

### Q6: 各 requirement 之间是否存在矛盾或重叠？

**A:** 无矛盾。R1（依赖声明）→ R8（lock 重新生成）→ R2（PHPUnit 配置）→ R3（测试代码适配）→ R4（源代码修复）→ R5（全量验证）形成自然的依赖链。R6 和 R7 是文档同步，与代码变更并行。R5 是对 R1-R4 的集成验证，存在合理的覆盖关系而非重叠。

### Q7: 是否有隐含的前置假设没有显式列出？

**A:** 有以下隐含假设，均已在 goal.md 的 Clarification 记录中确认：
- 运行环境已安装 PHP >=8.2（goal Q4 确认使用系统默认 `php`）
- `voku/portable-utf8` ^3.0 在 PHP 8.x 下无兼容性问题（goal Q2 调研确认）
- `ut/CaesarCipherTest.php` 不在当前 phpunit.xml 的 test suite 中（共 18 个测试文件，suite 配置 17 个），本次升级保持现有 suite 配置不变


---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] Introduction 缺少 Non-scope 声明，已补充"不涉及的内容"段落
- [内容] Socratic Review 缺少对 requirement 间矛盾/重叠和隐含假设的审视，已补充 Q6、Q7

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（术语表术语在 AC 中均有使用）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且正确
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空，格式正确
- [x] Requirements section 存在且包含 8 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] 所有 Glossary 术语在 AC 中被实际使用
- [x] AC 中使用的领域概念在 Glossary 中有定义
- [x] User Story 使用中文行文
- [x] AC 使用 THE/WHEN/IF 语体
- [x] AC Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续无跳号
- [x] 内容边界合理（升级类 spec 中引用具体 API 名称可接受）
- [x] Socratic Review 覆盖充分
- [x] Goal CR 决策已体现在 requirements 中
- [x] 完成标准充分（8 条 requirement 的 AC 覆盖 goal 全部目标）
- [x] 可 design 性充分

### Clarification Round

**状态**: 已回答

**Q1:** R4（源代码兼容性修复）要求"不产生 Deprecation 警告"，但 PHP 8.x 各小版本的 deprecation 列表不同（8.2 vs 8.3 vs 8.4 的 deprecation 有差异）。Design 阶段应以哪个版本的 deprecation 列表为基准进行排查？
- A) 以 PHP 8.2（最低支持版本）的 deprecation 为基准，确保最低版本无警告即可
- B) 以当前开发环境实际安装的 PHP 版本为基准（需确认具体版本）
- C) 以 PHP 8.4/8.5（当前最新稳定版）为基准，前瞻性修复所有已知 deprecation
- D) 其他（请说明）

**A:** C — 以 PHP 8.5 为基准，前瞻性修复所有已知 deprecation

**Q2:** R3（测试代码适配）中 R3.4 提到 `@dataProvider` 可使用 annotation 或 attribute 语法。PHPUnit 11.x 同时支持两种方式，design 阶段应选择哪种？
- A) 保持现有 `@dataProvider` annotation 不变（PHPUnit 11 仍支持）
- B) 全部迁移到 PHP 8 attribute 语法 `#[DataProvider('methodName')]`
- C) 仅在需要修改的测试文件中使用 attribute，其余保持 annotation
- D) 其他（请说明）

**A:** A — 保持现有 `@dataProvider` annotation 不变

**Q3:** R4.5 提到"使用 PHP 8 兼容的替代方式"修复不兼容函数调用。对于 PHP 8 中行为变更但未完全移除的函数（如 `utf8_encode`/`utf8_decode` 在 8.2 deprecated），修复策略应如何选择？
- A) 使用 PHP 官方推荐的替代函数（如 `mb_convert_encoding`），即使引入新的函数依赖
- B) 优先使用项目已有依赖 `voku/portable-utf8` 提供的等价方法
- C) 仅在实际触发 deprecation 的调用点做最小修改，不做预防性替换
- D) 其他（请说明）

**A:** A 或 B — 优先使用 PHP 官方推荐替代函数，若项目已有依赖 `voku/portable-utf8` 提供等价方法则优先使用

**Q4:** R2（PHPUnit 配置升级）要求"无 deprecation 警告"。PHPUnit 11.x 的配置文件格式相比 5.7 有大量变更（如 `<filter>` 改为 `<source>`、属性名变更等）。当前 phpunit.xml 非常简洁（仅 testsuite + bootstrap），是否需要在 design 中考虑添加 PHPUnit 11 推荐的额外配置项（如 `cacheDirectory`、`executionOrder` 等）？
- A) 最小化迁移——仅更新 schema 和必要的格式变更，不添加新配置项
- B) 添加 PHPUnit 11 推荐的默认配置项以获得最佳实践
- C) 根据 PHPUnit 11 迁移指南逐项评估，仅添加影响测试行为的配置
- D) 其他（请说明）

**A:** C — 根据 PHPUnit 11 迁移指南逐项评估，仅添加影响测试行为的配置
