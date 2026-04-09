# 变更审阅报告（2026-02-15）

范围与前提
- 当前 Git 暂存区为空；基于工作区新增文件进行审阅：  
  - docs/auth.md  
  - docs/translators.md  
  - docs/translated-poem.md  
  - database/migrations/2026_02_30_migrate_poem_translator_id.php
- 为与仓库约定保持一致，报告写入 docs/review.md（而非 doc/）。

概览结论
- 文档增补质量较高，结构清晰、上下文充分，适合后续重构对齐；英文/中文双语覆盖核心域。
- 迁移脚本能完成基础回填，但在“幂等性、正确性与可运维性”方面仍有改进空间（见下）。

关键建议（TL;DR）
- 迁移脚本
  - 建议以“若已存在任一 translator_is 关联则整体跳过该诗”的策略避免与既有多译者数据叠加。
  - 分批（chunk）处理并使用游标查询，避免长事务与内存压力；增加日志总览与进度。
  - 更正迁移文件名中的日期（2026_02_30 不存在），保持与既有命名一致性。
  - 考虑新增（或确认已存在）relatable 索引：`(start_type, start_id, relation)` 及联接查询常用组合。
- 文档
  - docs/auth.md：补充登录节流/锁定策略、CSRF 与错误消息规范；Next.js 节建议强调 Node 运行时与 `$2y$/$2b$` 兼容说明。
  - docs/translators.md：落实 `RELATION_RULES` 的运行时校验（译者数上限 6）；记录译者顺序持久化方案；更新时尽量采用增量 diff。
  - docs/translated-poem.md：统一字段命名风格（snake_case/camelCase）；校对被引用的迁移文件名；补充唯一性/外键约束与缓存失效策略清单。

按文件反馈

1) docs/auth.md（Web 登录：邮箱 + 密码）
- 优点：路由分支解释清楚；`AuthenticatesUsers` 职责与 `redirectTo` 行为阐述准确；配置片段与默认 guard/provider 对齐；Node 侧哈希兼容说明到位。
- 建议：
  - 登录安全：明确是否启用 Laravel 自带节流（throttle）/锁定策略，建议记录默认阈值与解锁机制。
  - CSRF 与错误信息：补充 CSRF 校验与统一错误消息规范（避免泄露“邮箱是否存在”）。
  - 验证码：若保持可选，标注可配置开关与表单/控制器改动点。
  - Next.js 运行时：突出“Edge 不支持原生 bcrypt，需使用 bcryptjs 或改 Node 运行时”；强调 `$2y$/$2b$` 前缀兼容策略与轮数一致性注意事项。

2) docs/translators.md（译者体系）
- 优点：数据模型、写入路径、缓存预加载与展示链路描述完整，覆盖 Web/Admin/API/批量导入；强调 legacy 字段兼容与缓存字段复用，便于重构对齐。
- 建议：
  - 运行时约束：将 `RELATION_RULES['translator_is']` 的“最多 6 位”落到写入逻辑（验证层或领域服务），失败时返回清晰错误。
  - 顺序持久化：明确译者顺序的最终承载（继续暂存于 `poem.translator` JSON 或迁移到 `relatable.properties`），并定义读取端优先级。
  - 增量更新：`update()` 处当前“先删后建”，可引入集合 diff 以减少写放大与日志噪音。
  - 索引与一致性：建议在文档中列出 `relatable` 必需索引与唯一性约束建议，方便新栈建模时不遗漏。

3) docs/translated-poem.md（Translated Poem Domain）
- 优点：域概念、缓存与树构建流程、API 触点与边界条件说明到位，便于重写保持行为等价。
- 建议：
  - 命名与引用：统一风格（字段/变量命名）；校对示例中的迁移文件名与当前仓库保持一致。
  - 约束清单：补充推荐的唯一/外键约束与必要的覆盖性索引；对 `merged_to_poem` 的排除策略给出查询准则。
  - 缓存策略：增加“失效触发面”清单，覆盖所有可能变更译者关系的路径（含批量导入与合并）。

4) database/migrations/2026_02_30_migrate_poem_translator_id.php（迁移）
- 正确性与幂等性：
  - 目前仅判断“相同 end_id 的关系是否存在”，若诗已有其他译者关联，仍会新增一条，可能导致与 `translator_id` 的历史语义叠加。建议改为：只要存在任一 `translator_is` 关联即跳过该诗。
  - 未验证 `translator_id` 对应的作者是否存在；如不存在作者，是否应回退为 `Entry` 或直接跳过需在注释中明确。
- 性能与运维：
  - 无分批/游标处理，数据量大时可能拉长事务与锁时间；建议 `chunkById()`/`cursor()` 并在批次间日志进度。
  - 建议在迁移前确认/新增 `relatable` 查询常用索引（至少 `(start_type, start_id, relation)` 与 `(start_type, relation)`）。
- 命名与一致性：
  - 文件名日期无效（2 月 30 日），建议修正为实际日期；保持与历史文件前缀一致的排序语义。
- 代码风格：
  - 采用 `DB::table` 直写可避免模型事件，适合作为回填；日志记录总计数已具备，可考虑添加跳过数量统计。

可选伪代码（供后续修订参考）
```php
// 仅当该诗尚无任何 translator_is 关联时才回填 translator_id
if (!DB::table('relatable')
      ->where('start_type', $poemType)
      ->where('start_id', $poem->id)
      ->where('relation', Relatable::RELATION['translator_is'])
      ->exists()) {
    // 进一步校验作者存在性再写入
}
// 大表建议分批处理（cursor/chunkById）并记录进度
```

后续建议
- 提交建议
  - 文档与迁移脚本建议分别提交，commit message 示例：  
    - docs: add auth/login flow and translator domain notes  
    - chore(db): backfill poem.translator_id to relatable translator_is
- 校验清单
  - 本地/预发执行迁移前后抽样核对：译者字符串与译者头像/作者页链接是否一致；合并/缓存是否按预期失效与重建。
  - 执行后统计：回填数量、跳过数量、异常记录数（作者缺失等）。

