# 线上流量防护与安全策略

最后核对：2026-10-12（北京时间）。本文记录 poemwiki.org 当前已经实施的策略及后续变更方法；控制台与实际加载配置是运行状态的依据，调整策略后应同步更新本文。

目标是减少不必要的源站出口流量，同时保留公开诗歌阅读、小程序和主要搜索引擎访问。UA、Referer、单次访问及 `/index.php` 前缀都不能独立证明请求恶意。

## 接入链路与职责

```text
访客 → Cloudflare 权威 DNS（DNS only）→ 阿里云 ESA → Nginx → Laravel
```

- Cloudflare 为根域名提供 DNS；`poemwiki.org` 的 CNAME 指向 `poemwiki.org.a1.initaj.com`，使用灰云 DNS only，不叠加 Cloudflare 代理。
- ESA 站点 ID：`1104710873844320`，目前使用基础版。浏览器 HTTPS 连接由 ESA 边缘证书承接。DNS 切换和证书部署已完成，不需要为新增 WAF 规则再次修改 DNS。
- UA/IP 拦截及 JS 挑战在 ESA 执行，命中后可避免请求进入源站。应用权限、robots.txt 和日志各自承担不同职责，不能代替边缘拦截。
- 阿里云源站公网按 GB 计费；衡量节省效果要看源站实际出口字节与账单，而不是 request log 文件大小或仅看请求数。

## ESA 自定义规则

入口：[poemwiki.org → 安全防护 → WAF](https://esa.console.aliyun.com/site/1104710873844320/safe/waf) → 自定义规则。当前使用 6/10 条，以下规则均已启用、永久生效；顺序按表排列，直接拦截优先于挑战。

| 顺序 | 规则名称与 ID | 条件 | 动作 |
| --- | --- | --- | --- |
| 1 | `Block-DataForSeoBot` / `23494068` | UA 不区分大小写包含 `dataforseobot` | 默认拦截页面，403 |
| 2 | `Block-known-abusive-IPs` / `23494069` | 真实客户端 IP 属于下列五个单独地址 | 默认拦截页面，403 |
| 3 | `Block-specified-crawlers` / `23495456` | UA 不区分大小写包含 `dotbot`、`reflectionbot`、`petalbot`、`semrushbot` 任意一项 | 默认拦截页面，403 |
| 4 | `Challenge-Mac-Chrome-read-pages` / `23494140` | 无 Referer、固定格式 Mac Chrome UA，全站匹配 | JS 挑战 |
| 5 | `Challenge-pages-without-Referer` / `23495478` | poemwiki.org 网页无 Referer，且不属于本规则的 UA 或路径豁免 | JS 挑战 |
| 6 | `bingbot` / `23495588` | UA 不区分大小写包含 `bingbot`，全站匹配 | JS 挑战 |

第 6 条是在 2026-10-12 核对控制台时发现的现有规则，本次未修改；其表达式为 `(lower(http.user_agent) contains "bingbot")`。Bing 在第 5 条中的豁免仍保留，但不能跳过这条单独的 Bing 挑战，因此当前配置不能描述为“Bing 全站免挑战”。

五个已封禁 IP：

```text
20.219.185.206
104.208.69.113
34.52.150.124
34.124.129.64
35.214.36.191
```

IP 规则使用连接 ESA 的 `ip.src`，不使用客户端可伪造的 `http.x_forwarded_for`。这些地址来自已观察的扫描行为，逐个封禁，没有扩大到网段、ASN、地区或运营商；它们不是历史流量的大头。

### 已封禁 UA 的表达式

DataForSeoBot 单独保留原规则：

```text
(lower(http.user_agent) contains "dataforseobot")
```

后增四种 UA 合并为一条：

```text
(lower(http.user_agent) contains "dotbot" or lower(http.user_agent) contains "reflectionbot" or lower(http.user_agent) contains "petalbot" or lower(http.user_agent) contains "semrushbot")
```

这五种 UA 是用户明确批准的封禁范围。其他商业、AI 或搜索爬虫不自动加入黑名单；新增时先说明来源、流量占比和预期影响。UA 可以更换，封禁名称不等于可靠识别某家爬虫。

### Mac Chrome 无 Referer 全站挑战

无 Referer 同时覆盖请求头缺失与值为空两种情况。UA 必须以以下内容开头，并以 `.0.0.0 Safari/537.36` 结尾；条件未固定 Chrome 主版本：

```text
Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/
```

完整运行表达式：

```text
(((not exists(http.referer)) or (exists(http.referer) and len(http.referer) lt 1)) and starts_with(http.user_agent, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/") and ends_with(http.user_agent, ".0.0.0 Safari/537.36"))
```

2026-10-12 按用户要求去掉全部 URI 条件；规则名称保留历史值 `Challenge-Mac-Chrome-read-pages`，不再表示只覆盖阅读页。只要上述 UA 特征与空 Referer 同时满足，首页、登录、作者、比较、贡献、编辑、接口、静态文件及历史 `/index.php` 入口均在匹配范围；没有路径、方法或单独的 API 豁免。

这也是合法浏览器可能发送的 UA，因此采用 JS 挑战。真实 Mac 用户直接打开链接也可能需要验证；不执行挑战 JavaScript 的接口客户端若使用相同 UA 且没有 Referer，会取得挑战 HTML，无法正常使用 API。带小程序/搜索引擎后缀的 UA 通常不满足本规则的严格结尾条件，这不等于建立了全局可信白名单。非空 Referer 不触发本规则，但 Referer 可被伪造。已持有有效挑战凭据的客户端仍按 ESA 的验证有效期处理，不能把匹配范围扩大理解为每次访问都重新挑战。

2026-10-12 00:05 的 12 项无 Cookie 公网 GET 检查通过：符合特征且缺失 Referer 的首页、登录、旧登录入口、作者、诗歌、robots.txt 和只读 API，以及空 Referer 的登录请求，均收到 JS 挑战 HTML；相同 UA 带 Referer 的登录、其他 UA 的首页、Googlebot 的 robots.txt、带小程序后缀的只读 API 正常返回。检查未自动执行挑战 JavaScript，也未测试登录提交或其他写入请求。

### 通用网页无 Referer 挑战

2026-10-12 00:27（北京时间）将原 `Challenge-poem-pages-without-Referer` 扩展为所有网页，并改名为 `Challenge-pages-without-Referer`；ID、顺序、JS 挑战动作和永久启用状态保留。匹配 Host `poemwiki.org`，去掉 `/p/` 和 `/index.php/p/` 的路径限制。因此首页、登录、作者、贡献、比较、编辑、搜索、日历及历史 `/index.php` 页面入口均在范围内，不限 Mac Chrome UA。

仅此规则豁免以下 UA 子串，均不区分大小写：`googlebot`、`bingbot`、`baiduspider`、`sogou`、`miniprogram`。前四项用于保留 Google、Bing、百度和搜狗抓取；最后一项用于小程序 WebView。小程序原生请求通常有 `servicewechat.com` Referer，已有 Referer 时不会触发本规则。

```text
(http.host eq "poemwiki.org" and ((not exists(http.referer)) or len(http.referer) lt 1) and not lower(http.user_agent) contains "googlebot" and not lower(http.user_agent) contains "bingbot" and not lower(http.user_agent) contains "baiduspider" and not lower(http.user_agent) contains "sogou" and not lower(http.user_agent) contains "miniprogram")
```

`not exists(...) or len(...) lt 1` 覆盖缺失与空值；不只使用 `http.referer eq ""`。控制台的“不包含”语法是 `not lower(...) contains ...`，对整组条件写 `not (...)` 曾被生成器拒绝。将所有接口和资源排除条件塞进单条规则时报 `Phase.HttpCustom.Match.Criteria.Length.OverQuota`；`not ali.static_resource` 保存时报 `Ali.StaticResource.NotSupport`。因此使用上面的通用规则，再用两条白名单规则仅跳过它；没有启用“跳过全部规则”。

豁免只是 UA 特征判断，未验证搜索引擎 IP 身份，也不是“跳过所有安全规则”的全局白名单。例如 UA 同时含 `Googlebot` 与 `DotBot`，仍由更早的封禁规则返回 403；Bing 还受到第 6 条独立挑战规则影响。未列出的搜索服务可能被挑战；需要保留时应先核查实际 UA 和身份，再调整条件，不能把包含任意 `bot` 的请求全部放行。

#### 非网页请求的定向豁免

入口：WAF → 白名单规则。当前使用 2/2 条；两条均已启用，操作均选择“部分规则种类或 ID”，规则种类留空，**规则 ID 只填写 `23495478`**。它们不会跳过已封禁 UA/IP、Mac 挑战、独立 Bing 挑战或其他 WAF 防护。

第一条 `Exclude-non-page-routes-from-Referer-challenge` / `23495633`：

```text
(http.host eq "poemwiki.org" and (http.request.uri.path contains "/api/" or http.request.uri.path contains "/calendar/q/" or http.request.uri.path contains "/poem-card/" or http.request.uri.path contains "/q/nation/" or http.request.uri.path contains "/q/author/" or http.request.uri.path contains "/q/poem/" or http.request.uri.path contains "/captcha/"))
```

这些是 API、日历数据、分享图片、搜索数据和验证码图片的路由段；`http.request.uri.path` 不包含查询参数，并同时覆盖对应 `/index.php/...` 入口。例如 `/login?next=/api/v1/test` 不会得到豁免。`/q`、`/q/{keyword}`、`/query` 和 `/calendar` 返回网页，仍应挑战。URI 路径字段已在这条白名单实际保存通过，不能把白名单和自定义规则的可用匹配项混为一谈。

第二条 `Exclude-static-paths-from-Referer-challenge` / `23495637`：

```text
(starts_with(http.request.uri, "/css/") or starts_with(http.request.uri, "/js/") or starts_with(http.request.uri, "/fonts/") or starts_with(http.request.uri, "/icon/") or starts_with(http.request.uri, "/images/") or starts_with(http.request.uri, "/media/") or starts_with(http.request.uri, "/vendor/") or starts_with(http.request.uri, "/storage/") or starts_with(http.request.uri, "/robots.txt") or starts_with(http.request.uri, "/favicon.ico"))
```

这条使用现有静态目录和文件前缀，带版本查询参数的资源也能匹配。没有按 URI 中包含 `.css`、`/api/` 等字符串全局放行，也没有按任意文件后缀放行业务页面。静态目录不应承载业务网页；新增资源目录或无扩展名的数据路由时，需要核对实际用途并同步更新豁免。仓库关闭了 Passport 默认 OAuth 路由，当前登录回调仍走 `/login`，未增加整个登录路径豁免。

豁免仅针对本条通用挑战。例如严格 Mac UA + 空 Referer 的 API 请求仍会由第 4 条挑战，这是之前已批准的全站 Mac 规则行为，不能承诺所有 API 都免挑战。

普通用户从书签、地址栏或其他没有 Referer 的入口直接打开任何网页，都可能需要挑战，包括首页和登录页；正常浏览器通常自动完成，但部分 WebView 或关闭 JavaScript 的环境可能失败。Referer 可伪造，具备浏览器能力的采集程序也可能通过挑战，不能承诺挡住全部采集。

2026-10-12 00:28 起的 30 项串行、无 Cookie 公网 GET 检查中，28 项按预期通过：普通 Windows Chrome UA 缺失 Referer 的首页、登录、作者、贡献、比较、诗歌、搜索和日历页面及历史入口收到挑战；空 Referer 和带 API 查询参数的登录页也收到挑战。带 Referer 的登录页、Googlebot/百度 UA 的诗歌页正常返回；普通 UA 的新旧 API、搜索数据接口、小程序 UA 的 API、CSS、JS、图标、robots 和验证码图片正常返回。API 与静态文件的 DotBot + Googlebot 请求仍被 403 拦截，严格 Mac UA 的 API 仍被原规则挑战。

另两项未验证成功：`/calendar/q/1/1` 在 20 秒内未返回，未继续重试；无效分享图 `/poem-card/0` 返回源站 500，未收到挑战 HTML，但不能据此证明有效分享图正常。检查只读取响应，没有自动完成 ESA JS 挑战、识别验证码、登录或发起写入请求；模拟 UA 也不能证明真实客户端身份。

2026-10-11 22:50–22:51（Mac 规则扩大全站前）的 17 项无 Cookie 公网 GET 检查全部通过：缺失/空 Referer、旧入口、缺失 UA、普通微信浏览器收到挑战；四种搜索引擎 UA、小程序 WebView、小程序 Referer及普通非空 Referer 收到诗歌正文；首页、robots 和相同 Mac Chrome/150 的登录页正常返回；同时包含 `DotBot` 与 `Googlebot` 的请求仍返回 403。这是当时的验证结果，当前 Mac UA 登录页已按全站规则挑战，Bing 豁免也不能当作当前全站放行结果。搜索引擎和小程序验证使用模拟请求特征，没有证明实际客户端身份，也未自动执行挑战 JavaScript。

### 如何解读安全分析

- `最终执行防护动作 = JS 挑战`：ESA 返回的是验证页面；边缘 HTTP 200 不代表返回诗歌正文。
- 成功通过后默认 30 分钟内无需重复挑战；只看一次挑战记录不能证明该客户端以后都被拦截。
- `可能是真人` 是分类结果，不是完成验证或放行的凭据。
- `DYNAMIC`、边缘状态 200、源站状态 200、最终动作 `-`：该记录取得了源站响应，不能算作被拦截。
- `/index.php/login?ref=...` 的 `ref` 是业务查询参数，不是 HTTP `Referer` 请求头。2026-10-12 扩大范围前，登录路径不匹配旧 Mac 页面的限制；现在相同 Mac Chrome/150 UA 且无 Referer 的登录请求也命中全站挑战，是否有业务 `ref` 参数不影响判断。

## 源站真实 IP

2026-10-11 已分别确认写入、校验及平滑 reload，并用经过 ESA、直连和伪造标头的无登录 GET 验证两种日志得到真实出口 IP。

ESA 已开启：

1. 规则 → 转换规则 → 托管转换 → 添加真实客户端 IP 标头，名称 `ali-real-client-ip`。
2. 安全防护 → 源站防护；自动启用最新回源 IP 列表保持关闭。

Nginx 文件：

```text
/www/server/panel/vhost/nginx/extension/poemwiki.org/esa-realip.conf
```

站点配置通过该目录的 `*.conf` include 加载。文件中的 `set_real_ip_from` 只信任当次 ESA 官方导出的 149 条 IPv4、109 条 IPv6 回源 CIDR，然后设置：

```nginx
real_ip_header ali-real-client-ip;
real_ip_recursive off;
```

网段数量是当日快照，不能当作以后名单的固定校验值，也不能信任 `0.0.0.0/0` 或 `::/0`。更新时从本站 ESA 源站防护页面导出当时完整名单，校验 CIDR、重复项和变更范围；先按审批流程更新 Nginx 信任名单，再启用新的 ESA 回源名单，避免日志重新记录代理地址。

PHP FastCGI 已使用 `REMOTE_ADDR $remote_addr`，Laravel 日志和原有按 IP 限流因此读取 Nginx 还原的访客 IP，无需把所有 Laravel 代理设为可信。原有源站 `block-crawlers.conf` 保留五个 IP 与 DataForSeoBot 拦截。

源站 HTTP/HTTPS 直连入口尚未限制为只允许 ESA 回源；用户已要求暂不处理。ESA 的源站防护开关不会自动等同于本机防火墙或安全组封闭入口。后续任务不能把收紧源站入口当成已获授权的步骤。

## 应用与爬虫约定

- `/poems/contribution/{fakeId}`、`/compare/{ids}` 在本仓库通过统一 Web `auth` 中间件要求登录；游客 HTML 跳登录，JSON 返回 401，比较数量和其他方法不能绕过。实现与验证见 [登录与权限](auth.md)。仓库状态不代表后续版本已经部署，排查时仍需核对生产版本。
- `public/robots.txt` 已为贡献、比较、登录、创建、验证码和查询路径补齐对应 `/index.php/...` 禁抓项；公开诗歌与作者页面仍允许抓取。robots 是自愿约定，不能阻止忽略规则的请求。
- 生成链接及 canonical 去掉 `/index.php`，旧入口继续兼容；不要因为历史链接带前缀就认定恶意或全站封禁。
- 请求日志格式、解析与 `REQUEST_LOG_DAYS` 保留期限见 [请求日志与爬虫排查](request-logging.md)。不要把缩短字段带来的日志体积下降当作流量下降。

## 后续 IP 封禁与 WAF 变更流程

### 生产日志分析的资源边界

只读统计也会占用内存、CPU 和磁盘 I/O，不能因为没有改配置就认为安全。2026-10-11 的故障中，未限制资源的日志统计任务与内存使用率升至 98.35%、磁盘读带宽饱和及网站失去响应在时间上吻合，是主要嫌疑；缺少故障进程快照，尚未证明内核 OOM 或唯一原因。

后续分析先取定额、限速的小片段在本地统计。必须在源站聚合时，应事先设置内存、CPU 时间、总运行时间、读取总量与读取速率的边界，保留服务所需的可用内存；启动前检查可用内存及 I/O 压力。只设 `nice` 或 `ionice` 不能代替这些边界。

使用固定数量的分钟/类别桶，或限制明细键数量；不要按每个 UA、IP、完整路径维护无上限字典，更不能为每个重叠窗口重复保存全量明细。最终输出 Top N 不代表中间统计内存已经受到限制。单次读取失败或机器开始卡顿时停止采集，不并发重试。

记录远端任务 PID；取消任务后必须确认远端进程已结束。本地 SSH 连接退出不能当作远端进程退出的证明。无法确认时不继续启动统计任务。历史资源证据不足时保留不确定性，不在生产机重放可能导致卡死的脚本来验证猜测。

### 规则变更步骤

1. **只读核查当前状态。** 先阅读本文，核对 ESA 规则、顺序、白名单及生产真实 IP 配置。记录时间范围、时区、来源路径、UA、Referer、频率、状态码与响应字节；结合多个信号判断，不只取 IP 请求数排行榜。
2. **准备具体可审阅的规则。** 说明条件、动作、豁免、预计节省和误伤可能；检查普通直接访问、搜索引擎、小程序、接口、资源及历史入口。没有先前授权时先确认。按 IP 封禁默认只操作已审阅的单个地址，不自动扩大到 CIDR/ASN 或运营商。
3. **区分批准与实施。** 500 IP 证据名单尚未获准应用；本地全站 Mac 403 草案也未批准写入。不要将草案、历史建议或审查文档当作现行配置。线上 Nginx 文件修改前要先给出完整差异让用户确认；只写入并运行 `nginx -t` 也不包含 reload 授权。reload/restart 必须再单独确认。
4. **备份和验证。** 保存原表达式、动作、顺序、启用状态及配置文件，避免覆盖他人修改。ESA 保存后重新打开核对，再以少量无登录公网 GET 验证实际响应；不能仅凭“保存成功”认定生效。默认不携带已有 Cookie，不自动完成验证码；保留时效内的验证 Cookie 会使挑战测试失真。
5. **比较实际效果。** 选择变更完成后的完整窗口与前一天同一时段，统一时区并避开发布窗口；分别计算请求数、成功正文、源站发送字节以及 ESA 挑战/拦截/回源量。源站 access log 当前未覆盖部分静态资源，应用正文大小也是压缩前数值，最终支出以阿里云出口账单为准。没有基线时不能承诺降到原来的某个百分比。
6. **回滚并同步本文。** ESA 可停用本次新规则或恢复原表达式/顺序，保留原有规则。若仅回滚通用网页扩展，恢复 `23495478` 原有 `/p/` 或 `/index.php/p/` 条件及历史名称，再停用本次两条定向豁免；不要在通用挑战仍启用时先撤销接口/资源豁免。停用通用规则后，Mac 规则仍按当前全站 UA + 空 Referer 范围挑战；若仅回滚 Mac 的全站扩展，应恢复其原诗歌/贡献 URI 条件。源站回滚也先校验配置，再经单独确认 reload；不能只删除被其他配置引用的变量定义。记录每次实际生效时间与验证结果。

源站校验与平滑加载命令如下，执行前仍适用上述两次确认：

```bash
/www/server/nginx/sbin/nginx -t
/www/server/nginx/sbin/nginx -s reload
```

无 Cookie 的挑战检查示例；只读 GET 不自动执行页面内 JavaScript：

```bash
curl --compressed --max-time 20 -sS \
  -A 'PoemWiki-Security-Verification' \
  -o /tmp/poemwiki-security-probe.html -w '%{http_code}\n' \
  'https://poemwiki.org/p/MjQ5NTExNzU3NTczOTYzNzM=?esa_policy_probe=manual'
```

需要同时核对正文是否为挑战页面；HTTP 200 本身不是通过测试的标准。用新 Cookie/UA/IP 排查也不能据此认定原日志中的客户端没有通过过挑战。

## 独立渲染服务的 HTTPS 入口

2026-11-11（北京时间），需求 r15 的 `render.poemwiki.com` 已配置 Cloudflare 代理 DNS，并新增仅匹配该 Host 的 `render-https-origin` 配置规则，使用 Strict HTTPS 回源。`poemwiki.com` 全站原有 SSL 模式及其他域名规则未修改；本节不改变上述 `poemwiki.org` 的 ESA 策略。

用户已明确确认公开该域名的图片生成接口且不加认证。没有新增公网 18080 入站规则；渲染容器端口仅绑定服务器内网。公网健康接口、目标提交号及 PNG 生成均已验证。旧渲染服务保留至用户验收；入口依赖和恢复清单保存在本机私有部署记录中。

## 官方参考

- [ESA 自定义规则与 JS 挑战语义](https://help.aliyun.com/zh/edge-security-acceleration/esa/user-guide/waf-custom-rules/)
- [ESA 白名单：只跳过指定规则 ID](https://help.aliyun.com/zh/edge-security-acceleration/esa/user-guide/whitelist-rules)
- [ESA 匹配字段：URI 包含查询参数、Referer 与客户端 IP](https://help.aliyun.com/zh/edge-security-acceleration/esa/user-guide/phase-list)
- [ESA 托管转换](https://help.aliyun.com/zh/edge-security-acceleration/esa/user-guide/managed-conversion)
- [ESA 源站防护与名单更新](https://help.aliyun.com/zh/edge-security-acceleration/esa/user-guide/origin-protection)
- [Nginx realip 信任来源与专用标头](https://nginx.org/en/docs/http/ngx_http_realip_module.html)
