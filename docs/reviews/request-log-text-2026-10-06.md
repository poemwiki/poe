# 请求日志固定列文本格式审查

日期：2026-10-06。审查范围：`RequestLogFormatter`、`RequestLogger` 的字段调整、requests 通道 formatter 配置、既有日志集成测试及 `docs/request-logging.md`。不包含工作区其他修改，未使用 SSH 或修改线上。

本轮检查以简单、可直接解释的数据流为准，关注字段单一来源、避免重复、避免过度设计、适量解释原因的注释和真实边界测试。未发现需要修复的缺陷；以下七项为检查通过意见，按风险重要性排序，已由 coding agent 逐项确认并完成复核。

## 1. 检查通过：攻击者可控字符串不会破坏单行结构

位置：`app/Logging/RequestLogFormatter.php`。

IP、请求行、UA、来源域名均经过同一 quote 方法，使用 JSON 字符串转义；引号、反斜杠、控制字符不会变成额外字段或实际换行。`JSON_INVALID_UTF8_SUBSTITUTE` 避免无效 UTF-8 令编码失败。直接 `sprintf` 渲染已转义字段，不经过 LineFormatter 的模板替换，因此 UA 中的 `%context%` 仅为原始字符串。继承的构造设置不参与此次 format，未引入隐藏的第二轮插值。

- Coding agent 回复：认同。保留集中字符串转义和直接渲染，确保可控 UA 不注入新行、列或模板；已用带换行、引号、反斜杠和 %context% 的真实请求验证。
- 复核：通过。回复与现有集中转义及直接渲染实现一致；初审已验证恶意字符串的可解析性与单行边界，代码未再变化，无新增意见。

## 2. 检查通过：路径保留 front controller 且不再记录站点域名和查询

位置：`RequestLogger::log()`。

`getBaseUrl() + getPathInfo()` 在真实 Symfony Request 中保留脚本入口前缀：直接路径与 `/index.php` 两种部署解析都适用。独立验证 `/index.php/login`、`/login`、`/sub/index.php/login`、`/poems/a%2Fb/` 四种路径，配置真实 SCRIPT_NAME/SCRIPT_FILENAME 后均保留完整路径，查询参数未进入记录；编码斜杠和末尾斜杠也被保留。没有额外保存相同 URL 或固定协议域名。

- Coding agent 回复：认同。使用 base URL 与 path info 的组合保留 index.php 和部署子目录，不通过去域名的字符串替换推测路径；既有测试已补真实脚本入口信息。
- 复核：通过。真实脚本入口信息已在最终测试中覆盖，与初审四种路径实测一致；路径不需要额外手工拼域名或字符串替换。

## 3. 检查通过：真实 Monolog formatter 契约与继承的批处理一致

位置：`RequestLogFormatter`、`config/logging.php`。

本项目实际依赖使用 Monolog 2 的数组 record，覆盖签名兼容 `LineFormatter::format(array): string`；Laravel 能按 formatter 类名实例化。读取真实依赖确认 `LineFormatter::formatBatch()` 会逐条调用子类 format 并拼接字符串。独立用四条记录调用 formatBatch，得到恰好四行，未出现数组输出、双重格式化或额外空行。无需为当前同步 daily 通道再实现一套批处理。

- Coding agent 回复：认同。沿用 Monolog 2 的 formatter 契约及已有批处理，不增加重复实现。
- 复核：通过。继承批处理与当前依赖契约已被实测确认，维持现有实现即可。

## 4. 检查通过：时间与诊断字段各有唯一来源

位置：`RequestLogger`、`RequestLogFormatter`。

移除 context 中的 time 后，使用 record datetime 输出带时区的时间；方法和路径合为一个带引号字段，状态、毫秒、正文大小各写一次。未知正文大小与缺失字符串统一以明确的 `-` 标记，合法零字节响应仍输出 `0`。字段名仅在程序内部维护，落盘为固定位置文本，符合用户此次精简要求。仍然不记录请求参数、Cookie、Authorization 或完整 Referer。

- Coding agent 回复：认同。使用日志 record 的单一时间来源，落盘不再有重复字段名和 URL 固定前缀；缺失值与合法零值分开表达。
- 复核：通过。单一 datetime 来源、固定列及缺失值表示符合最新代码和用户要求，没有重新引入重复元数据。

## 5. 检查通过：文档解析器可解析真实 formatter 输出

位置：`docs/request-logging.md`。

格式说明与样例均对应八个逻辑字段；来源和 UA 中的空格不会改变列边界。独立运行文档中的 Python 示例，输入为真实 formatBatch 输出，包含引号、反斜杠、CR/LF、Tab、中文、无效 UTF-8 替换字符及 `%context%`，成功统计全部四条记录且保留可解析的 UA。文档说明混合旧格式时只统计新格式，未把新文本称为 JSON 对象。缺失字段、耗时单位、响应大小与计费流量的区别说明一致。

- Coding agent 回复：认同。文档已替换 jq 示例为固定列解析，并实际执行；用户提供的同一条日志经真实 formatter 从 358 降到 247 字节，含换行减少 31%。
- 复核：通过。已执行的文档解析器与最终输出格式一致。回复中的 358→247 字节下降约 31.0% 算术正确，仅代表所测样例，不能直接外推所有请求日志的节省比例。

## 6. 检查通过：应用日志隔离、轮转和流式响应行为不变

位置：requests 通道及既有事件集成测试。

本轮仅替换请求通道 formatter 和数据提取；旧服务隔离、同步 RequestHandled 链路、保留设置及禁用/排除逻辑保持原状。独立运行 `./vendor/bin/phpunit tests/Feature/RequestLoggingTest.php`：6 tests、26 assertions 通过，涵盖应用错误前后仍写正确文件、轮转保留、流式正文不被消费以及禁用/排除行为。

- Coding agent 回复：认同。保留已验证的日志隔离、轮转配置和同步流程；6 条测试、26 个断言已通过。
- 复核：通过。复用初审后最新的 6 tests、26 assertions 验证结果；代码未变化，无需重复运行。

## 7. 检查通过：抽象、注释和测试粒度适度

位置：新 formatter 和既有测试调整。

formatter 仅负责固定列编码，logger 负责请求数据采集，没有混入解析、过滤、封禁或第二套日志系统。私有 quote 集中处理所有可变字符串，避免 DRY 问题；直接渲染的注释解释模板注入风险，属于必要的“为什么”。原有六条真实落盘测试被适配为稳定日志格式断言，没有为字段名删除写源码匹配或 mirror mock 测试。固定列顺序是本次明确的输出协议，测试该顺序合理；无需额外前端测试。

- Coding agent 回复：认同。此次仅增加一个小型格式化器并调整既有测试，未新增无关抽象、前端测试或封禁逻辑。
- 复核：通过。回复确认范围与实现一致；未发现额外抽象、测试耦合或扩大任务范围的问题。

## 最终结论

七项意见均已回复并逐项复核，通过，无新增阻断问题。本轮“review → 回复 → 复核”已完成；代码未变化，复用已通过的 6 tests、26 assertions，以及真实路径、formatBatch 和文档解析器验证结果。结论仅覆盖本地变更，不表示已部署线上。
