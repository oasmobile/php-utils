# Spec Goal: PHP 8.2+ Syntax Modernization

## 来源

- 分支: `release/3.0.0`
- 需求文档: `docs/notes/php82-syntax-modernization.md`

---

## 背景摘要

v2.0.0 完成了 PHP >=8.2 + PHPUnit ^11.0 的最小化兼容性迁移，但源代码风格仍为 PHP 7.x 时代写法。`composer.json` 已要求 PHP >=8.2，基础设施就绪。

`docs/notes/php82-syntax-modernization.md` 提出了对 `src/` 进行 PHP 8.2+ 新语法现代化改造的方向，包括 constructor promotion、`readonly`、union/intersection types、`match` 表达式、named arguments、新字符串函数、`enum`、类型声明补齐等 8 个方向。

---

## 目标

- 对 `src/` 下所有模块进行 PHP 8.2+ 语法全量改造，覆盖 note 中列出的全部 8 个方向
- 引入 `enum` 替代现有常量/魔术字符串（`TrimmedStringValidator` direction、`DataProviderInterface` 类型常量、`AnsiColorizer` 颜色名等适用场景）
- 补齐参数类型、返回类型、属性类型声明
- 用 `match` 替代简单 `switch`
- 用 `str_contains` / `str_starts_with` / `str_ends_with` 替代 `strpos` 惯用法
- 适用处使用 constructor promotion 和 `readonly`
- 确保所有现有测试通过（必要时同步更新测试代码）

---

## 不做的事情（Non-Goals）

- 不新增功能或新增类
- 不变更业务逻辑或行为语义
- 不做性能优化（除非语法替换本身带来的）
- 不引入新的外部依赖
- 不编写旧数据兼容或迁移逻辑

---

## Clarification 记录

### Q1: Release scope

- 问题: Note 中列出了 8 个可探索方向，本次 release 的 scope 如何界定？
- 选项: A) 全量改造 / B) 仅低风险子集 / C) 按模块分阶段 / D) 补充说明
- 回答: A——全量改造，一次性覆盖所有 8 个方向

### Q2: 公共 API 签名变更的兼容策略

- 问题: 作为 minor version，公共 API 签名变更如何处理？
- 选项: A) 严格 semver / B) 允许技术性 breaking change / C) 升级为 major / D) 补充说明
- 回答: D——允许 breaking change
- 后续修正: 用户确认这实质上是 major bump，版本号从 2.1.0 改为 3.0.0

### Q3: enum 引入策略

- 问题: enum 的引入范围？
- 选项: A) 有适用场景就引入 / B) 本次不引入 / C) 仅最明显场景 / D) 补充说明
- 回答: A——有明确适用场景就引入

---

## 约束与决策

- **版本**: v3.0.0（major bump，包含 breaking change）
- **范围**: 全量改造，覆盖所有 8 个语法方向，所有 `src/` 模块
- **enum**: 有适用场景即引入，不限制范围
- **行为不变**: 语法现代化不改变任何业务逻辑和行为语义
- **测试**: 现有测试必须全部通过，必要时同步更新测试代码适配新签名
- **无旧数据兼容**: 不编写迁移逻辑
