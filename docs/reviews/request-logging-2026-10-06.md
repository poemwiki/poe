# 请求日志精简与爬虫规则审查

审查日期：2026-10-06。范围限定于本次请求日志、robots、配置、测试和说明文档变更，不包含工作区其他已有修改。全部检查均在本地完成，未连接 SSH 或修改生产环境。

审查方法：读取实际 `brackets/advanced-logger` 事件、同步任务、服务构造及 Laravel LogManager、Monolog RotatingFileHandler 的实现；从依赖边界验证行为，优先保持简单、明确的数据流，避免自建重复基础设施。独立运行 `./vendor/bin/phpunit tests/Feature/RequestLoggingTest.php`：6 tests、24 assertions 通过。

以下按重要程度排列；“检查通过”是审查结论，不是待修复缺陷。每项保留 coding agent 回复和复核位置。

## 1. P2：保留期限输入需要校验，避免误填导致无限保留

位置：`config/logging.php` 的 `requests.days`。

当前 `(int) env('REQUEST_LOG_DAYS', 14)` 会把非数字字符串转换为 `0`；真实 Monolog 将 `0` 解释为无限保留，正好重新引入本次要解决的磁盘增长问题。负数也未被拒绝，Monolog 会直接将其用于 `array_slice`，不具备文档所说的非负整数语义。建议在配置入口接受合法非负整数，其余输入明确报错，让发布阶段可以发现。无需为“默认必须等于 14”写常量测试。

- Coding agent 回复：采纳。配置入口使用 FILTER_VALIDATE_INT 验证非负整数并拒绝布尔值；误填时抛出明确异常。已通过实际应用 bootstrap 分别验证 -1、invalid、1.5、true 均被拒绝；未增加固定默认值测试。
- 复核：通过。配置入口已拒绝布尔及非法非负整数，强制转换仅在校验后执行。复核独立启动真实应用确认 -1、invalid、1.5、true 报错，0 和 30 分别保留正确数值。误填不会再静默变成无限保留。

## 2. P2：五个 IP 的带宽节省百分比不能超过现有证据

位置：`docs/request-logging.md` 的“2026-10-06 排查参考”。

文档称五个 IP 响应只有 KB 量级，并推断封禁节省不到 0.1%。本次可见排查结论没有给出这五个 IP 在相同时间范围内全部响应字节之和；部分 IP 还是今天才观察到，不能仅凭昨天/今天的少量探测样本推出完整比例。若无已有精确统计证据，建议删去 KB 和 0.1% 的具体数值，写清扫描不是已观察到的主要来源，无法可靠估计封禁全部流量的百分比。无需为此再连接线上机器。

- Coding agent 回复：采纳证据边界意见。文档补充上轮已获得的四 IP 扫描路径响应总计 104,324 字节及其同日占比约 0.0034%；第五 IP 的 20,276 字节属于次日，单独列出。删除封整 IP 小于 0.1% 的结论，明确没有全部路径统计，不能推导精确节省比例或上界。未再连接 SSH。
- 复核：通过。最新文档只将 104,324 字节与 10 月 5 日约 3.09 GB 比较（约 0.0034%），次日样本单独说明；已明确不能据此计算封整个 IP 的比例或上界。复核只验证算术与结论边界，原始线上样本采用 coding agent 提供的既有排查记录，未重新采集。

## 3. P3：日志 schema 测试应比较键集合，不绑定 JSON 字段顺序

位置：`tests/Feature/RequestLoggingTest.php` 的诊断字段测试。

最初版本用 `assertSame([...], array_keys($record))` 固定字段顺序；JSON 对象的键顺序不构成本功能的对外要求，重排构造数组不应导致失败。建议保留严格字段集合约束以防敏感字段混入，但使用无序比较。审查期间已看到主 agent 将其改为 `assertEqualsCanonicalizing`，等待统一复核。

- Coding agent 回复：采纳。已改为 assertEqualsCanonicalizing 比较字段集合，不依赖 JSON 键顺序；仍约束日志只输出允许的诊断字段。
- 复核：通过。最新测试使用 assertEqualsCanonicalizing 比较键集合，不再依赖字段顺序；独立重跑测试通过。

## 4. 检查通过：替换服务确实绕开会修改应用日志的旧构造链

位置：`AppServiceProvider::register()`、`app/Logging/RequestLogger.php`。

真实链路是 `RequestHandled → RequestLoggerListenerHandler → RequestLogJob::handle() → app(RequestLoggerService::class)`，容器绑定会落到新服务。新服务不构造旧 logger，因此不会触发旧依赖中的 `popHandler/pushHandler`。独立 `requests` 通道没有应用日志的 tap，不会重复附加 WebProcessor、UserRequestProcessor、调用位置。应用错误前后写入测试证明目标和 context 保持正确。这里薄适配比重写监听器、事件订阅和完整轮转逻辑更简单。

- Coding agent 回复：认同。保留薄服务适配及独立通道，继续沿用现有事件触发和排除路径逻辑；应用错误日志前后目标与 context 的集成测试通过。
- 复核：通过。最新绑定、独立通道与服务仍保持原有薄适配；集成测试继续证明请求写入不影响前后应用错误日志。

## 5. 检查通过：保留诊断字段且避免敏感参数与换行注入

位置：`RequestLogger::log()`、`logging.channels.requests.formatter_with`。

IP 使用 Laravel 的客户端解析；UA 保留完整值；URL 去掉查询；Referer 仅保留 host；没有记录表单、Cookie 或 Authorization。真实 LineFormatter 将 context 编码成 JSON，带 CR/LF 的 UA 测试仍只有一行，且能解码回原始 UA。`response_bytes` 对常规响应计正文，对流式响应使用 null，且不会执行流回调。文档明确这不是 Nginx/云计费字节，避免排查时混用统计口径。

- Coding agent 回复：认同。保留完整 UA 和 IP、去查询的 URL、referer_host；不加入 payload，流式响应的未知大小明确为 null。单行 JSON 注入边界测试通过。
- 复核：通过。最新字段和 formatter 维持单行 JSON 及敏感参数排除，流式响应未执行回调；对应实际落盘测试通过。

## 6. 检查通过：轮转、清理范围和配置生效说明符合依赖实现

位置：`config/logging.php`、`config/advanced-logger.php`、`docs/request-logging.md`。

Laravel daily 通道确实把 `days` 传给 Monolog 的 maxFiles。首次写入新日期文件触发轮转；已有当天文件时不保证立即清理。仅匹配 `request-YYYY-MM-DD.log`，不会清理应用日志或压缩归档。文档明确日期文件数量而不是严格日历期限，并说明 config cache、常驻进程重载和同步 benchmark 的限制。移除旧 service 不再消费的 handler/file/format 配置，有助于保持配置的单一来源，没有加入多余兼容层。

- Coding agent 回复：认同。保留 Laravel/Monolog 的现有轮转实现，文档已说明含当前文件、新日期写入触发、归档和配置刷新，不引入清理 cron 或第二套保留策略。
- 复核：通过。配置与文档仍对应真实 Monolog 轮转语义，新增输入校验不改变合法值的保留策略；真实临时文件轮转测试通过。

## 7. 检查通过：robots 补丁与已有六项限制一一对应

位置：`public/robots.txt`。

原有六个禁抓前缀各有对应 `/index.php` 前缀，仍位于同一 `User-agent: *` 分组，未扩大为全站禁止抓取；公开诗歌和作者路由不在新增前缀范围内。静态 robots 的重复规则是协议文件表达别名所需，不值得为 DRY 引入运行时生成器。文档区分自愿遵守规则与访问控制，没有声称修复 robots 可拦截恶意客户端。

- Coding agent 回复：认同。只补六条 index.php 别名限制。已使用 Python RobotFileParser 实际验证 36 项禁抓和 12 项公开页面允许抓取的行为；不添加脆弱源码匹配测试。
- 复核：通过。最终 robots diff 仍仅新增六个对应别名前缀，没有新分组、全站禁止或 URL 重定向变更；接受已记录的解析器行为验证，无需重复源码断言。

## 8. 检查通过：测试边界、数字与说明注释适度

位置：新增测试与 `RequestLogger`。

测试使用真实事件、容器绑定、formatter、临时目录和轮转文件，没有 mock 被测实现、源码字符串检查或固定默认值断言；临时目录隔离使清理不会触碰真实日志。秒到毫秒的 `1000` 和小数位 `3` 是单位/精度表达，不需要额外抽象。构造说明解释为何绕开旧构造链，响应字节注释解释测量边界，同步配置注释解释进程状态依赖，均提供“为什么”。无需增加 UI/E2E 测试。

- Coding agent 回复：认同。测试继续限定真实 RequestHandled 到日志文件的公共边界，轮转操作只使用随机隔离临时目录。已运行仓库 PHP CS Fixer；保留解释为什么的少量注释。
- 复核：通过。测试仍为真实公共边界、独立临时目录与低耦合断言；最新格式化未引入额外业务改动。未发现新的过度设计、无意义注释或配置常量测试。

## 最终结论

全部 8 项已回复并逐项复核，3 项建议已采纳，未发现新增阻断问题。本次已完成“review → 修改/回复 → 复核”闭环。最终独立验证：6 tests、24 assertions 通过；真实应用配置加载验证非法输入拒绝及 0/30 正常接受。此结论仅针对上述本地变更，不代表线上部署、真实带宽下降或日志切换已经完成。
