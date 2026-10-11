# 免费流量控制与页面鉴权审查

日期：2026-10-11。本轮范围：`PoemController`、`Authenticate`、比较数量配置、贡献与比较入口模板、`docs/auth.md`、`PoemPagesAccessTest`，以及 `/private/tmp/poemwiki-traffic-2026-10-11/` 的 Nginx 本地草案。工作区已有 Campaign、环境变量、todo、迁移等无关修改不在本轮范围。

Nginx 草案尚未应用线上。此审查不替代用户对线上写入和后续 reload 的分别确认，也不把历史流量覆盖比例等同于计费流量节省比例。

审查以单一权限边界和可解释的请求处理为原则，关注 SSOT、DRY、可回滚配置和真实 HTTP 结果；不要求引入新的封禁服务、配置框架或前端 E2E。以下意见按风险重要性排序；检查通过项属于明确复核的风险，不是为了凑数提出的缺陷。

## 1. P2：直接打开受限页面的微信用户会丢失登录返回地址

位置：`PoemController` 的鉴权变更、`Authenticate::redirectTo()`、`LoginWechatController::login()`、`routes/web.php`。

新鉴权把直接访问贡献与比较页的游客跳转到不带 `ref` 的 `/login`。普通邮箱登录的框架 trait 会读取 `url.intended`；微信浏览器却选择 `LoginWechatController`，其成功分支只读取查询参数 `ref`，缺失时返回根路径。页面内 `urlOrLoginRef` 可保留目标，但无法覆盖分享链接或地址栏直接打开。原比较三首以上的分支显式传过 `ref`，删除该分支后此流程也发生变化。

建议在这两个受限入口的登录跳转中保留目标，或让微信登录使用已保存的 intended 地址。选择最小影响范围；用微信 UA 的真实受限页请求断言返回参数或目标，避免只验证源码字符串。无需为此泛改其他登录渠道。

- Coding agent 回复：已修复。项目实际绑定 Brackets\\AdminAuth\\Exceptions\\Handler，会忽略 AuthenticationException 的自定义 redirectTo；只改 redirectTo 无效，测试曾明确失败。因此在 Authenticate::handle 中复用框架鉴权，仅截获这两个页面的非 JSON 未登录异常，返回带 ref 的登录跳转，其他路由和 JSON 原样抛出。ref 用 getPathInfo 保持无 index.php 前缀并保留查询参数。新增微信直接访问测试，连同旧前缀用例通过。
- 复核：通过，缺陷已处理。读取实际 Brackets Handler 确认其自行选定 route('login')，忽略异常中的 redirectTo；最终实现先调用 parent::handle，只有两个命名路由的 HTML 鉴权异常转换为 guest 跳转，JSON 和其他路由重抛原异常，没有另一套认证判断或全局 Handler 变更。guest 保留 intended；ref 从真实路径信息及查询构造，不采信请求 Host。新增微信 UA、查询参数和 front controller 的 HTTP 结果测试均包含在最终通过的套件内。微信 OAuth 外部交互未重做，返回行为依据现有 LoginWechatController 对 ref 的读取和已验证的跳转边界。

## 2. 上线前确认：Mac Chrome 与空 Referer 组合存在真人误伤

位置：`mac-chrome.http.conf`、`block-mac-chrome.server.conf`、草案 README。

规则精确匹配完整的 Macintosh / Intel Mac OS X 10_15_7 / Chrome 数字.0.0.0 / Safari 537.36 UA，并要求 Referer 为空；不接受 Edge 或机器人后缀。该 UA 也是普通 Chrome 的合法输出；直接打开链接、书签和关闭 Referer 的真人可以命中。规则作用于全站，包括已登录用户和 `/p`，不是只作用于贡献与比较页；伪造 Referer 则可绕过。

README 已明确这些边界。用户选择直接拒绝时，这是可以审阅和回滚的策略取舍，而非可以认定恶意的身份信号。线上写入前必须确认包含“全站、空 Referer、可能误伤”的具体范围，不能宣称百分之百爬虫或绝不影响正常用户。搜索引擎的带标识 UA 不匹配此完整模式，但这不等于所有真人或搜索服务都得到身份验证。

- Coding agent 回复：采纳边界说明，草案保留全站、空 Referer、完整固定格式 UA 组合。会把真人直接访问可能被拒绝和 Referer 可伪造的取舍写入最终说明及审批问题；该审批仅阻止线上写入，当前本地代码可提交。没有宣称 UA 可以确认恶意身份。
- 复核：本地草案边界通过，线上仍待具体策略批准。完整匹配和空 Referer 条件与 README 一致，真人误伤和可绕过性没有被隐藏；不把“固定 UA”解释为“已确认身份”。本地权限代码可提交，但不能将该结论作为全站 Mac 规则已获得线上写入或 reload 授权。

## 3. 检查通过并需核实来源：500 个地址有独立证据，geo 的可信性取决于现有 realip 配置

位置：`suspect-ips.geo`、`suspect-ips-evidence.csv`、`suspect-ips.http.conf`。

独立解析确认配置和 CSV 恰好包含相同的 500 个有效且不重复的 IP；每条证据均达到至少 12 个 Chrome 主版本，贡献/比较请求数与无 Referer 请求数一致。合计 114,227 次贡献/比较请求，其中 113,306 次带 `/index.php` 前缀。名单使用准确地址，没有扩大到网段、ASN 或运营商。共享出口仍可能存在真人，七天复核只是运维建议，Nginx 不会自动过期。

`geo` 默认以 `$remote_addr` 查表，不直接读取 X-Forwarded-For。若现有 Nginx 的 realip 配置允许不可信连接改写 `$remote_addr`，仅此写法不足以证明“连接来源”。应用前应核实当前 http/server 的 `real_ip_header` 与 `set_real_ip_from`；有可信代理时需明确识别的地址含义。这个检查不要求修改应用的 TrustProxies。

- Coding agent 回复：已只读核实线上实际加载配置：/www/server/nginx/sbin/nginx -T 返回成功，全量已加载文件中没有 set_real_ip_from、real_ip_header 或 real_ip_recursive 指令。现状 $remote_addr 为连接地址。CSV 和配置各 500 条，全是有效公网地址；仍只给用户审阅，暂无线上应用。
- 复核：通过。复用 coding agent 成功读取线上 nginx -T 的结果：已加载文件无 realip 指令；与当前 geo 默认以 $remote_addr 查表的实现一致。此前独立确认的 500 条 CSV/config 一致性仍成立。配置保持精确地址和手动复核期限，没有升级为 CIDR/ASN 封禁。以后引入 CDN 或代理时需要重新核实来源地址；这不阻止当前草案审阅。

## 4. 检查通过：登录权限集中在控制器，比较的其他方法不会绕过

位置：`PoemController::__construct()`、`routes/web.php`、`PoemPagesAccessTest`。

从统一 `auth` 中间件的公开例外中移除贡献与比较方法，使一首、两首、三首等比较都在查询诗歌前经过同一鉴权边界。`Route::any('/compare/{ids}')` 的 POST 等方法仍调用同一控制器方法，不因 URL 或 HTTP 方法有第二套游客规则。合法 `/index.php` 入口解析后仍由该路由调用同一控制器；未识别的前缀返回路由错误，不会获得另一条公开方法。诗歌阅读和随机跳转继续属于原公开例外。

测试直接断言未登录 HTML 跳登录、JSON 返回 401、POST JSON 不可绕过，并以真实登录用户访问贡献与比较页面；没有按源码中必须存在某个方法或字符串编写测试。可补真实 SCRIPT_NAME 下的前缀入口权限验证，以使旧链接边界的证据更直接。

- Coding agent 回复：已增加真正的 SCRIPT_NAME=/index.php、PHP_SELF 与 SCRIPT_FILENAME 入口测试，贡献页及比较一、二、三首都只能跳登录；干净路径的 HTML、JSON 与 POST 同样覆盖。公开 /p 以真实诗歌请求返回 200。权限仍只有既有 auth 一处来源。
- 复核：通过。最终测试已加入实际 SCRIPT_FILENAME、SCRIPT_NAME 和 PHP_SELF 的前缀请求，四类受限目标均跳登录，不能通过旧入口恢复游客权限。controller 的统一 auth 保持为权限来源；新增 middleware 只决定未登录响应，不重复认证。公开阅读与已登录访问的真实成功结果也已覆盖。

## 5. 检查通过并需实际验证：Nginx 在 PHP 之前返回小响应，不能只凭文本认定生效

位置：两组 http 与 server 草案。

map 和 geo 定义分别处于 http 上下文，server 仅使用 `if` 加 `return`，未使用不支持的嵌套 if 或把地图定义塞入站点 server。规则以头部和来源地址判定，不依赖被 Laravel rewrite 改写的 `$uri`，因此无需复制 clean 和 `/index.php` 两套路径规则。命中时直接返回 `Forbidden\n`，避免应用及自定义 HTML 错误页带来的响应成本。

本地独立语法检查在默认沙箱内被 Nginx 的 sysctl/默认日志访问权限挡住，未得到成功的 nginx -t 结果；不能把此环境失败写为配置错误或验证通过。应使用允许的本地 Nginx 完成语法和匹配/不匹配 HTTP 实测；线上写入获批后再用线上实际版本运行 nginx -t，reload 后复查小响应及原封禁规则。

- Coding agent 回复：本机解除沙箱后已通过 nginx -t，并启动仅监听 127.0.0.1 的临时 Nginx 实测：完整 UA 命中、带 Referer 放行、Edge/机器人后缀/非冻结版本/其他系统不命中、干净与前缀路径、500 IP 逐个拒绝、well-known 不能绕过、旧五 IP 与 DataForSeoBot 仍拒绝；新增拒绝响应正文确为 10 字节。测试进程已停止。线上 nginx -T 通过现有配置验证；草案尚未写入，线上版本的最终 nginx -t 和 reload 后验证仍须在用户审批后执行。
- 复核：本地验证通过，线上验证仍待审批。复用报告的 nginx -t 和 HTTP 实测结果，并读取临时 verify/nginx.conf 确认加载的是草案文件；其中仅对 loopback 信任 X-Test-Client-IP 来逐条模拟来源地址，生产 geo 草案未加入此测试头或 realip 配置。完整 UA、Referer、后缀、非冻结版本、两种路径、500 地址、well-known 和旧规则的行为已验证。10 字节仅是拒绝响应正文；不包含响应头或连接开销。临时验证不能替代获批写入后的线上 nginx -t 和 reload 后验证。

## 6. 检查通过：UA 与 IP 规则能够独立安装和回滚

位置：草案 README 和文件划分。

两组规则使用不同变量、不同 http 文件和不同 server 文件；IP 列表使用 `.geo` 扩展名，不进入现有 `*.conf` glob。README 给出了每个文件的安装路径、上下文和分别启用流程，保留已有五个 IP 和 DataForSeoBot 规则。

回滚说明要求先停用引用变量的 server 文件，再停用定义变量的 http 文件，并重新 nginx -t；避免只删变量定义造成启动失败。未 reload 时旧 worker 仍使用旧配置；验证通过也不能自行绕过用户的 reload 确认。静态清单和少量 include 符合当前需求，无需引入动态封禁框架。

- Coding agent 回复：同意。两个 HTTP/server 配置组分开，geo 地址文件不匹配 *.conf glob，回滚说明及两阶段用户确认均保留。不会覆盖 block-crawlers.conf，也不主动 reload。
- 复核：通过。两组变量和文件独立，README 的加载上下文和回滚顺序与最终草案一致；已有 block-crawlers.conf 不需重写。线上写入与 reload 的两次确认仍保留，未用本地测试结果越过这两个步骤。

## 7. 检查通过并需完成测试：真实 HTTP 与数据库边界是适当验证层级

位置：`PoemPagesAccessTest`。

新测试覆盖登录拦截及真实用户可访问，构造真实诗歌和译诗，不用同值 mock 或常量断言替代权限结果；事务避免测试数据残留。贡献/比较属于权限和数据库访问边界，使用集成测试适当，无需再复制一套 UI 内容测试。`/p` 在未登录时返回 200 的验证确保阅读未被一起关闭。

独立执行 `PoemPagesAccessTest|PublicUrlTest|RequestLoggingTest` 时，旧 URL 与日志测试的 13 个用例、45 个断言通过。10 个新权限用例在开始数据库事务时因沙箱禁止 MySQL 连接而出错，尚未进入业务断言；需要在获准访问本地测试数据库的环境完成。此结果不支持声称新权限测试通过，也不表明权限实现有缺陷。

- Coding agent 回复：最终解除沙箱执行 ./vendor/bin/phpunit --filter 'PoemPagesAccessTest|PublicUrlTest|RequestLoggingTest' tests/Feature：28 tests / 75 assertions 全通过，含 15 个新权限用例；使用 .env.testing 指定的测试数据库，DatabaseTransactions 回滚。已运行 PHP CS Fixer 和 git diff --check；无额外 E2E 或源码断言测试。
- 复核：通过。复用最终解除沙箱后 28 tests / 75 assertions 全通过的结果，其中新权限类为 15 个用例；复核最终测试源码，覆盖游客 HTML/JSON、POST、旧前缀、微信目标查询、公开阅读和登录访问，没有源码匹配、固定配置值断言或同值 mock。事务测试边界适当；本轮没有代码再变动，按要求不重复执行相同套件。格式化和 diff 检查也已完成。

## 8. 检查通过：移除无效游客配置，复用现有登录入口，无无关范围扩张

位置：`PoemController::compare()`、`config/app.php`、两处模板及 `docs/auth.md`。

所有比较都要求登录后，旧的游客最多两首分支和 `max_compare_poem_count_guest` 不再有执行意义。检索应用和路由未发现其他调用，删除它们避免保留相互冲突的权限来源；登录用户数量上限保持原有配置。模板使用既有 `urlOrLoginRef`，没有复制 Auth 判断或另建 URL 拼接规则；登录表单已经保留 ref 查询参数。

修改范围小，未添加与规则值绑定的测试，未引入新的硬编码流量阈值、兜底路径和解释“做什么”的注释。Nginx 中的 12 个版本只用于生成待审清单的调查标准，不是应用业务配置。文档区分鉴权跳转与 Nginx 拒绝，避免把磁盘日志减少当作带宽或账单减少。

- Coding agent 回复：同意，废弃游客配置与分支已删，登录入口复用既有 helper。config/app.php 中 formatter 顺带产生的无关对齐已还原。会仅显式暂存本轮访问权限、模板、测试与说明，不暂存原有 Campaign/.env/todo/migration 更改。
- 复核：通过。最终 diff 中 config 仅删除废弃游客项，模板复用原有 helper；新增 Authenticate 的两行注释说明实际 Handler 和微信渠道的原因，属必要的“为什么”。未改变其他认证渠道、比较上限、robots 或既有无关工作区文件。提交时仍须显式选本轮文件；此审查不授权提交 Campaign、环境变量、todo 或迁移修改。

## 最终结论

八条意见均已回复并逐条复核，第 1 条 P2 已修复，其余本地代码和草案未发现新增阻断问题。“review → 修改/回复 → 复核”闭环完成，本轮本地应用代码可按用户要求提交。

流量测算文档使用精确完整 UA 规则及去重后的历史响应体，原覆盖比例约 50.73% / 52.68%；扣除短拒绝与登录跳转正文后的估计约 49.35% / 51.81%。文档明确其依赖访问数量稳定和贡献/比较请求大多未登录的假设，未计入跟随跳转的额外响应，也未将其承诺为阿里云账单同比下降。该测算不增加封禁范围。

线上 Mac 规则及 500 IP 名单仍处于待确认草案状态；本轮审查不表示已经部署应用、写入 Nginx 或 reload。第 2 条所述真人误伤需在具体配置的审批中说明。写入获批后先检查线上实际配置，随后按用户要求另行确认 reload 并验证效果。
