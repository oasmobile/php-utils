# Release 3.0.0 Requirements

本文件定义 `oasis/utils` v3.0.0 release 的需求。v3.0.0 对 `src/` 下所有模块进行 PHP 8.2+ 语法全量改造，覆盖 8 个方向，行为语义不变。

---

## 发布范围

本次 release 不包含独立 feature spec，所有改造工作直接在 release spec 中定义。

| 改造方向 | 涉及模块 | 说明 |
|----------|----------|------|
| Constructor promotion | Validators、DataPacker、CaesarCipher、ArrayDataProvider | 减少属性声明样板代码 |
| `readonly` 属性 | Validators、CaesarCipher、EnumerationValidator | 构造后不可变的配置参数 |
| Union / intersection types | ValidatorInterface、AbstractDataProvider、DataValidationException | 替代 PHPDoc 类型注解 |
| `match` 表达式 | AbstractDataProvider、TrimmedStringValidator、CommonUtils | 替代简单 `switch` |
| Named arguments | — | 提升多参数构造函数可读性（不改变签名，仅在调用处使用） |
| 新字符串函数 | StringUtils、AnsiColorizer | `str_contains` / `str_starts_with` / `str_ends_with` 替代 `strpos` 惯用法 |
| `enum` | TrimmedStringValidator（direction）、DataProviderInterface（类型常量）、AnsiColorizer（颜色名） | 替代类常量 / 魔术字符串 |
| 类型声明补齐 | 全部模块 | 参数类型、返回类型、属性类型 |

---

## 改造概要

v2.0.0 完成了 PHP >=8.2 + PHPUnit ^11.0 的最小化兼容性迁移，但源代码风格仍为 PHP 7.x 时代写法。本次 release 对 `src/` 下全部模块进行 PHP 8.2+ 新语法现代化改造，覆盖 constructor promotion、`readonly`、union/intersection types、`match` 表达式、named arguments、新字符串函数、`enum`、类型声明补齐共 8 个方向。

改造为纯语法层面重构，不新增功能、不变更业务逻辑、不引入新依赖。由于公共 API 签名变更（类型声明补齐、enum 替代常量等）构成 breaking change，以 major version 3.0.0 发布。

---

## Release 工作项

### Requirement 1: Constructor Promotion 改造

**User Story:** 作为 Developer，我希望使用 constructor promotion 简化属性声明，以便减少样板代码并提升可读性。

#### Acceptance Criteria

1. THE Modernizer SHALL 将以下类的构造函数转换为 constructor promotion 形式：`StringValidator`、`TrimmedStringValidator`、`IntegerValidator`、`FloatValidator`、`BooleanValidator`、`ArrayValidator`、`ObjectValidator`、`EnumerationValidator`、`StringLengthValidator`、`RegexValidator`、`ChainedValidator`、`CaesarCipher`、`DataPacker`、`ArrayDataProvider`
2. WHEN 一个构造函数参数在构造函数中直接赋值且后续不再被修改，THE Modernizer SHALL 将该 promoted 属性声明为 `readonly`（延迟初始化的属性不使用 `readonly`）
3. THE Modernizer SHALL 保留每个构造函数参数的原始默认值
4. THE Modernizer SHALL 移除被 promotion 替代的手动属性声明和赋值语句
5. IF 一个构造函数包含非简单赋值的初始化逻辑（如 `DataPacker` 的 callable 回退），THEN THE Modernizer SHALL 仅对可直接 promote 的参数使用 promotion，保留需要额外逻辑的参数为手动赋值

---

### Requirement 2: Union / Intersection Types 与类型声明补齐

**User Story:** 作为 Developer，我希望为所有参数、返回值和属性补齐原生类型声明，以便增强静态分析能力并替代 PHPDoc 类型注解。

#### Acceptance Criteria

1. THE Modernizer SHALL 为 `src/` 下所有类和接口的公开方法、受保护方法的参数添加原生类型声明
2. THE Modernizer SHALL 为 `src/` 下所有类和接口的公开方法、受保护方法添加返回类型声明
3. WHEN 一个参数或返回值可能为多种类型，THE Modernizer SHALL 使用 union type（如 `int|string`）声明
4. WHEN 一个参数或返回值可能为 `null`，THE Modernizer SHALL 使用 nullable type（如 `?string` 或 `Type|null`）声明
5. THE Modernizer SHALL 为 `ValidatorInterface::validate()` 添加参数类型 `mixed` 和返回类型 `mixed`
6. THE Modernizer SHALL 为 `DataProviderInterface` 的所有方法添加参数类型和返回类型声明
7. THE Modernizer SHALL 为 `HierarchicalDataProviderInterface` 的所有方法添加参数类型和返回类型声明
8. THE Modernizer SHALL 为 `DataValidationException` 的所有方法添加参数类型和返回类型声明，包括 `$previous` 参数使用 `?\Throwable` 类型
9. WHEN 类型声明与现有 PHPDoc 注解完全等价，THE Modernizer SHALL 移除冗余的 PHPDoc `@param` 和 `@return` 标签

---

### Requirement 3: Enum 引入

**User Story:** 作为 Developer，我希望使用 PHP 8.1+ enum 替代类常量和魔术字符串，以便获得类型安全和 IDE 自动补全支持。

#### Acceptance Criteria

1. THE Modernizer SHALL 创建 `TrimDirection` enum，包含 `Both`、`Left`、`Right` 三个 case，替代 `TrimmedStringValidator` 中的 `TRIM_BOTH`、`TRIM_LEFT`、`TRIM_RIGHT` 常量
2. THE Modernizer SHALL 创建 `DataType` enum，包含与 `DataProviderInterface` 中 10 个类型常量（`INT_TYPE` 至 `MIXED_TYPE`）对应的 case，每个 case 的 backed value 为原常量的字符串值
3. THE Modernizer SHALL 创建 `AnsiColor` enum，包含 `Black`、`Red`、`Green`、`Yellow`、`Blue`、`Magenta`、`Cyan`、`White` 及其对应的 `LightBlack`、`LightRed`、`LightGreen`、`LightYellow`、`LightBlue`、`LightMagenta`、`LightCyan`、`LightWhite` 共 16 个 case，替代 `AnsiColorizer` 中的颜色名字符串查找
4. WHEN `TrimDirection` enum 创建后，THE Modernizer SHALL 更新 `TrimmedStringValidator` 构造函数的 `$direction` 参数类型为 `TrimDirection`，并更新 `validate()` 中的分支逻辑使用 enum case
5. WHEN `DataType` enum 创建后，THE Modernizer SHALL 更新 `DataProviderInterface` 移除旧类型常量，方法签名中 `$validator` 参数类型改为 `ValidatorInterface|DataType`（同时接受 enum 和 validator 实例，移除字符串路径）
6. WHEN `AnsiColor` enum 创建后，THE Modernizer SHALL 更新 `AnsiColorizer` 的 `foreground()` 和 `background()` 方法参数类型为 `AnsiColor`（含 16 个 case 覆盖基础色和亮色），同时保留数字颜色码（256 色模式）的支持路径（参数类型为 `AnsiColor|int`）
7. THE Modernizer SHALL 将每个 enum 放置在独立的 PHP 文件中，遵循 PSR-4 自动加载规范

---

### Requirement 4: Match 表达式替代 Switch

**User Story:** 作为 Developer，我希望使用 `match` 表达式替代简单的 `switch` 语句，以便获得更简洁的语法和严格比较语义。

#### Acceptance Criteria

1. THE Modernizer SHALL 将 `AbstractDataProvider::getValidatorByLegacyString()` 中的 `switch` 语句替换为 `match` 表达式
2. THE Modernizer SHALL 将 `TrimmedStringValidator::validate()` 中的 `switch` 语句替换为 `match` 表达式
3. THE Modernizer SHALL 将 `CommonUtils::monitorMemoryUsage()` 中解析 memory limit 后缀的 `switch` 语句替换为 `match` 表达式
4. WHEN `switch` 包含 `default` 分支，THE Modernizer SHALL 在 `match` 表达式中保留 `default` 分支
5. THE Modernizer SHALL 确保 `match` 替换后的比较语义与原 `switch` 一致（`match` 使用严格比较 `===`，必要时调整）

---

### Requirement 5: 新字符串函数替代

**User Story:** 作为 Developer，我希望使用 PHP 8.0+ 内置的 `str_contains`、`str_starts_with`、`str_ends_with` 替代 `strpos` 惯用法，以便提升代码可读性和意图表达。

#### Acceptance Criteria

1. THE Modernizer SHALL 将 `StringUtils::stringStartsWith()` 的实现替换为使用 `str_starts_with()`
2. THE Modernizer SHALL 将 `StringUtils::stringEndsWith()` 的实现替换为使用 `str_ends_with()`
3. WHEN `src/` 中其他位置存在 `strpos($haystack, $needle) !== false` 或等价的子串检查惯用法，THE Modernizer SHALL 替换为 `str_contains()`
4. THE Modernizer SHALL 保留 `StringUtils::stringStartsWith()` 和 `StringUtils::stringEndsWith()` 的公开方法签名不变（方法名、参数个数、参数名），仅替换内部实现（类型声明补齐由 R2 覆盖，不受此约束）

---

### Requirement 6: 测试适配

**User Story:** 作为 Developer，我希望现有测试在语法改造后全部通过，以便确认改造未引入行为变更。

#### Acceptance Criteria

1. WHEN 语法改造导致公共 API 签名变更（如参数类型从无类型变为 enum），THE Modernizer SHALL 同步更新 `ut/` 下受影响的测试文件
2. THE Test_Suite SHALL 在改造完成后通过全部测试，零失败、零 error
3. THE Modernizer SHALL NOT 在测试适配中新增测试用例或修改测试断言逻辑，仅调整调用方式以适配新签名
4. IF 测试中使用了被 enum 替代的旧常量值（如字符串 `'both'` 或类常量 `TrimmedStringValidator::TRIM_BOTH`），THEN THE Modernizer SHALL 将其替换为对应的 enum case

---

### Requirement 7: SSOT 更新

**User Story:** 作为 Developer，我希望 `docs/state/` 准确反映改造后的接口和类型信息，以便 SSOT 与代码保持一致。

#### Acceptance Criteria

1. WHEN 语法改造完成，THE Modernizer SHALL 更新 `docs/state/validators.md` 反映 constructor promotion、`readonly`、类型声明和 enum 变更
2. WHEN 语法改造完成，THE Modernizer SHALL 更新 `docs/state/data-provider.md` 反映 `DataType` enum 和类型声明变更
3. WHEN 语法改造完成，THE Modernizer SHALL 更新 `docs/state/crypto.md` 反映 constructor promotion、`readonly` 和类型声明变更
4. WHEN 语法改造完成，THE Modernizer SHALL 更新 `docs/state/utils.md` 反映类型声明和新字符串函数变更
5. WHEN 语法改造完成，THE Modernizer SHALL 更新 `docs/state/exceptions.md` 反映类型声明变更
6. THE SSOT 更新 SHALL 记录新增的 enum 类型（`TrimDirection`、`DataType`、`AnsiColor`）及其 case 定义

---

### Requirement 8: Release 分支全量验证

**User Story:** 作为 Release_Manager，我希望在所有改造完成后执行最终全量验证，以便确认 release 可合并和打 tag。

#### Acceptance Criteria

1. WHEN Release_Manager 执行 release 验证，THE Test_Suite SHALL 通过全部测试，零失败、零 error、零 deprecation 警告
2. WHEN Release_Manager 执行 release 验证，THE Release_Manager SHALL 验证 `docs/state/` 中所有文件已更新反映改造后的接口
3. WHEN Release_Manager 执行 release 验证，THE Release_Manager SHALL 验证 `src/` 下无残留的 PHP 7.x 风格类型常量（已被 enum 替代的）
4. THE Release_Manager SHALL 验证 `composer.json` 未引入新的运行时依赖

---

### Requirement 9: 功能与行为不变性

**User Story:** 作为 Release_Manager，我希望确保语法改造不改变任何业务逻辑和行为语义，以便下游用户仅需适配 API 签名变更。

#### Acceptance Criteria

1. THE Modernizer SHALL NOT 新增任何类或公开方法（enum 类型文件除外）
2. THE Modernizer SHALL NOT 修改任何验证器的验证逻辑或错误抛出条件
3. THE Modernizer SHALL NOT 修改 `CaesarCipher` 或 `Rc4` 的加解密算法逻辑
4. THE Modernizer SHALL NOT 修改 `DataPacker` 的打包/解包格式或序列化行为
5. THE Modernizer SHALL NOT 修改 `ArrayDataProvider` 的路径解析策略或数据查找逻辑
6. THE Modernizer SHALL NOT 在 `composer.json` 中添加或移除任何依赖
7. THE Modernizer SHALL NOT 修改异常继承树结构或异常抛出时机

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
| 8 个改造方向全覆盖 | ⏳ 待执行 | R1–R5 覆盖全部 8 个方向 |
| 全量测试通过 | ⏳ 待执行 | R6、R8 要求零失败 |
| SSOT 更新 | ⏳ 待执行 | R7 要求更新全部 state 文件 |
| 行为不变性 | ⏳ 待执行 | R9 约束不改变业务逻辑 |
| 无新依赖 | ⏳ 待执行 | R9 AC6 约束 |
| 项目级 Issue | ✅ 无阻塞 | 无未解决 issue |

### 结论

所有改造工作在 R1–R5 中定义，测试适配在 R6 中定义，SSOT 更新在 R7 中定义，最终验证在 R8 中定义，不变性约束在 R9 中定义。完成全部 requirement 后可进入最终验证并合并打 tag。

---

## Glossary

- **Modernizer**: 执行 PHP 8.2+ 语法改造的 agent 或开发者
- **Release_Manager**: 执行 release 验证流程的 agent 或开发者
- **Test_Suite**: 由 `phpunit.xml` 定义的全量测试集合（`ut/` 目录下所有测试文件）
- **SSOT**: `docs/state/` 目录下的系统状态文档（Single Source of Truth）
- **Constructor_Promotion**: PHP 8.0+ 特性，在构造函数参数前添加可见性修饰符以自动声明和赋值属性
- **TrimDirection**: 新增 enum，定义 trim 方向（`Both` / `Left` / `Right`）
- **DataType**: 新增 backed enum，定义数据提供者的类型常量（`Int` / `Float` / `String` 等）
- **AnsiColor**: 新增 enum，定义 ANSI 终端颜色名，含基础 8 色和亮色变体共 16 个 case（`Black` / `Red` / ... / `LightBlack` / `LightRed` / ...）

---

## Socratic Review

### Q1: 文档结构是否符合 release spec 标准？

**A:** 是。文档使用 release spec 结构：`# Release 3.0.0 Requirements` → `## 发布范围` → `## 改造概要` → `## Release 工作项`（9 个 Requirement）→ `## 已知 Issue 评估` → `## 发布判定` → `## Glossary`。与 v2.0.0 release requirements 结构一致。

### Q2: 9 个 Requirement 是否完整覆盖 goal.md 中的 8 个改造方向？

**A:** 是。R1 覆盖 constructor promotion 和 `readonly`（两者紧密关联，合并处理）。R2 覆盖 union/intersection types 和类型声明补齐。R3 覆盖 enum。R4 覆盖 `match` 表达式。R5 覆盖新字符串函数。Named arguments 作为调用侧改进，不改变签名，在各 requirement 的实现中自然使用，无需独立 requirement。

### Q3: 各 Requirement 是否都在描述外部可观察行为？是否混入了实现细节？

**A:** 是，均为外部可观察行为。R1–R5 描述代码结构的预期变更结果（可通过代码审查验证），R6 描述测试适配要求，R7 描述文档更新要求，R8 描述最终验证检查项，R9 描述不变性约束。未混入具体实现步骤。

### Q4: Named arguments 为何没有独立 Requirement？

**A:** Named arguments 是调用侧的可读性改进，不改变方法签名。它在各模块改造过程中自然使用（如多参数构造函数调用），不需要独立的验收标准。如果为其创建独立 requirement，AC 将难以定义明确的验收条件（"在适当的地方使用 named arguments" 违反 INCOSE 的可测试性规则）。

### Q5: R3（Enum 引入）的 3 个 enum 是否覆盖了 goal.md 中提到的所有适用场景？

**A:** 是。goal.md 明确提到三个场景：`TrimmedStringValidator` direction（→ `TrimDirection`）、`DataProviderInterface` 类型常量（→ `DataType`）、`AnsiColorizer` 颜色名（→ `AnsiColor`）。这三个是代码中最明确的 enum 适用场景。

### Q6: R9（不变性约束）是否与 goal.md 的 Non-Goals 一致？

**A:** 是。goal.md Non-Goals 列出：不新增功能或新增类（R9 AC1，enum 除外）、不变更业务逻辑或行为语义（R9 AC2–AC5、AC7）、不引入新的外部依赖（R9 AC6）。完全对应。

### Q7: R5 AC4 要求保留 `stringStartsWith` / `stringEndsWith` 方法签名不变，但 R2 要求补齐类型声明，是否矛盾？

**A:** 不矛盾。R5 AC4 的"签名不变"指方法名和参数列表（参数个数、参数名）不变，不包括类型声明的添加。R2 的类型声明补齐是在原有参数上添加类型约束，属于签名增强而非变更。两者可以同时满足。

### Q8: 与 goal.md 的约束与决策是否一致？

**A:** 一致。版本 v3.0.0（major bump）→ 文档标题和概要中体现。全量改造 → R1–R5 覆盖所有模块。enum 有适用场景即引入 → R3 定义了 3 个 enum。行为不变 → R9。测试通过 → R6、R8。无旧数据兼容 → 未包含任何迁移逻辑 requirement。

### Q9: 是否有遗漏的边界条件或错误路径？

**A:** R3 AC6 原文未提及 `AnsiColorizer` 的数字颜色码（256 色模式）路径。实际代码中 `foreground()` / `background()` 同时支持命名颜色和数字颜色码，enum 仅替代命名颜色查找，数字路径应保留。已在 AC6 中补充此约束。其余 requirement 的边界条件覆盖充分。

### Q10: R4（match 替代 switch）中 `match` 的严格比较是否会引入行为变更？

**A:** 需要关注。`match` 使用 `===` 而 `switch` 使用 `==`。对于 `getValidatorByLegacyString()` 和 `TrimmedStringValidator::validate()` 中的 switch，比较对象均为字符串常量/enum case，`===` 和 `==` 行为一致。`CommonUtils::monitorMemoryUsage()` 中的 switch 比较的是 `strtolower()` 返回的字符串，同样不受影响。R4 AC5 已要求确保比较语义一致，覆盖了此风险。


---

## Gatekeep Log

**校验时间**: 2025-07-18
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [语体] R1 AC1 移除自引用条件 `WHEN Constructor_Promotion 改造完成`，改为直接 `THE Modernizer SHALL`（WHEN 子句应描述触发条件，不应描述 AC 自身的完成状态）
- [语体] R2 AC1-AC2 移除自引用条件 `WHEN 类型声明补齐完成`，改为直接 `THE Modernizer SHALL`（同上）
- [内容] R3 AC6 补充数字颜色码（256 色模式）支持路径的保留约束——原 AC 仅提及 enum 替代字符串颜色名查找，未说明数字颜色路径应保留
- [内容] R5 AC4 澄清"签名不变"的具体含义为"方法名、参数个数、参数名"，并明确类型声明补齐由 R2 覆盖不受此约束，消除与 R2 的潜在歧义
- [格式] `## 改造概要` 与 `## Release 工作项` 之间补充 `---` 分隔线
- [内容] Socratic Review 补充 Q9（边界条件遗漏评估）和 Q10（match 严格比较的行为影响分析）

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirement 编号、术语表术语在正文中使用）
- [x] 无 markdown 格式错误
- [x] 一级标题为 `# Release 3.0.0 Requirements`
- [x] `## 发布范围` 存在，列出 8 个改造方向
- [x] `## 改造概要` 存在，概述改造背景和范围
- [x] `## Release 工作项` 存在，包含 9 条 requirement
- [x] `## 已知 Issue 评估` 存在，含项目级 Issue 和 Release 系列 Issue
- [x] `## 发布判定` 存在，含检查项表格和结论
- [x] Glossary 存在且无孤立术语
- [x] 所有 User Story 使用中文行文
- [x] AC 使用 THE/SHALL/WHEN/IF 语体，无自引用条件
- [x] AC Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续无跳号
- [x] AC 未混入实现步骤（具体类名/方法名用于界定改造范围，属于可验证的预期结果）
- [x] Socratic Review 覆盖充分（结构、覆盖度、行为边界、矛盾检查、goal 一致性、边界条件、match 语义）
- [x] goal.md 中 Q1-Q3 决策全部体现
- [x] goal.md Non-Goals 由 R9 不变性约束覆盖

### Clarification Round

**状态**: ✅ 已确认

**Q1:** R3 AC6 要求 `AnsiColorizer` 使用 `AnsiColor` enum 替代字符串颜色名查找。当前代码中 `foreground()` / `background()` 接受字符串参数（如 `'RED'`、`'LIGHT-RED'`、`'128'`），改为 enum 后方法签名将变为 `AnsiColor` 类型。`LIGHT-` 前缀的亮色变体（如 `LIGHT-RED`）如何处理？
- A) 在 `AnsiColor` enum 中为每个颜色增加 `LightXxx` case（如 `LightRed`），共 16 个 case
- B) 保留 `LIGHT-` 前缀作为独立逻辑，`foreground()` / `background()` 参数类型改为 `AnsiColor|string`，enum 仅覆盖基础 8 色，亮色和数字颜色仍通过字符串传入
- C) `foreground()` / `background()` 参数类型改为 `AnsiColor|int`，enum 覆盖基础 8 色 + 亮色变体，数字颜色通过 int 传入
- D) 其他（请说明）

**A:** A——在 `AnsiColor` enum 中为每个颜色增加 `LightXxx` case，共 16 个 case。→ 已更新 R3 AC3 和 AC6。

**Q2:** R3 AC5 要求 `DataProviderInterface` 移除旧类型常量，使用 `DataType` enum。当前 `AbstractDataProvider` 的方法签名中 `$validator` 参数同时接受 `ValidatorInterface` 实例和类型常量字符串（通过 `getValidatorByLegacyString()` 映射）。改为 `DataType` enum 后，`$validator` 参数类型应如何定义？
- A) `ValidatorInterface|DataType`——同时接受 enum 和 validator 实例，移除字符串路径
- B) `ValidatorInterface|DataType|string`——保留字符串兼容路径（但 goal 明确允许 breaking change，且 R9 不要求旧数据兼容）
- C) 仅 `DataType`——不再接受 `ValidatorInterface` 实例，统一通过 enum 映射
- D) 其他（请说明）

**A:** A——`ValidatorInterface|DataType`，同时接受 enum 和 validator 实例，移除字符串路径。→ 已更新 R3 AC5。

**Q3:** R1 AC2 要求构造后不再被修改的 promoted 属性声明为 `readonly`。`CaesarCipher` 的 `$bits`、`$partition`、`$strength` 在构造后不变，但其 lookup table 相关属性（`$lookupTable`、`$reverseLookupTable`）是延迟生成的（首次使用时 shuffle）。`readonly` 的应用范围是否仅限于构造函数中直接赋值且后续不变的属性？
- A) 是，仅对构造函数中直接赋值且后续不变的属性使用 `readonly`，延迟初始化的属性不使用
- B) 对延迟初始化的属性也使用 `readonly`，但需要在构造函数中完成初始化（改变当前的延迟生成策略）
- C) 其他（请说明）

**A:** A——仅对构造函数中直接赋值且后续不变的属性使用 `readonly`，延迟初始化的属性不使用。→ 已更新 R1 AC2。
