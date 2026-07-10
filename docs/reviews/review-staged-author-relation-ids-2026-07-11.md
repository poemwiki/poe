# Staged Review：Author dynasty/nation IDs

## 范围

本次只审查 `git diff --staged`，包括作者导入/更新接口、API 文档和相关测试。未暂存的 `database/migrations/2026_02_30_migrate_poem_translator_id.php` 不在范围内。

审查取向：Linus Torvalds 强调数据结构、清晰控制流、删除特殊情况和拒绝“聪明”代码；John Carmack 强调直接、可验证、容易推理的实现，以及让断言覆盖真实失败模式。另以 SSOT、DRY、YAGNI、注释质量和测试稳定性作为检查项。

## 结论

方向合理：关联 ID 先校验再赋值，导入命中已有作者时不静默覆盖，部分更新也开始保留未提交字段。初审发现 2 个行为缺陷和 3 个设计/测试问题；处理及复核结果见每条回复和文末“修复后复核”。

## 处理状态

- 已明确支持显式 `null` 清空关联，并补充契约与测试。
- 已排除软删除的 Dynasty/Nation。
- 已统一关联字段白名单、补齐失败边界，并将 lookup builder 从全局 `TestCase` 下沉到局部 concern。
- 已修正过时 docblock、测试文件末尾换行和 Dynasty `45` 的名称。
- Dynasty 完整字典目前仍需保留；后续数据字典查询接口记录在 `docs/todo.md`。

## Findings

### P1：显式 `null` 会清空关联，与公开契约冲突

位置：`app/Http/Controllers/API/AuthorAPIController.php:257-265`、`:83-90`、`:127`

规则使用了 `nullable`：

```php
'dynasty_id' => ['nullable', 'integer', 'exists:' . Dynasty::class . ',id']
```

Laravel 遇到 `null` 会跳过后续 `integer`/`exists`；随后 `validated()` 仍保留该键，`extractAuthorRelationIds()` 也保留它，最终 `fill()` 把数据库字段写成 `null`。因此“未提交”与“显式提交 null”具有完全不同的效果，但文档只说“若提交，必须是已存在 ID”，没有声明清空语义。

这不是无害兜底，而是隐藏的第三种状态。按 Carmack 的可推理性原则，API 应明确二选一：

- 不支持清空：去掉 `nullable`，显式 `null` 返回 422；
- 支持清空：文档明确 `null` 表示解除关联，并为 import/update 分别补测试。

> **回复：采纳“支持清空”。** 保留 `nullable`；update 显式传 `null` 时解除对应关联，未提交字段仍保持原值。`docs/api-import-guide.md` 已分别写明 import 的 `null` 表示新作者不设置关联、update 的 `null` 表示清空关联；API 测试覆盖两种行为。

### P1：`exists:Model,id` 会接受软删除的 Dynasty/Nation

位置：`app/Http/Controllers/API/AuthorAPIController.php:257-261`

`Dynasty` 和 `Nation` 都使用 `SoftDeletes`，但字符串形式的 `exists` 只查询表中是否有该 ID，不会自动附加 `deleted_at IS NULL`。于是 API 可以把作者绑定到业务上已删除、正常 Eloquent 关系又不可见的记录，制造“ID 有值但关系为空”的不一致状态。

建议使用带条件的 `Rule::exists(...)->whereNull('deleted_at')`，并加一个软删除 lookup 的失败用例。Linus 式的重点不是让校验看起来简短，而是让数据不变量在唯一入口处真实成立。

> **回复：采纳。** `authorRelationIdRules()` 已改用 `Rule::exists($model, 'id')->whereNull('deleted_at')`。测试覆盖 import 拒绝软删除/不存在的 Dynasty，以及 update 拒绝软删除 Nation 且作者原有关联保持不变。

### P2：关联字段白名单出现三份，已经违反 SSOT

位置：`app/Http/Controllers/API/AuthorAPIController.php:83-85`、`:138-145`、`:257-265`

同一知识“作者可接收 `dynasty_id`、`nation_id`”同时存在于：

1. `importSimple()` 的 `$request->only([...])`；
2. `authorRelationIdRules()` 的键；
3. `extractAuthorRelationIds()` 的硬编码数组。

新增第三个字段时，漏改任何一处都会静默丢字段。这正是 Linus 所反对的坏数据结构：控制流不复杂，但正确性依赖人工同步列表。

以 `array_keys($this->authorRelationIdRules())` 作为唯一字段来源即可。`update()` 的 validator 本来只包含这组字段，`extractAuthorRelationIds($validator->validated())` 在那里更是重复过滤；可直接 `fill($validator->validated())`。import 的合并校验结果需要过滤时，也应从 rules 的键派生，而不是再写一份 literal。

> **回复：采纳。** 新增 `AUTHOR_RELATION_ID_MODELS`，以“字段名 → 模型”映射同时派生请求白名单、校验规则和 import 提取范围；update 直接使用 `$validator->validated()`，不再重复过滤。

### P2：测试只证明 happy path，没证明新校验真的工作

位置：`tests/APIs/AuthorImportApiTest.php:35-66`、`tests/APIs/AuthorUpdateApiTest.php:40-70`

新增测试创建合法 lookup，再断言相同 ID 被写回。这不是严格意义上的 `constantA = literalA`，但具有相同弱点：它主要证明 Eloquent 能保存两个整数，没有验证本次新增代码最重要的业务边界——拒绝坏引用且失败时不产生/不修改作者。

建议至少覆盖稳定行为边界：

- import 提交不存在或软删除 ID，返回 422 且不创建 author；
- update 提交不存在或软删除 ID，返回 422 且原 name/desc/关联均不变；
- 只提交一个关联字段时，另一个保持原值；
- 根据 P1 的决策，明确验证 `null` 是拒绝还是清空。

这也符合仓库 `AGENTS.md` 对业务规则、异常输入、状态转换以及 happy/sad path 的要求。

> **回复：采纳。** 已补充公开 HTTP API 层测试：不存在 ID、软删除 ID、失败后不创建/不修改、只提交一个字段时保留另一个字段，以及显式 `null`。断言关注响应和持久化行为，不测试私有 helper。

### P2：通用 `TestCase` 被迫知道 lookup schema，并加入无依据兼容分支

位置：`tests/TestCase.php:12-36`

`createLookupId($table, $name)` 看似 DRY，实际把 `dynasty`/`nation` 的表名、JSON 编码、`f_id`、时间戳以及可选 `name` 列泄漏进所有测试的根基类。`Schema::hasColumn($table, 'name')` 每次建数据都探测 schema，却没有证据表明受支持环境存在两套 schema；当前 migration 明确两个表都有 `name`。这是多余兜底兼容，也让错误 schema 更晚暴露。

更直接的做法是使用各自模型/工厂创建真实领域对象，或在该 API 测试类的 concern 中提供两个明确 helper。不要为了复用十几行代码，把数据库结构知识提升到全局 `TestCase`。Carmack 倾向可局部理解的重复，而不是错误抽象；Ousterhout 的视角则是这个 helper 接口很浅，却泄漏了大量实现知识。

> **回复：采纳，并修正原 review 的一处判断。** `tests/TestCase.php` 已恢复为空基类，lookup 构造下沉到 `tests/Concerns/CreatesAuthorLookups.php`，分别使用 `Dynasty`、`Nation` 模型，不再运行时探测 schema。原文称“当前 migration 明确两个表都有 `name`”不完整：后续 migration 已删除 `nation.name`；这正说明通用 helper 的兼容分支掩盖了领域差异，分别构造更合适。

## 次要问题

### 过时的 import docblock

`app/Http/Controllers/API/AuthorAPIController.php:133-136` 的 docblock 仍声称只接受 `name, describe, describe_locale, wikidata_id`，与新增字段不一致。它只是重复字段清单，而且已经产生漂移；应删除字段枚举，或改为解释这个端点为何对已存在作者不覆盖关联。

> **回复：采纳。** 已删除易漂移的字段枚举，改为说明该入口“导入单个作者且不修改已匹配作者”，记录接口的关键策略而不是复述参数。

### Dynasty 字典与后续查询接口

`docs/api-import-guide.md:710-790` 的完整 Dynasty 字典目前是 API 使用方所需内容，因此保留；其中 `梁梁` 已修正为 `南朝梁`。动态查询数据字典的接口作为后续任务记录在 `docs/todo.md`。

> **回复：部分采纳。** 按产品需要保留完整字典，修正 ID 45 为“南朝梁”；在 `docs/todo.md` 记录公开数据字典查询接口及未来减少文档漂移的方向。本次不扩展 API 范围。

### 测试文件末尾缺少换行

`tests/APIs/AuthorUpdateApiTest.php` 文件末尾缺少换行；`git diff --staged --check` 已报告该问题。虽然工具可自动修复，但提交前应处理。

> **回复：采纳。** 已补齐文件末尾换行，并通过 `git diff --check`。

## 建议修改顺序

1. 先决定并固定 `null` 语义。
2. 将 lookup 校验改为排除软删除记录。
3. 用 rules 的键统一白名单，删除重复过滤。
4. 补 sad path 和单字段部分更新测试。
5. 收窄测试 helper，删除 schema 探测兼容。
6. 保留当前完整 Dynasty 字典，修正文档漂移，并另行规划数据字典查询接口。

## 修复后复核

### P2：初次回复声称 import 已覆盖显式 `null`，但当时缺少对应测试

第一次处理状态把 import/update 的 `null` 覆盖写在一起，但当时只有 update 清空用例。虽然实现允许 import 接受 `null`，文档回复不应超前于证据。

> **回复：采纳并补齐。** 新增 `test_import_accepts_explicit_null_author_relation_id`，通过公开 HTTP API 确认 import 显式传 `null` 会创建无对应关联的作者。测试不调用私有过滤方法。

### P2：三个作者创建分支最初只覆盖纯名称分支

关联 ID 分别经过“本地已有 Wikidata 详情”“只有未知 Wikidata ID”“纯名称”三条创建路径；第一条使用 `fill() + save()`，后两条使用 `array_merge() + create()`，不能由一个 happy path 推断全部正确。

> **回复：采纳并补齐。** 新增两个 HTTP API 测试，分别覆盖本地 Wikidata 仓储导入和未知 Wikidata ID 的最小记录创建，并断言 Dynasty/Nation 都被持久化。连同原纯名称用例，三条路径均有行为测试。

### P3：格式化器扩大了 `AuthorImportApiTest.php` 的 diff

执行仓库 PHP CS Fixer 后，旧测试中的括号、引号、对齐与空行也被统一，产生了与功能无关的 review 噪音。这不改变行为，但扩大人工审查面。

> **回复：确认风险，保留格式化结果。** 这些改动全部由仓库指定的 PHP CS Fixer 产生，没有手工混入逻辑修改；考虑到仓库明确要求提交前运行 formatter，本次不反向恢复为不符合现行规则的格式。复核时已将新增行为测试与纯格式化 diff 分开检查。

### 复核结论

没有发现新的 `null` 更新语义、软删除校验、SSOT、过度设计或 tautological test 问题。新增的局部 concern 只封装测试数据构造，不参与生产逻辑；数据字典查询接口仍保持为独立 TODO，没有扩大本次 API 范围。
