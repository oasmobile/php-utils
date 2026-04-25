# PHP 8.2+ Syntax Modernization

> 来源：feature/upgrade-to-php8 后续观察

`upgrade-to-php8` 完成了最小化兼容性迁移（PHP >=8.2 + PHPUnit ^11.0），但源代码风格仍为 PHP 7.x 时代写法。可考虑对 `src/` 进行 PHP 8.2+ 新语法的现代化改造。

---

## 可探索方向

- Constructor promotion：减少属性声明样板代码
- `readonly` 属性：适用于不可变字段（如 Validator 配置参数）
- Union / intersection types：替代 PHPDoc 类型注解，增强静态分析
- `match` 表达式：替代简单 `switch` 语句
- Named arguments：提升可读性（尤其是多参数构造函数）
- `str_contains` / `str_starts_with` / `str_ends_with`：替代 `strpos` 惯用法
- `enum`：如有适用场景可引入
- 参数类型、返回类型、属性类型声明：全面补齐

---

## 约束提醒

- 公共 API 签名变更需评估对下游的影响
- `readonly` 和 constructor promotion 可能改变序列化行为
- 逐模块推进比一次性全量改造更可控
