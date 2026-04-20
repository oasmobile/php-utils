# Design Document: Upgrade to PHP 8

## Overview

本设计文档描述将 `oasis/utils` 项目从 PHP 7.4 + PHPUnit 5.x 升级到 PHP >=8.2 + PHPUnit ^11.0 的技术方案。

升级涉及四个维度：
1. **依赖声明**：更新 `composer.json` 中的 PHP 和 PHPUnit 版本约束
2. **源代码兼容性**：修复 PHP 8.x 下的 deprecation 和 incompatibility
3. **测试代码适配**：将 PHPUnit 5.x API 迁移到 PHPUnit 11.x API
4. **配置与文档**：更新 `phpunit.xml`、`PROJECT.md`、`docs/state/`

核心原则：**最小变更**——仅修复兼容性问题，不做 PHP 8 新语法重写，不改变公共 API 和功能行为。

---

## Architecture

升级不改变项目架构。现有模块结构保持不变：

```
src/
├── Validators/          # 数据验证器体系（不变）
├── Exceptions/          # 自定义异常体系（不变）
├── DataProviderInterface.php / AbstractDataProvider.php / ArrayDataProvider.php
├── StringUtils.php / CommonUtils.php / DataPacker.php
├── CaesarCipher.php / Rc4.php / AnsiColorizer.php
ut/                      # 单元测试（适配 PHPUnit 11 API）
```

变更仅限于：
- 源代码中 PHP 8 不兼容的函数调用和语法
- 测试代码中 PHPUnit API 的迁移
- 配置文件格式更新

---

## Components and Interfaces

### 变更清单

#### 1. composer.json

| 字段 | 当前值 | 目标值 |
|------|--------|--------|
| `require.php` | （无） | `>=8.2` |
| `require-dev.phpunit/phpunit` | `^5.1` | `^11.0` |
| `require.voku/portable-utf8` | `^3.0` | `^3.0`（不变） |

#### 2. phpunit.xml

从 PHPUnit 5.7 schema 迁移到 PHPUnit 11.x 格式：

| 项目 | 当前 | 目标 |
|------|------|------|
| Schema | `http://schema.phpunit.de/5.7/phpunit.xsd` | 移除 `xsi:noNamespaceSchemaLocation`（PHPUnit 11 不再提供外部 XSD） |
| Root attributes | 仅 `bootstrap` | 添加 `cacheDirectory=".phpunit.cache"`（PHPUnit 11 要求） |
| Test suite | 17 个 `<file>` 元素 | 保持不变 |
| Bootstrap | `ut/bootstrap.php` | 保持不变 |

根据 CR Q4 决策（逐项评估，仅添加影响测试行为的配置）：
- `cacheDirectory`：PHPUnit 11 必需，不设置会产生警告
- 不添加 `executionOrder`、`beStrictAboutTestsThatDoNotTestAnything` 等可选配置

#### 3. 测试代码适配（ut/*.php）

**3.1 基类变更**

所有 17 个测试文件需要：
- 将 `extends PHPUnit_Framework_TestCase` 改为 `extends \PHPUnit\Framework\TestCase`
- 添加 `use PHPUnit\Framework\TestCase;` 导入

受影响文件：全部 17 个测试文件。

**3.2 方法签名变更**

PHPUnit 11 要求 `setUp()` / `tearDown()` 必须声明 `void` 返回类型：

受影响文件：
- `ut/DataPackerTest.php`：`setUp()` 和 `tearDown()` 需添加 `: void`
- `ut/MlibDataProviderTest.php`：`setUp()` 需添加 `: void`

**3.3 Data Provider 方法**

根据 CR Q2 决策，保持现有 `@dataProvider` annotation 不变。

PHPUnit 11 要求 data provider 方法必须为 `public static`。当前所有 data provider 方法为 `public`（非 static），需全部添加 `static` 关键字。

受影响文件及方法：
- `ut/ArrayValidatorTest.php`：`getInvalidInputInAllowNull`, `getValidInputInAllowNull`, `getInvalidInputInNotAllowNull`, `getValidInputInNotAllowNull`, `getValidInputForSpecificValidator`, `getInvalidInputForSpecificValidator`
- `ut/BooleanValidatorTest.php`：`getInvalidInputInStrictMode`, `getValidInputInStrictMode`, `getInvalidInputInNonStrictMode`, `getValidInputInNonStrictMode`
- `ut/ChainedValidatorTest.php`：`provideChainedTestData`, `provideInvalidChainedTestData`
- `ut/EmailValidatorTest.php`：`getValidEmails`, `getInvalidEmails`
- `ut/EnumerationValidatorTest.php`：`getValidEnumerations`, `getInvalidEnumerations`
- `ut/FloatValidatorTest.php`：`getInvalidInputInStrictMode`, `getValidInputInStrictMode`, `getInvalidInputInNonStrictMode`, `getValidInputInNonStrictMode`
- `ut/IntegerValidatorTest.php`：`getInvalidInputInStrictMode`, `getValidInputInStrictMode`, `getInvalidInputInNonStrictMode`, `getValidInputInNonStrictMode`
- `ut/MlibDataProviderTest.php`：`getValidatorsForNullTest`
- `ut/ObjectValidatorTest.php`：`getInvalidInputInAllowNull`, `getValidInputInAllowNull`, `getInvalidInputInNotAllowNull`, `getValidInputInNotAllowNull`
- `ut/RegexValidatorTest.php`：`getValidStrings`, `getInvalidStrings`
- `ut/StringLengthValidatorTest.php`：`getValidStrings`, `getInvalidStrings`
- `ut/StringValidatorTest.php`：`getValidDataForNonStrictMode`, `getInvalidDataForNonStrictMode`, `getValidDataForStrictMode`, `getInvalidDataForStrictMode`, `getValidDataForStrictModeWithEmptyNotAllowed`, `getInvalidDataForStrictModeWithEmptyNotAllowed`
- `ut/TrimmedStringValidatorTest.php`：`getValidStrings`, `getInvalidStrings`
- `ut/UrlValidatorTest.php`：`getValidUrls`, `getInvalidUrls`

**3.4 异常测试方式**

当前代码已使用 `$this->expectException()`，这是 PHPUnit 11 支持的方式，无需变更。

**3.5 MlibDataProviderTest 中的空 @dataProvider**

`ut/MlibDataProviderTest.php` 中 `testNull()` 方法有一个空的 `@dataProvider` annotation（无方法名引用）。PHPUnit 11 会对此报错。需移除该空 annotation。

**3.6 CaesarCipherTest.php（不在 test suite 中）**

`ut/CaesarCipherTest.php` 存在于 `ut/` 目录但未包含在 `phpunit.xml` 的 test suite 定义中。该文件同样使用 `PHPUnit_Framework_TestCase`，但根据 R2 AC2（保留现有 test suite 定义）和 R5 AC4（保持现有测试覆盖范围不变），本次升级不将其纳入 suite。但为避免直接运行该文件时报错，仍应对其进行基类和 API 适配（与其他 17 个文件相同的变更）。

#### 4. 源代码 PHP 8 兼容性修复（src/）

经逐文件分析，以 PHP 8.5 为基准（CR Q1 决策），识别以下兼容性问题：

**4.1 `src/Validators/StringValidator.php` — `method_exists` 参数错误**

```php
method_exists($target, '__toString()')
```

`method_exists` 的第二个参数应为方法名（不含括号）。当前写法在 PHP 7.x 下碰巧不会报错（因为方法名不匹配，返回 false），但在 PHP 8.x 下行为一致（仍返回 false）。虽然不会产生 deprecation，但这是一个 bug——应修复为 `'__toString'`。

同样的问题存在于 `src/Validators/TrimmedStringValidator.php`。

**4.2 `src/CommonUtils.php` — 隐式 nullable 类型（PHP 8.4 deprecated）**

`monitorMemoryUsage()` 方法中：
```php
$currentLimit = ini_get('memory_limit');
$last = strtolower($currentLimit[strlen($currentLimit) - 1]);
```

`ini_get()` 在 PHP 8.x 下可能返回 `false`（当 option 不存在时）。虽然 `memory_limit` 始终存在，但从防御性编程角度无需修改——该函数在 PHP 8.x 下不会产生 deprecation。

**4.3 `src/DataPacker.php` — 隐式 nullable 构造函数参数**

```php
function __construct($serializer = null, $unserializer = null)
```

PHP 8.4 deprecated 隐式 nullable（当默认值为 null 但类型声明不含 `?` 时）。但此处无类型声明，所以不受影响。无需修改。

**4.4 总结：源代码实际需要修复的问题**

| 文件 | 问题 | 修复方式 |
|------|------|----------|
| `src/Validators/StringValidator.php` | `method_exists($target, '__toString()')` 括号多余 | 改为 `method_exists($target, '__toString')` |
| `src/Validators/TrimmedStringValidator.php` | 同上 | 同上 |

其余源代码在 PHP 8.5 下无 deprecation 或 incompatibility 问题。项目代码风格较为保守，未使用 PHP 8 中已废弃的函数（如 `utf8_encode`、`utf8_decode`、`strftime` 等）。

#### 5. 文档更新

**5.1 PROJECT.md**

| 项目 | 当前 | 目标 |
|------|------|------|
| PHP 版本 | `≥ 5.6（推断自 PHPUnit 5.x）` | `>=8.2` |
| 测试框架 | `PHPUnit 5.x` | `PHPUnit 11.x` |
| 测试命令 | `php74 vendor/bin/phpunit` | `php vendor/bin/phpunit` |
| 注意事项 | 关于 `php74` 别名的说明 | 移除 |

**5.2 docs/state/ 文件**

经检查，`docs/state/` 中的文件（`validators.md`、`utils.md`、`data-provider.md`、`exceptions.md`、`crypto.md`）不包含 PHP 版本或 PHPUnit 版本的直接引用。这些文件仅描述模块接口和行为规则，无需修改。

#### 6. composer.lock 重新生成

在 PHP >=8.2 环境下执行 `composer update` 重新生成 `composer.lock`，确保依赖解析结果反映新的版本约束。

#### 7. .gitignore 更新

添加 `.phpunit.cache/` 到 `.gitignore`，避免 PHPUnit 11 的缓存目录被提交到版本控制。

---

## Data Models

本次升级不涉及数据模型变更。所有类的属性、方法签名和行为保持不变。

---

## Error Handling

本次升级不改变错误处理策略。现有异常体系（`DataValidationException` 及其子类）保持不变。

需要注意的 PHP 8 错误处理变化：
- PHP 8 中 `TypeError` 在更多场景下抛出（如传入 `null` 给非 nullable 参数），但本项目源代码中的函数参数均无显式类型声明，不受影响
- `DataValidationException::create()` 和 `__construct()` 中的 `\Exception $previous` 参数在 PHP 8 下仍然兼容（`\Exception` 实现了 `\Throwable`）

---

## Impact Analysis

### 受影响的 State 文档

| 文件 | 影响 |
|------|------|
| `docs/state/validators.md` | 无需修改（不含版本信息，接口行为不变） |
| `docs/state/utils.md` | 无需修改（不含版本信息，接口行为不变） |
| `docs/state/data-provider.md` | 无需修改（不含版本信息，接口行为不变） |
| `docs/state/exceptions.md` | 无需修改（不含版本信息，接口行为不变） |
| `docs/state/crypto.md` | 无需修改（不含版本信息，接口行为不变） |

### 现有模块行为变化

- **无行为变化**。所有模块的公共 API、输入输出关系保持不变。
- `StringValidator` / `TrimmedStringValidator` 中 `method_exists` 参数修正后，对 `__toString` 对象的处理从"始终不走该分支"变为"正确检测并走该分支"。这是 bug 修复，使行为与文档描述一致。

### 数据模型变更

- 不涉及。无数据模型变更，无旧数据兼容问题。

### 外部系统交互

- 不涉及。本项目为独立 PHP 库，无外部系统依赖。

### 配置项变更

| 配置文件 | 变更 |
|----------|------|
| `composer.json` | 新增 `require.php: >=8.2`，`require-dev.phpunit/phpunit` 从 `^5.1` 改为 `^11.0` |
| `phpunit.xml` | Schema 移除，新增 `cacheDirectory` 属性 |
| `.gitignore` | 建议添加 `.phpunit.cache/` 目录（PHPUnit 11 缓存产物） |

---

## Testing Strategy

### 测试方法

本次升级的正确性验证完全依赖**现有测试套件**。升级的核心验证标准是：

> 所有 17 个测试文件在 PHP >=8.2 + PHPUnit 11.x 下全部通过，且无 deprecation 警告。

### 为什么不使用 Property-Based Testing

本次升级是一个**配置迁移和兼容性修复**任务，不引入新的业务逻辑或纯函数。PBT 不适用于以下原因：
- 变更主要是配置文件格式更新（composer.json、phpunit.xml）
- 源代码修复仅涉及 2 处 `method_exists` 参数修正
- 测试代码变更是 API 迁移（类名、方法签名），不改变测试逻辑
- 正确性由现有测试套件的通过来保证——这些测试本身就是对功能行为的验证

### 验证步骤

1. **依赖安装验证**：`composer install` 在 PHP >=8.2 下成功完成
2. **全量测试验证**：`php vendor/bin/phpunit` 报告所有测试通过
3. **无警告验证**：测试输出中无 PHP deprecation 和 PHPUnit deprecation 警告
4. **测试数量验证**：测试用例总数与升级前一致（不删除、不跳过）

### 测试执行命令

```bash
# 升级后的标准测试命令
php vendor/bin/phpunit

# 单文件测试
php vendor/bin/phpunit ut/SomeTest.php
```

---

## Socratic Review

### Q1: 源代码兼容性分析是否充分？

**A:** 是。逐文件分析了全部 16 个源代码文件（含 Validators/、Exceptions/ 子目录），以 PHP 8.5 为基准排查。识别出 2 处 `method_exists` 参数问题（`StringValidator.php` 和 `TrimmedStringValidator.php`）。其余代码未使用 PHP 8 中废弃或移除的函数，类型使用保守（无显式类型声明），不触发 PHP 8 的严格类型检查。

### Q2: PHPUnit 5→11 的 API 变更是否完整覆盖？

**A:** 是。覆盖了以下变更点：
- 基类名：`PHPUnit_Framework_TestCase` → `PHPUnit\Framework\TestCase`（全部 17 文件 + 1 个 suite 外文件 `CaesarCipherTest.php`）
- 方法签名：`setUp()`/`tearDown()` 添加 `: void`（2 文件）
- Data Provider：添加 `static` 关键字（14 文件）
- 空 `@dataProvider` annotation 修复（1 处）
- 异常测试：已使用 `expectException()`，无需变更
- Assertion 方法：当前使用的 `assertEquals`、`assertTrue`、`assertFalse`、`assertInstanceOf`、`assertGreaterThan`、`assertNotEquals`、`expectException` 在 PHPUnit 11 中均保留

### Q3: phpunit.xml 迁移方案是否正确？

**A:** 是。当前 phpunit.xml 非常简洁（仅 testsuite + bootstrap），迁移工作量小：
- 移除过时的 XSD schema 引用
- 添加 `cacheDirectory` 属性（PHPUnit 11 必需）
- 保留 testsuite 和 bootstrap 配置不变
- 根据 CR Q4 决策，不添加非必要的额外配置

### Q4: voku/portable-utf8 ^3.0 在 PHP 8.5 下是否确认兼容？

**A:** 是。根据 goal.md 中的调研结论：该库仍在活跃维护，CI 已在 PHP 8.5 上运行，README 声明支持 PHP 7.1+/8.0+。保持 `^3.0` 约束即可。

### Q5: 是否存在遗漏的风险点？

**A:** 主要风险点：
1. `voku/portable-utf8` ^3.0 的某些内部方法可能在 PHP 8.5 下产生 deprecation——但这属于上游库的问题，不在本次升级范围内
2. `DataPacker` 默认使用 `igbinary_serialize`，需确保 igbinary 扩展在 PHP 8.x 下可用——若不可用，代码已有 fallback 到 `serialize`
3. `CommonUtils::registerMemoryMonitorForTick()` 使用 `register_tick_function`，该函数在 PHP 8.x 下仍然可用

### Q6: 设计是否完整覆盖了 requirements 中的全部 8 个 requirement？

**A:** 是。
- R1（Composer 依赖）→ Components §1
- R2（PHPUnit 配置）→ Components §2
- R3（测试代码适配）→ Components §3
- R4（源代码兼容性）→ Components §4
- R5（全量测试通过）→ Testing Strategy
- R6（构建命令更新）→ Components §5.1
- R7（状态文档同步）→ Components §5.2 + Impact Analysis
- R8（composer.lock 重新生成）→ Components §6

---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 缺少 `## Impact Analysis` section，已补充完整的影响分析（受影响 state 文档、模块行为变化、数据模型、外部系统、配置项变更）
- [内容] 未提及 `ut/CaesarCipherTest.php`（存在于 ut/ 但不在 phpunit.xml suite 中），已在 §3.6 补充说明该文件也需适配基类和 API
- [内容] 未提及 `.gitignore` 需添加 `.phpunit.cache/`（PHPUnit 11 缓存目录），已在 §7 和 Impact Analysis 配置项变更中补充
- [内容] `StringValidator`/`TrimmedStringValidator` 的 `method_exists` 修复实际上是行为变化（从不走分支变为正确走分支），已在 Impact Analysis 中明确说明这是 bug 修复

### 合规检查
- [x] 无 TBD / TODO / 待定 / 占位符
- [x] 无空 section 或不完整的列表
- [x] 内部引用一致（requirements 编号、术语引用）
- [x] 代码块语法正确（语言标注、闭合）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且正确
- [x] 技术方案主体存在，承接了 requirements 中的需求
- [x] 接口签名 / 数据模型有明确定义
- [x] 各 section 之间使用 `---` 分隔
- [x] 每条 requirement 在 design 中都有对应的实现描述
- [x] 无遗漏的 requirement
- [x] design 中的方案不超出 requirements 的范围
- [x] Impact Analysis 覆盖全部必要维度
- [x] 技术选型有明确理由
- [x] 接口签名足够清晰，能让 task 独立执行
- [x] 无过度设计
- [x] 与 state 文档中描述的现有架构一致
- [x] Socratic Review 覆盖充分
- [x] Requirements CR 决策全部体现在 design 中（Q1→§4 PHP 8.5 基准、Q2→§3.3 保持 annotation、Q3→§4 分析结论、Q4→§2 逐项评估）
- [x] 技术选型明确，无含糊选型
- [x] 可 task 化——模块间关系清晰，执行顺序明确

### Clarification Round

**状态**: 已回答

**Q1:** Design 中 §3.6 提到 `ut/CaesarCipherTest.php` 不在 phpunit.xml suite 中但仍需适配。Tasks 阶段应如何处理该文件？
- A) 与其他 17 个文件一起适配（同一个 task），不加入 suite
- B) 单独作为一个 task 处理，同时将其加入 phpunit.xml suite
- C) 仅适配基类和 API，不加入 suite，作为其他测试适配 task 的附带工作
- D) 其他（请说明）

**A:** B — 单独作为一个 task 处理，同时将其加入 phpunit.xml suite

**Q2:** Tasks 拆分粒度偏好——测试代码适配涉及 18 个文件的重复性变更（基类替换、static 添加等），如何拆分为 task？
- A) 按变更类型拆分：一个 task 做基类替换（全部文件），一个 task 做 data provider static 化（全部文件），一个 task 做 setUp/tearDown 签名修复
- B) 按文件分组拆分：将 18 个文件按功能模块分为 3-4 组，每组一个 task 完成所有适配
- C) 合并为一个 task：所有测试文件的 PHPUnit API 适配作为单个 task 一次性完成
- D) 其他（请说明）

**A:** A — 按变更类型拆分

**Q3:** 实现顺序偏好——design 中存在自然依赖链（R1→R8→R2→R3→R4→R5），tasks 是否严格按此顺序执行？
- A) 严格按依赖链顺序：先 composer.json → composer.lock → phpunit.xml → 测试代码 → 源代码 → 验证 → 文档
- B) 将无依赖的工作并行化：文档更新（R6、R7）可与代码变更并行，源代码修复（R4）可与测试适配（R3）并行
- C) 以最快验证为目标：先做最小可运行变更（composer + phpunit.xml + 一个测试文件），验证通过后再批量适配其余文件
- D) 其他（请说明）

**A:** A — 严格按依赖链顺序

**Q4:** `.gitignore` 中添加 `.phpunit.cache/` 是否需要作为独立 task，还是附带在 phpunit.xml 迁移 task 中完成？
- A) 附带在 phpunit.xml 迁移 task 中一并完成
- B) 与文档更新（PROJECT.md）合并为一个"项目配置更新" task
- C) 作为独立的小 task
- D) 其他（请说明）

**A:** C — 作为独立的小 task
