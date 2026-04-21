# Bugfix Requirements Document

本文件为 `.kiro/specs/memory-limit-warning-fix/` 的需求文档，定义 `monitorMemoryUsage()` 内存限制 warning 修复的验收标准。

---

## Introduction

`CommonUtils::monitorMemoryUsage()` 在动态调整 `memory_limit` 时，M 和 G 单位的转换逻辑使用 `ceil($x / 1024 * 100) / 100` 保留两位小数，生成类似 `317.19M` 的值。`ini_set('memory_limit', '317.19M')` 在 PHP 8.x 中触发 `E_WARNING: Invalid quantity`，该 warning 会覆盖 `error_get_last()` 中的原始错误信息，导致依赖 `error_get_last()` 检测 fatal error 的 shutdown function 失效。

此外，当前 `monitorMemoryUsage()` 没有全局开关，调用方无法在不修改调用点的情况下禁用内存监控行为。本次修复同时增加启用/禁用开关。

**不涉及的内容**：不修改内存监控的阈值算法逻辑、不修改 `registerMemoryMonitorForTick()` 的注册机制、不修改 CLI 日志输出格式。

---

## Glossary

- **Memory_Monitor**: `CommonUtils::monitorMemoryUsage()` 方法，负责动态调整 PHP `memory_limit` 的内存监控函数
- **Memory_Limit_String**: 传递给 `ini_set('memory_limit', ...)` 的值字符串，由数字部分和可选的单位后缀（K/M/G）组成
- **Unit_Conversion**: 将字节数转换为带单位后缀的 Memory_Limit_String 的过程
- **Global_Switch**: 控制 Memory_Monitor 是否执行监控逻辑的全局开关机制

---

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN THE Memory_Monitor 计算的新内存限制经 K→M Unit_Conversion 后不是 1024 的整数倍 THEN THE Memory_Monitor 生成带小数的 Memory_Limit_String（如 `317.19M`）并传给 `ini_set`，触发 `E_WARNING: Invalid quantity "317.19M"`

1.2 WHEN THE Memory_Monitor 计算的新内存限制经 M→G Unit_Conversion 后不是 1024 的整数倍 THEN THE Memory_Monitor 生成带小数的 Memory_Limit_String（如 `1.23G`）并传给 `ini_set`，触发 `E_WARNING: Invalid quantity "1.23G"`

1.3 WHEN THE Memory_Monitor 在 shutdown function 中被调用且产生上述 `E_WARNING` THEN `error_get_last()` 返回该 warning 而非原始的 `E_ERROR`，导致依赖 `error_get_last()` 检测 fatal error 的逻辑失效

1.4 WHEN 调用方希望在运行时禁用内存监控 THEN THE Memory_Monitor 没有提供 Global_Switch，必须修改所有调用点或取消 tick function 注册

### Expected Behavior (Correct)

2.1 WHEN THE Memory_Monitor 计算的新内存限制经 K→M Unit_Conversion 后不是 1024 的整数倍 THEN THE Memory_Monitor SHALL 生成整数值的 Memory_Limit_String（如 `318M`），`ini_set` 不产生任何 warning

2.2 WHEN THE Memory_Monitor 计算的新内存限制经 M→G Unit_Conversion 后不是 1024 的整数倍 THEN THE Memory_Monitor SHALL 生成整数值的 Memory_Limit_String（如 `2G`），`ini_set` 不产生任何 warning

2.3 WHEN THE Memory_Monitor 在 shutdown function 中被调用 THEN THE Memory_Monitor SHALL 不产生任何 `E_WARNING`，不影响 `error_get_last()` 的已有值

2.4 WHEN 调用方通过 Global_Switch 禁用内存监控 THEN THE Memory_Monitor SHALL 直接返回，不执行任何内存检测或 `ini_set` 操作

### Unchanged Behavior (Regression Prevention)

3.1 WHEN 内存使用率超过 `$upperThreshold` THEN THE Memory_Monitor SHALL CONTINUE TO 扩大 `memory_limit` 至合理值

3.2 WHEN 内存使用率低于 `$lowerThreshold` 且非首次调整 THEN THE Memory_Monitor SHALL CONTINUE TO 缩小 `memory_limit`（不低于 `$minUsage`）

3.3 WHEN 新限制值不超过 1024 字节 THEN THE Memory_Monitor SHALL CONTINUE TO 使用无单位后缀的纯数字值

3.4 WHEN 新限制值在 K 范围（1024 < value ≤ 1024²） THEN THE Memory_Monitor SHALL CONTINUE TO 使用 `K` 单位后缀且值为整数

3.5 WHEN CLI 模式下触发内存限制调整 THEN THE Memory_Monitor SHALL CONTINUE TO 向 stderr 输出调整日志

3.6 WHEN Global_Switch 未被设置（默认状态） THEN THE Memory_Monitor SHALL CONTINUE TO 正常执行内存监控逻辑，行为与修复前一致


---

## Socratic Review

**Q: 每条 requirement 是否都在描述外部可观察的行为？**
A: 是。所有条款描述的是 `ini_set` 调用的结果（是否产生 warning）、函数的返回行为（直接返回 vs 执行逻辑）、以及 `error_get_last()` 的可观察状态。对于 bugfix spec，引用具体函数名是合理的，因为修复目标就是特定函数的行为。

**Q: 是否有遗漏的场景？**
A: 2.1 和 2.2 使用 `ceil` 向上取整，但未明确当值恰好为整数时的行为（如 `2048K` → `2M`）。不过这属于 3.4 的 Unchanged Behavior 覆盖范围（K 范围整数值），且 M/G 整数倍情况下 `ceil` 结果本身就是整数，无需额外条款。

**Q: 各条款之间是否存在矛盾或重叠？**
A: 无矛盾。2.1/2.2 处理非整数倍情况，3.3/3.4 保护整数倍情况的现有行为，互不重叠。2.4 与 3.6 互补（开关开/关两种状态）。

**Q: 是否有隐含的前置假设？**
A: 有一个隐含假设——Global_Switch 的默认状态为"启用"（即不设置开关时 Memory_Monitor 正常工作）。这已在 3.6 中显式声明。另一个假设是 `ceil` 取整方向（向上），这在 Expected Behavior 的示例中隐含体现（`317.19M` → `318M`）。

**Q: scope 边界是否清晰？**
A: Introduction 已明确不涉及阈值算法、注册机制和日志格式。Global_Switch 的 scope 边界清晰——仅控制是否执行，不涉及参数调整。

---

## Gatekeep Log

**校验时间**: 2025-01-24
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] 补充一级标题下方的文件定位说明
- [结构] 补充 Introduction 的 Non-scope 声明
- [结构] 补充 `## Glossary` section，定义 Memory_Monitor、Memory_Limit_String、Unit_Conversion、Global_Switch
- [结构] 补充 `## Socratic Review` section
- [结构] 补充各 section 之间的 `---` 分隔线
- [语体] 将 AC 中的 `the system` 统一为 Glossary 中定义的术语（THE Memory_Monitor）
- [语体] 将 AC 中的"全局开关"统一为 Glossary 术语 Global_Switch
- [语体] 将"内存限制字符串"统一为 Glossary 术语 Memory_Limit_String
- [语体] 将"转换"统一为 Glossary 术语 Unit_Conversion

### 合规检查
- [x] 无 TBD / TODO / 占位符
- [x] 无空 section
- [x] 内部引用一致
- [x] 无 markdown 格式错误
- [x] 一级标题存在且含文件定位说明
- [x] Introduction 描述了修复范围
- [x] Introduction 明确了不涉及的内容
- [x] Glossary 存在且非空
- [x] Bug Analysis section 包含完整的 Current/Expected/Unchanged Behavior
- [x] 各 section 之间使用 `---` 分隔
- [x] AC 使用 WHEN/THEN/SHALL 语体
- [x] Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续无跳号
- [x] 内容聚焦外部可观察行为
- [x] Socratic Review 覆盖充分

### Clarification Round

**状态**: 已完成

**Q1:** Global_Switch 的 API 形式应如何设计？
- A) 静态属性：`CommonUtils::$memoryMonitorEnabled = false`（简单直接，调用方直接赋值）
- B) 静态方法对：`CommonUtils::enableMemoryMonitor()` / `CommonUtils::disableMemoryMonitor()`（封装性更好，未来可加钩子）
- C) 方法参数：在 `monitorMemoryUsage()` 增加 `$enabled` 参数（不改接口签名，但每次调用都要传）
- D) 其他（请说明）

**A:** B）静态方法对，默认 enabled，不改变现有行为。

**Q2:** Unit_Conversion 取整策略应使用 `ceil`（向上取整）还是 `floor`（向下取整）？向上取整保证分配的内存不少于计算值（如 317.19M → 318M），向下取整则更保守（317.19M → 317M）但可能略低于实际需求。
- A) `ceil`（向上取整）——保证分配量 ≥ 计算值，与当前逻辑方向一致
- B) `floor`（向下取整）——更保守的内存分配
- C) `round`（四舍五入）——取最接近的整数值
- D) 其他（请说明）

**A:** A）`ceil`（向上取整），与当前逻辑方向一致。

**Q3:** 当 Global_Switch 禁用后再重新启用时，Memory_Monitor 的内部状态（`$isLowest`、`$neverReset` 等 static 变量）应如何处理？
- A) 保留状态——重新启用后从上次状态继续，不重置
- B) 重置状态——重新启用时将内部 static 变量恢复为初始值，等同于首次调用
- C) 不关心——Global_Switch 一旦禁用就不再启用，无需处理此场景
- D) 其他（请说明）

**A:** A）保留状态，重新启用后从上次状态继续，不重置内部 static 变量。enable/disable 仅控制是否执行，不影响 `$isLowest`、`$neverReset` 等内部状态。
