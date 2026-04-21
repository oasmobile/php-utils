# Memory Limit Warning Fix — Bugfix Design

## Overview

`CommonUtils::monitorMemoryUsage()` 在将字节数转换为 M/G 单位时，使用 `ceil($x / 1024 * 100) / 100` 保留两位小数，产生如 `317.19M` 的非整数值。PHP 8.x 的 `ini_set('memory_limit', '317.19M')` 会触发 `E_WARNING: Invalid quantity`，覆盖 `error_get_last()` 中的原始错误信息。

修复策略：将 M/G 单位转换改为 `ceil($x / 1024)` 直接取整，确保生成的 Memory_Limit_String 始终为整数；同时增加 Global_Switch（静态方法对 `enableMemoryMonitor()` / `disableMemoryMonitor()`）控制是否执行监控逻辑。

---

## Glossary

- **Bug_Condition (C)**: 触发 bug 的条件——Memory_Monitor 经 K→M 或 M→G Unit_Conversion 后产生非整数值的 Memory_Limit_String
- **Property (P)**: 期望行为——Unit_Conversion 后的 Memory_Limit_String 始终为整数值加单位后缀，`ini_set` 不产生 warning
- **Preservation**: 修复不得改变的现有行为——阈值判断逻辑、K 范围转换、CLI 日志输出、`registerMemoryMonitorForTick()` 注册机制
- **monitorMemoryUsage()**: `CommonUtils` 中的静态方法，负责动态调整 PHP `memory_limit`
- **Memory_Limit_String**: 传递给 `ini_set('memory_limit', ...)` 的值字符串，由整数部分和可选的单位后缀（K/M/G）组成
- **Unit_Conversion**: 将字节数转换为带单位后缀的 Memory_Limit_String 的过程
- **Global_Switch**: 通过 `enableMemoryMonitor()` / `disableMemoryMonitor()` 静态方法控制 Memory_Monitor 是否执行的开关

---

## Bug Details

### Bug Condition

当 `monitorMemoryUsage()` 决定调整 `memory_limit` 后，将字节数逐级除以 1024 转换为 K→M→G 单位。M 和 G 级别的转换使用 `ceil($newLimit / 1024 * 100) / 100`，意图保留两位小数，但 PHP 8.x 的 `ini_set('memory_limit', ...)` 不接受带小数的值字符串。

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type { newLimitBytes: int, resetNeeded: bool }
  OUTPUT: boolean

  IF NOT input.resetNeeded THEN RETURN false

  // 模拟现有 Unit_Conversion 逻辑
  value := input.newLimitBytes
  IF value > 1024 THEN value := ceil(value / 1024)          // → K（整数，无 bug）
  IF value > 1024 THEN value := ceil(value / 1024 * 100) / 100  // → M（可能小数）
  IF value > 1024 THEN value := ceil(value / 1024 * 100) / 100  // → G（可能小数）

  RETURN value != floor(value)   // 值含小数部分即触发 bug
END FUNCTION
```

### Examples

- **M 范围非整数倍**: `newLimitBytes = 324_534_272`（≈309.5 × 1024²）→ K 阶段 `ceil(324534272/1024) = 316928` → M 阶段 `ceil(316928/1024*100)/100 = 309.50` → `ini_set('memory_limit', '309.5M')` → **E_WARNING**
- **M 范围整数倍**: `newLimitBytes = 2_097_152`（= 2 × 1024²）→ K 阶段 `ceil(2097152/1024) = 2048` → M 阶段 `ceil(2048/1024*100)/100 = 2.0` → `ini_set('memory_limit', '2M')` → PHP 将 `2.0` 转为 `"2"` → **无 warning**（但依赖 PHP 浮点转字符串行为，不可靠）
- **G 范围非整数倍**: `newLimitBytes = 1_500_000_000`（≈1.40 × 1024³）→ 经 K、M 转换后 M 值 `1430.51` → G 阶段 `ceil(1430.51/1024*100)/100 = 1.40` → `ini_set('memory_limit', '1.4G')` → **E_WARNING**
- **K 范围（不触发 bug）**: `newLimitBytes = 500_000` → K 阶段 `ceil(500000/1024) = 489` → 489 ≤ 1024，停在 K → `ini_set('memory_limit', '489K')` → **无 warning**

---

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- 内存使用率超过 `$upperThreshold` 时扩大 `memory_limit` 的阈值判断逻辑
- 内存使用率低于 `$lowerThreshold` 且非首次调整时缩小 `memory_limit` 的逻辑（含 `$minUsage` 下限）
- 新限制值 ≤ 1024 字节时使用无单位后缀的纯数字值
- K 范围（1024 < value ≤ 1024²）使用 `K` 后缀且值为整数（现有 `ceil` 逻辑已正确）
- CLI 模式下向 stderr 输出调整日志的格式和时机
- `registerMemoryMonitorForTick()` 的注册机制
- `$isLowest`、`$neverReset` 等内部 static 变量的状态管理逻辑

**Scope:**
所有不涉及 M/G Unit_Conversion 和 Global_Switch 的输入路径应完全不受影响，包括：
- K 范围及以下的 Unit_Conversion
- 阈值判断逻辑（`$upperThreshold`、`$lowerThreshold`）
- CLI 日志输出
- `registerMemoryMonitorForTick()` 调用

---

## Hypothesized Root Cause

基于源码分析，bug 的根因明确：

1. **M/G 转换公式错误**: `ceil($newLimit / 1024 * 100) / 100` 意图保留两位小数，但 PHP 8.x 的 `ini_set('memory_limit', ...)` 只接受整数值字符串。K 转换使用 `ceil($newLimit / 1024)` 是正确的，M/G 转换应采用相同模式。

2. **K 转换与 M/G 转换不一致**: K 阶段使用 `ceil($newLimit / 1024)` 产生整数，M/G 阶段却使用 `ceil($newLimit / 1024 * 100) / 100` 产生浮点数。三个阶段应统一使用 `ceil($newLimit / 1024)`。

3. **缺少全局开关**: `monitorMemoryUsage()` 没有提供运行时禁用机制，调用方无法在不修改调用点或取消 tick function 注册的情况下暂停内存监控。

---

## Correctness Properties

Property 1: Bug Condition — M/G Unit_Conversion 产生合法整数值

_For any_ `newLimitBytes`（正整数）经 Unit_Conversion 后落入 M 或 G 范围时，修复后的转换逻辑 SHALL 生成仅包含整数和单位后缀的 Memory_Limit_String（匹配 `/^\d+[MG]$/`），使 `ini_set('memory_limit', ...)` 不产生 `E_WARNING`。

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Preservation — 非 M/G 范围的 Unit_Conversion 行为不变

_For any_ `newLimitBytes`（正整数）经 Unit_Conversion 后落入 K 范围或无单位范围时，修复后的转换逻辑 SHALL 产生与原始逻辑完全相同的 Memory_Limit_String，保留现有的 K 范围整数转换和无单位纯数字行为。

**Validates: Requirements 3.3, 3.4**

Property 3: Preservation — Global_Switch 禁用时不执行监控

_For any_ 调用，当 Global_Switch 处于禁用状态时，`monitorMemoryUsage()` SHALL 直接返回，不执行内存检测、不调用 `ini_set`、不输出日志，且不改变 `$isLowest`、`$neverReset` 等内部 static 变量。

**Validates: Requirements 2.4, 3.6**

---

## Fix Implementation

### Changes Required

假设根因分析正确：

**File**: `src/CommonUtils.php`

**Function**: `monitorMemoryUsage()`

**Specific Changes**:

1. **添加 Global_Switch 静态属性和方法**:
   - 新增 `private static bool $memoryMonitorEnabled = true` 静态属性
   - 新增 `public static function enableMemoryMonitor(): void` 方法，将 `$memoryMonitorEnabled` 设为 `true`
   - 新增 `public static function disableMemoryMonitor(): void` 方法，将 `$memoryMonitorEnabled` 设为 `false`

2. **在 `monitorMemoryUsage()` 入口添加开关检查**:
   - 在方法体最前面（`static $isLowest` 声明之前）添加：
     ```php
     if (!self::$memoryMonitorEnabled) {
         return;
     }
     ```
   - 开关检查在 static 变量声明之前，确保禁用时不影响内部状态

3. **修复 M 单位转换公式**:
   - 将 `$newLimit = ceil($newLimit / 1024 * 100) / 100;`（M 行）
   - 改为 `$newLimit = ceil($newLimit / 1024);`

4. **修复 G 单位转换公式**:
   - 将 `$newLimit = ceil($newLimit / 1024 * 100) / 100;`（G 行）
   - 改为 `$newLimit = ceil($newLimit / 1024);`

5. **不修改的部分**:
   - K 转换公式 `ceil($newLimit / 1024)` 保持不变
   - 阈值判断逻辑保持不变
   - CLI 日志输出格式保持不变
   - `registerMemoryMonitorForTick()` 保持不变

---

## Impact Analysis

### 受影响的 State 文档

- `docs/state/utils.md` — CommonUtils section：需新增 `enableMemoryMonitor()` / `disableMemoryMonitor()` 方法描述，补充 Global_Switch 行为说明

### 现有行为变化

- `monitorMemoryUsage()` 的 M/G Unit_Conversion 输出从浮点数变为整数（向上取整），实际分配的内存可能比修复前略多（最多多 1M 或 1G 单位内的差值）
- 新增 `enableMemoryMonitor()` / `disableMemoryMonitor()` 公开静态方法，扩展了 `CommonUtils` 的公开 API

### 数据模型变更

不涉及。本次修复仅改变运行时行为，不涉及持久化数据。

### 外部系统交互

不涉及。`ini_set('memory_limit', ...)` 是 PHP 进程内操作，不涉及外部系统。

### 配置项变更

不涉及。不新增、删除或修改任何配置文件或配置项。Global_Switch 通过代码 API 控制，非配置文件。

---

## Testing Strategy

### Validation Approach

测试策略分两阶段：先在未修复代码上运行探索性测试以确认 bug 存在并验证根因，再在修复后验证 fix 正确性和行为保留。

### Exploratory Bug Condition Checking

**Goal**: 在实施修复前，用反例证明 bug 存在，确认或否定根因分析。若否定，需重新假设。

**Test Plan**: 编写测试用例，构造使 M/G 转换产生非整数值的输入，在未修复代码上运行，观察 `ini_set` 是否触发 `E_WARNING`。

**Test Cases**:
1. **M 范围非整数倍**: 构造 `newLimitBytes` 使 K 阶段结果不是 1024 的整数倍，验证 M 转换产生小数值（will fail on unfixed code）
2. **G 范围非整数倍**: 构造 `newLimitBytes` 使 M 阶段结果不是 1024 的整数倍，验证 G 转换产生小数值（will fail on unfixed code）
3. **error_get_last() 覆盖**: 在 `error_get_last()` 有值的情况下触发 M/G 转换，验证 warning 覆盖了原始错误（will fail on unfixed code）

**Expected Counterexamples**:
- `ceil(316928 / 1024 * 100) / 100` = `309.50`，`ini_set('memory_limit', '309.5M')` 触发 `E_WARNING`
- 根因：M/G 转换公式 `ceil($x / 1024 * 100) / 100` 保留小数，而 PHP 8.x 不接受小数值

### Fix Checking

**Goal**: 验证对所有触发 bug 条件的输入，修复后的函数产生期望行为。

**Pseudocode:**
```
FOR ALL newLimitBytes WHERE isBugCondition({ newLimitBytes, resetNeeded: true }) DO
  result := formatLimit_fixed(newLimitBytes)
  ASSERT result MATCHES /^\d+[MG]$/
  ASSERT ini_set('memory_limit', result) does NOT trigger E_WARNING
END FOR
```

### Preservation Checking

**Goal**: 验证对所有不触发 bug 条件的输入，修复后的函数与原始函数产生相同结果。

**Pseudocode:**
```
FOR ALL newLimitBytes WHERE NOT isBugCondition({ newLimitBytes, resetNeeded: true }) DO
  ASSERT formatLimit_original(newLimitBytes) = formatLimit_fixed(newLimitBytes)
END FOR
```

**Testing Approach**: 推荐使用 property-based testing 进行 preservation checking，因为：
- 自动生成大量测试用例覆盖输入域
- 捕获手动单元测试可能遗漏的边界情况
- 对非 bug 输入的行为不变提供强保证

**Test Plan**: 先在未修复代码上观察 K 范围及以下输入的行为，再编写 property-based test 验证修复后行为一致。

**Test Cases**:
1. **K 范围保留**: 验证 K 范围值（1024 < value ≤ 1024²）的转换结果在修复前后完全一致
2. **无单位范围保留**: 验证 ≤ 1024 字节值的转换结果在修复前后完全一致
3. **阈值逻辑保留**: 验证 `$upperThreshold`/`$lowerThreshold` 判断逻辑在修复前后行为一致
4. **Global_Switch 默认行为保留**: 验证未调用 `disableMemoryMonitor()` 时，函数行为与修复前一致

### Unit Tests

- 测试 M 范围非整数倍输入产生整数 Memory_Limit_String
- 测试 G 范围非整数倍输入产生整数 Memory_Limit_String
- 测试 M/G 范围整数倍输入产生正确的整数 Memory_Limit_String
- 测试 `disableMemoryMonitor()` 后 `monitorMemoryUsage()` 不执行逻辑
- 测试 `enableMemoryMonitor()` 后恢复正常执行
- 测试 enable/disable 不重置 `$isLowest`、`$neverReset` 内部状态

### Property-Based Tests

- 生成随机正整数 `newLimitBytes`，验证 Unit_Conversion 结果始终匹配 `/^\d+[KMGB]?$/`（无小数）
- 生成 K 范围随机值，验证修复前后转换结果一致（preservation）
- 生成 M/G 范围随机值，验证修复后结果为整数且 ≥ 原始值除以对应单位（ceil 语义）

### Integration Tests

- 测试完整 `monitorMemoryUsage()` 流程：高内存使用率 → 触发扩容 → 验证 `ini_get('memory_limit')` 为合法整数值
- 测试 Global_Switch 在 tick function 场景下的行为：注册 → 禁用 → 验证不再调整
- 测试 `error_get_last()` 在修复后不被 warning 覆盖

---

## Socratic Review

**Q: design 是否完整覆盖了 requirements 中的每条需求？**
A: 是。bugfix.md 中 2.1–2.4 的 Expected Behavior 均有对应的 Fix Implementation 条目（M 转换修复→2.1、G 转换修复→2.2、整体无 warning→2.3、Global_Switch→2.4）。3.1–3.6 的 Unchanged Behavior 在 Preservation Requirements 中逐条对应。

**Q: 技术选型是否合理？是否有更简单的替代方案？**
A: 修复方案极为简单——将 `ceil($x / 1024 * 100) / 100` 改为 `ceil($x / 1024)`，与 K 阶段统一。没有引入新依赖或新抽象。Global_Switch 使用静态方法对而非静态属性直接暴露，封装性更好且与 requirements CR 中用户选择一致。

**Q: 接口签名是否足够清晰，能让 task 独立执行？**
A: 是。Fix Implementation 明确了：属性名（`$memoryMonitorEnabled`）、类型（`bool`）、默认值（`true`）、方法签名（`enableMemoryMonitor(): void` / `disableMemoryMonitor(): void`）、插入位置（static 变量声明之前）。转换公式的修改也精确到行级别。

**Q: 是否有过度设计？**
A: 无。Global_Switch 是 requirements 明确要求的功能（2.4），不是预留扩展。修复本身只改两行公式，没有引入额外抽象层。

**Q: Impact Analysis 是否充分？**
A: 是。已覆盖 state 文档影响（需更新 `docs/state/utils.md`）、行为变化（M/G 值从浮点变整数）、数据模型（不涉及）、外部系统（不涉及）、配置项（不涉及）。

**Q: 是否存在未经确认的重大技术选型？**
A: 无。所有关键决策（静态方法对 vs 属性、ceil vs floor、状态保留 vs 重置）均在 requirements CR 中由用户确认。

---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 补充 `## Impact Analysis` section（覆盖 state 文档影响、行为变化、数据模型、外部系统、配置项五个维度）
- [结构] 补充 `## Socratic Review` section（覆盖需求覆盖度、技术选型、接口清晰度、过度设计、影响分析充分性）

### 合规检查
- [x] 无 TBD / TODO / 占位符
- [x] 无空 section
- [x] 内部引用一致（requirements 编号与 bugfix.md 对应）
- [x] 代码块语法正确
- [x] 无 markdown 格式错误
- [x] 一级标题存在且说明文件定位
- [x] 技术方案主体存在，承接 requirements
- [x] 接口签名有明确定义（参数类型、返回类型、插入位置）
- [x] 各 section 之间使用 `---` 分隔
- [x] Impact Analysis 覆盖五个维度
- [x] Requirements CR 决策已在 design 中体现（静态方法对、ceil、状态保留）
- [x] 每条 requirement 有对应技术方案
- [x] 无超出 requirements 范围的设计
- [x] 技术选型有明确理由
- [x] 无过度设计
- [x] 与 state 文档描述的现有架构一致
- [x] Socratic Review 覆盖充分

### Clarification Round

**状态**: 已完成

**Q1:** 实现顺序偏好——修复 M/G 转换公式和添加 Global_Switch 是两个独立变更，拆分 task 时应如何排序？
- A) 先修复转换公式（核心 bug fix），再添加 Global_Switch（增强功能）——优先解决 bug
- B) 先添加 Global_Switch（基础设施），再修复转换公式——开关就位后方便测试时隔离
- C) 合并为单个 task 一次性实现——变更量小，拆分反而增加开销
- D) 其他（请说明）

**A:** A）先修复转换公式（核心 bug fix），再添加 Global_Switch。

**Q2:** 测试策略中的 Exploratory Bug Condition Checking（在未修复代码上验证 bug 存在）是否需要作为独立 task 执行，还是合并到修复 task 的验证步骤中？
- A) 独立 task——先确认 bug 存在再动手修复，确保根因分析正确
- B) 合并到修复 task 的前置步骤——同一 task 内先验证后修复，减少 task 数量
- C) 跳过探索性测试——根因已通过源码分析确认，直接编写修复后的验证测试
- D) 其他（请说明）

**A:** A）独立 task，先确认 bug 存在再动手修复。

**Q3:** state 文档更新（`docs/state/utils.md`）应在何时执行？
- A) 作为修复 task 的一部分——代码改完立即更新 state，保持 SSOT 同步
- B) 作为独立的收尾 task——所有代码和测试完成后统一更新文档
- C) 不在本次 spec 中处理——由 doc-operator 或后续流程负责
- D) 其他（请说明）

**A:** B）独立收尾 task，所有代码和测试完成后统一更新文档。
