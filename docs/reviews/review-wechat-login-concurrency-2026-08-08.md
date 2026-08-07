# 微信登录并发修复 Review

- 审查日期：2026-08-08
- 固定点：`26e50f2b030e45768a30a5e65068cbeb9da2146f`
- 被审提交：`09e76e798db57853d1a98831b5f27e33463e7b23`（`fix(auth): serialize concurrent WeChat registration`）
- Diff：`git diff 26e50f2b030e45768a30a5e65068cbeb9da2146f...09e76e798db57853d1a98831b5f27e33463e7b23`
- 当前阶段：正式 Review 已关闭

## 首轮结论

核心并发方案方向正确：两个入口统一按“跨渠道 unionid → 单渠道 openid”的顺序执行锁定读，用户与绑定在同一事务创建，数据库查询异常继续抛出，`Registered` 事件和 token 均在提交后执行。不过，openid 历史冲突仍可能导致登录到不确定账号，这是本轮最严重的正确性问题。本轮共记录 9 条可执行意见和 2 条已验证观察。

## 首轮意见（按严重程度排序）

### 1. [P1][Actionable] openid 冲突时仍会任取一个账号

- 文件/行：`app/Http/Controllers/API/LoginWeAppController.php:223-238`；`app/Http/Controllers/Auth/LoginWechatController.php:127-142`
- 问题：openid 查询使用无排序的 `first()`。如果同一个 `(open_id, bind_ref, bind_status = 1)` 再次绑定到多个不同 `user_id`，登录会取得执行计划碰到的第一条记录，而不是像 unionid 冲突一样拒绝登录。
- 为什么：本次选择了“程序层约束、无数据库唯一约束”，因此程序必须完整验证身份不变量。数据库数据刚修复并不能保证其他系统写入或未来代码不会再次产生冲突；任取一条会重新引入“用户登录到错误账号”的原始风险。增加 `ORDER BY` 只能让错误选择稳定，不能让选择正确。
- 建议：锁定并读取该有效 openid 的全部匹配记录，按 `user_id` 去重；超过一个用户时明确拒绝登录。同一用户存在重复绑定时，可以按最小 `id` 确定性选取，同时保留对整个等值索引范围的锁。
- 处理：采纳。两个入口现在都锁定并读取全部有效 openid 绑定，按 `user_id` 去重后检测冲突；多用户时拒绝登录，同一用户的重复绑定按 `id` 确定性取第一条。小程序和微信网页均增加了拒绝登录测试。

### 2. [P1][Actionable] 缺少“一个 unionid 绑定多个用户”的核心 sad-path 测试

- 文件/行：`tests/APIs/WeChatLoginConcurrencyTest.php:140-197`；生产分支位于 `LoginWeAppController.php:82-88`、`LoginWechatController.php:38-44`
- 问题：新增了 unionid 多用户冲突即终止登录的分支，但现有测试只覆盖“openid 与 unionid 分别指向两个用户”，没有覆盖“同一个 unionid 的有效绑定本身指向多个用户”。
- 为什么：这是历史事故数据的直接防线，也是高风险身份归并规则。缺少测试时，未来把 `get()` 改回 `first()` 或提前取首条都不会被发现。
- 建议：构造两条 `bind_status = 1`、unionid 相同、`user_id` 不同的绑定，至少验证小程序入口拒绝登录、事务无新增用户/绑定、无 `Registered`、无 token；如果第 6 条重复逻辑不抽取，还应覆盖网页入口。
- 处理：采纳。新增小程序和微信网页两个 sad path，验证同一有效 unionid 指向多用户时拒绝登录，不派发 `Registered`，小程序也不签发 token。

### 3. [P1][Actionable / Process] 未满足仓库要求的 Cucumber 前端交互测试

- 文件/行：`AGENTS.md`“测试准则”；本提交仅新增 `tests/APIs/WeChatLoginConcurrencyTest.php` 与 PHP worker fixture
- 问题：仓库明确要求涉及用户交互的业务流程必须有 Cucumber happy path、sad path，并覆盖前端交互层；本提交只有后端 PHPUnit/API 级验证。
- 为什么：这是文档化的硬性流程标准，不是一般代码风格建议。当前仓库未发现 Cucumber/Behat 配置或小程序前端代码，但基础设施缺失本身不会自动豁免标准。
- 建议：若前端位于其他仓库，应在对应仓库补充端到端场景并在此处记录链接；若本次无法补充，应由负责人写出明确、可复核的不采纳理由与后续跟踪项，避免把后端并发测试误报成完整用户流程覆盖。
- 处理：本次不采纳。本仓库没有 Cucumber/Behat 基础设施，也不包含微信小程序前端，无法在本次后端修复中提供可执行的前端 Cucumber 场景。本次只声明已完成后端接口与真实 MySQL 并发覆盖，不将其表述为完整前端交互覆盖。要补齐该项必须在实际小程序前端仓库中建立对应测试基础设施，超出当前仓库和本次修复范围。

### 4. [P2][Actionable] unionid 锁依赖的外部索引没有成为可审计的部署契约

- 文件/行：`LoginWeAppController.php:82-84,246-263`；`LoginWechatController.php:38-40,148-165`；`database/migrations/2026_07_31_000000_add_identity_lock_indexes_to_user_bind_info.php:8-14`
- 问题：unionid 锁依赖 `(union_id_crc32, union_id, bind_status)` 组合索引缩小扫描和锁范围，但仓库 migration 按规格不创建该索引，仓库文档中也查不到这个由其他系统维护的前置条件。
- 为什么：新环境、灾备恢复或外部系统迁移时容易漏掉该索引。查询通常仍能保持正确性，但可能退化为大范围扫描/加锁，造成登录互相阻塞和死锁放大；代码库无法独立说明其运行条件，违反 SSOT。
- 建议：不要在本 migration 重复创建索引；把“必须存在列顺序等价的普通索引、索引名不限”写入部署或数据库架构文档，并在发布检查中用 `SHOW INDEX`/`EXPLAIN` 验证。
- 处理：采纳。已在 `docs/auth.md` 增加“微信登录并发约束”，记录锁顺序、事务边界、两个组合索引的列顺序、unionid 索引由外部系统管理的契约，以及发布前 `SHOW INDEX`/`EXPLAIN` 检查。

### 5. [P2][Actionable] unionid 同步会改写已经停用的数据修复记录

- 文件/行：`LoginWeAppController.php:103-113`；`LoginWechatController.php:57-67`
- 问题：unionid 变化时，批量更新按 `user_id` 和微信渠道筛选，却没有限制 `bind_status = 1`，因此已修复为 `bind_status = 0` 的错误绑定也会被改写 unionid 与 CRC32。
- 为什么：虽然停用记录不会参与当前登录，但它们是修复痕迹和潜在审计数据。一次正常登录不应静默改变无效绑定的身份值；注释表达的也是同步当前用户的微信绑定，而没有解释为何需要改写历史无效记录。
- 建议：除非领域规则明确要求同步历史记录，否则批量更新增加 `where('bind_status', 1)`；并增加测试确认无效绑定既不参与身份解析，也不会被登录流程改写。
- 处理：采纳。两个入口的 unionid 同步都增加 `bind_status = 1`限制；测试同时验证无效绑定不参与 unionid 归并，且原 unionid 和 CRC32 对应的身份记录不被登录改写。

### 6. [P2][Actionable / Design smell] 两个控制器复制了完整身份解析与锁协议

- 文件/行：`LoginWeAppController.php:70-167,223-263`；`LoginWechatController.php:35-101,127-165`
- 问题：unionid 锁、冲突检测、openid 锁、账号归并、重试参数和两个查询 helper 在两个入口重复实现，属于 Duplicated Code / Shotgun Surgery。
- 为什么：锁顺序是正确性的协议，而不仅是普通查询代码。未来新增渠道或只修改其中一个入口，很容易造成锁序漂移、冲突判断不一致或再次吞掉异常。Linus/Carmack 风格强调把关键不变量放在一个清晰、可验证的位置。
- 建议：在不引入新身份设计的前提下，抽出一个小型的微信身份解析/锁定服务或现有 Repository 方法；控制器保留渠道资料映射、创建绑定及登录响应。如果本次严格不接受结构调整，至少把锁序及其原因集中成一处文档，并针对两个入口分别测试。
- 处理：本次不抽取新服务。用户已明确“不引入新设计，只在现有逻辑上解决竞态”；现在抽取身份服务会超出范围，并扩大登录回归面。作为可复核的替代措施，锁顺序和原因已集中写入 `docs/auth.md`；小程序与微信网页均覆盖并发 happy path、openid/unionid 冲突 sad path 和隔离级别恢复。

### 7. [P2][Actionable] `SET SESSION` 会把隔离级别泄漏给同一持久连接的后续请求

- 文件/行：`LoginWeAppController.php:70-72`；`LoginWechatController.php:35-37`
- 问题：`SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ` 不只作用于紧随其后的登录事务，而是修改当前连接后续事务的默认隔离级别。
- 为什么：PHP-FPM 的短连接下影响有限，但 PDO 持久连接、Octane 或常驻 worker 会复用连接；原本配置为 `READ COMMITTED` 的其他请求会被静默切换到 RR，形成隐藏的跨请求状态。测试特意先设置 RC，也证明应用不能假定连接初始状态固定。
- 建议：记录并在 `finally` 恢复原 session 隔离级别，或封装一个每次重试前为“下一个事务”设置 RR 的事务执行器。无论采用哪种方式，都应增加异常路径测试，保证事务抛错后也不会污染连接。
- 处理：采纳。两个入口在修改前读取 `@@SESSION.transaction_isolation`，并在 `finally` 中使用参数化的 session 变量赋值恢复原级别。小程序和微信网页都覆盖成功与查询异常路径，初始隔离级别均为 `READ COMMITTED`。

### 8. [P3][Actionable] 重试次数 `5` 是分散的硬编码策略

- 文件/行：`LoginWeAppController.php:167`；`LoginWechatController.php:101`
- 问题：两个入口分别硬编码事务尝试次数 `5`，没有命名，也没有说明该数值为何适合当前锁竞争。
- 为什么：这属于并发策略而非偶然字面量；两个入口未来可能漂移，排查死锁重试行为时也难以发现策略来源。
- 建议：至少提取为语义明确的共享常量（例如登录事务最大尝试次数），并用简短中文注释说明它用于 InnoDB deadlock retry；不要为该常量本身写 `constant = literal` 测试。
- 处理：采纳。重试策略提取为 `UserBind::LOGIN_TRANSACTION_ATTEMPTS`，两个入口共享；其语义是 Laravel 在 InnoDB 死锁时的登录事务最大尝试次数，未增加常量字面值测试。

### 9. [P3][Actionable / Test smell] token 时点测试绑定 Passport 的具体 INSERT SQL

- 文件/行：`tests/Fixtures/run_weapp_login.php:28-31`；断言位于 `tests/APIs/WeChatLoginConcurrencyTest.php:249-258`
- 问题：fixture 通过字符串匹配 ``insert into `oauth_access_tokens` `` 并写临时文件来判断 token 创建时的事务层级。
- 为什么：核心要求“token 在提交后”值得测试，但当前探针绑定 Passport 的表名和 SQL 形态；升级 Passport、替换 token 存储或改变 SQL quoting 后，业务行为未变测试也会失败。这是对实现细节的脆弱依赖。
- 建议：优先在 token 创建的应用边界注入可观察 spy，或监听稳定的模型/领域事件并记录 `DB::transactionLevel()`；并发进程与文件屏障本身可以保留。
- 处理：本次不采纳。Passport 当前没有在本调用路径上提供稳定且能观测实际 token 持久化时点的公开事件；为 `createToken()` 注入 mock 只能证明 mock 在事务外被调用，不能证明真实 Passport token 在事务外落库。当前探针在独立进程中观测真实持久化边界；如 Passport 升级改变存储契约，测试失败会要求重新验证该高风险时序，因此本次保留。

## 已验证观察（非问题）

### 10. [Verified] 锁顺序和不存在记录时的串行化方向正确

- 两个入口都先锁跨渠道 unionid，再锁当前渠道 openid，锁序一致。
- 在 MySQL InnoDB RR 下，查询命中完整非唯一二级索引等值范围；身份不存在时依靠 gap/next-key lock，竞争插入通过死锁回滚和 Laravel 的事务重试收敛。
- openid 使用显式组合索引；unionid 按规格不写死 `FORCE INDEX`，migration 也没有重复创建外部已有索引。

### 11. [Verified] 事务边界、异常语义和副作用时点符合规格

- 用户与 `user_bind_info` 创建在同一事务内。
- 原有把查询异常转成 `null` 的 catch 已删除，数据库异常会终止事务并继续抛出。
- `Registered` 事件、Passport token 和网页登录 guard 登录均在事务提交后执行。
- 身份查询固定包含 `bind_status = 1`，已停用的错误绑定不会被用于账号解析。
- 未发现数据库唯一约束、MySQL 命名锁、unionid `FORCE INDEX`、源码字符串否定断言或典型 `constantA = literalA` / `mockA = A` 测试。

## 复核

复核范围：当前工作树相对 `09e76e798db57853d1a98831b5f27e33463e7b23` 的修正。

### 逐条复核结果

1. **Resolved** — 两个 openid helper 均改为 `orderBy('id')->get()`，调用方在取首条前按 `user_id` 去重并拒绝多用户冲突。查询无 `LIMIT`，在 `FOR UPDATE` 下会读取并锁定完整等值范围；所有仓库内调用均已同步为 Collection 语义。小程序与微信网页的 openid 多用户测试均通过。
2. **Resolved** — 已为小程序和微信网页分别增加 unionid 多用户 sad path，验证拒绝登录且不触发对应提交后副作用。
3. **Accepted rationale** — 当前仓库没有 Cucumber/Behat 基础设施，也没有微信小程序前端，无法在本仓库提供前端 Cucumber 场景。处理结果明确限定当前交付为后端真实 MySQL 并发覆盖，没有把它表述为完整前端交互覆盖；该受限项不阻塞本次后端修复。
4. **Resolved** — `docs/auth.md` 已记录 unionid 外部索引的列顺序、所有权、名称非契约，以及发布前 `SHOW INDEX`/`EXPLAIN` 检查要求；migration 没有重复创建该索引。
5. **Resolved** — 两个 unionid 同步语句均增加 `bind_status = 1`；测试证明无效绑定既不参与归并，也不会被改写 unionid/CRC32。
6. **Accepted rationale** — 用户明确限制“不引入新设计，只解决竞态”，此时抽取新服务会扩大回归面。替代措施已落实：`docs/auth.md` 集中记录锁协议，两个入口均有独立的冲突和隔离恢复测试。重复代码仍是后续维护风险，但在本次范围内理由充分。
7. **Resolved** — 两个入口均先读取原 session 隔离级别，在 `finally` 中恢复；成功路径和查询异常路径均覆盖小程序与微信网页。复核执行的 12 项测试包含这些路径并通过。
8. **Resolved** — 重试次数已集中为 `UserBind::LOGIN_TRANSACTION_ATTEMPTS`，中文注释说明该值是 InnoDB 死锁时 Laravel 的最大事务尝试次数。放在 `UserBind` 虽非理想的全局策略位置，但与本次不新增服务的范围约束一致，且消除了两个入口的策略漂移。
9. **Accepted rationale** — 当前 Passport 路径没有同时满足“稳定公开边界”和“真实持久化时点”的事件；mock 只能证明调用位置，不能证明 token 真正落库时点。保留真实 SQL 持久化探针能更强地保护本次高风险事务边界，Passport 存储契约变化时要求重新验证是可接受代价。

### 复核验证

- `tests/APIs/WeChatLoginConcurrencyTest.php`：12 tests，72 assertions，通过。
- `tests/APIs/UserLoginApiTest.php`：7 tests，32 assertions，通过。
- 三个生产 PHP 文件语法检查通过。
- 本次修改的四个 PHP 文件逐文件 PHP CS Fixer dry-run 通过。全仓库 dry-run 仍报告大量既有文件以及无关的未跟踪 migration，不属于本次 diff。
- `git diff --check 09e76e798db57853d1a98831b5f27e33463e7b23` 通过。

### 新增意见

#### 12. [P3][Actionable] `docs/auth.md` 的范围说明与新增微信章节矛盾

- 文件/行：`docs/auth.md:130` 及 `docs/auth.md:189`“微信登录并发约束”章节
- 问题：文档原文仍写着“本文档仅覆盖 Web 侧邮箱 + 密码登录；API 登录与第三方（微信/小程序）不在范围内”，但本次已在同一文档新增微信登录并发约束。
- 为什么：读者会同时得到“本文不覆盖微信”和“本文覆盖微信关键约束”两个相反信号，削弱第 4 条作为部署 SSOT 的效果。
- 建议：把原范围说明改为只限定它紧邻的密码登录章节，或直接说明“下文章节另行覆盖微信登录并发约束”。
- 处理：采纳。原说明已改为只限定前文的 Web 邮箱密码登录内容，并明确指向文末的微信/小程序并发身份约束。
- 终审：**Resolved** — `docs/auth.md:130` 现在明确“以上内容”仅指 Web 邮箱密码登录，并准确指向 `docs/auth.md:189` 的微信/小程序并发身份约束，前后范围不再矛盾。

### 复核结论

首轮第 1-9 条及新增第 12 条均已解决，或有明确、可复核的不采纳理由；未发现未处理意见，也未发现新的并发、事务或账号归并错误。正式 Review 满足“review → 修改/回复 → 复核”要求，可以关闭并进入后续提交/PR 流程。
