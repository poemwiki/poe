# 请求日志与爬虫排查

请求日志沿用 `storage/logs/request-YYYY-MM-DD.log`，每行是一条 JSON。请求记录通过 `RequestHandled` 事件写入独立的 `requests` 通道，不再替换应用错误日志的 handler，也不再重复附加 URL、IP、UA、请求参数和调用位置。

## 字段

| 字段 | 含义 |
| --- | --- |
| `time` | 带时区的记录时间；项目默认 UTC |
| `ip` | Laravel 根据可信代理配置解析的客户端 IP |
| `method` | 请求方法 |
| `url` | 协议、域名和原始路径，保留 `/index.php`，不含查询参数 |
| `status` | HTTP 响应状态码 |
| `duration_ms` | 原有 application benchmark 的处理耗时，单位毫秒 |
| `response_bytes` | 应用响应正文的字节数；流式或文件响应为 `null` |
| `ua` | 完整 User-Agent；特殊字符按 JSON 转义 |
| `referer_host` | Referer 的域名；没有时为 `null` |

不记录 Cookie、Authorization、GET/POST 参数或完整 Referer。这样仍能按 IP、UA、路径、频率、状态和响应大小排查采集与扫描，但无法从请求日志还原查询参数。应用异常日志仍使用原来的日志配置。

`response_bytes` 是应用压缩前的正文大小，不是计费出口流量。核算带宽应使用 Nginx 的实际发送字节及云厂商统计；当前服务器还有部分静态资源未记录 access log。

## 保留期限

在部署环境的 `.env` 中设置：

```dotenv
REQUEST_LOG_DAYS=14
```

默认保留最近 **14 个日期文件（包含当前文件）**。设为 `30` 可保留 30 个；`0` 表示无限保留。必须使用非负整数，负数或非整数字符串会在加载配置时明确报错，避免误填后静默变成无限保留。如果某些日期没有请求，文件数量不等于严格的日历天数。

部署代码或修改环境变量后，按现有发布流程刷新配置，例如：

```bash
php artisan config:cache
```

如果使用常驻应用进程，需要随发布重新加载进程。request log 保持同步写入，因为用于计算耗时的 benchmark 属于当前 HTTP 进程；不要把 `advanced-logger.request.queue` 改为异步队列。

清理由 Monolog 在轮转时触发，无需额外 cron。当天文件已存在时，修改设置不保证立即清理；通常下一次创建新日期文件时生效。超过数量的旧 `request-YYYY-MM-DD.log` 会被删除，部署前应归档需要长期保留的日志。此配置不会清理 `laravel.log`、Nginx access log 或压缩归档。

现有 `logging.channels.daily.days` 只控制 Laravel 的 `daily` 通道，不能控制此前由 advanced-logger 自建的 request handler。新配置将请求日志交给独立的 Laravel daily 通道，从而使保留设置实际生效。

## 查询示例

新格式可直接用 `jq` 分析。切换当天可能混有旧格式，以下命令跳过无法解析为 JSON 的旧记录，因此只统计新格式部分；完整对比应分别解析旧记录，或选择切换后的完整日期。

```bash
# Top client IPs.
jq -Rr 'fromjson? | .ip' storage/logs/request-2026-10-07.log | sort | uniq -c | sort -nr | head -20

# Top user agents.
jq -Rr 'fromjson? | .ua | @json' storage/logs/request-2026-10-07.log | sort | uniq -c | sort -nr | head -20

# Inspect one client without treating its UA as shell code.
jq -R 'fromjson? | select(.ip == "203.0.113.7")' storage/logs/request-2026-10-07.log
```

反向代理/CDN 接入变化时，需要同步核对 Nginx 和 Laravel 的可信代理配置，不能直接信任任意来源提供的转发 IP 请求头。

## 爬虫规则

`public/robots.txt` 为已有的登录、创建、验证码、贡献记录和查询禁抓规则补充对应的 `/index.php/...` 路径。公开诗歌和作者页仍允许抓取。本次不修改 URL 重定向，也不全站屏蔽 AI 或搜索爬虫。

robots.txt 是爬虫自愿遵守的规则，不是访问控制。忽略规则的分布式采集仍需结合访问行为设置 WAF 挑战或其他限制，不能仅凭 Mac Chrome UA 或单次访问就封禁。

## 2026-10-06 排查参考

10 月 5 日北京时间的 Nginx 日志中，550,331 次已记录请求发送正文约 3.09 GB。相似 Mac Chrome UA 的请求占约 54%，发送约 1.51 GB，来自约 25.2 万个 IP；这是需要重点处理的疑似分布式采集。

10 月 5 日，四个已列出的扫描 IP 在命中扫描路径的请求中，响应字节分别为：`20.219.185.206` 50,740、`104.208.69.113` 41,648、`34.52.150.124` 6,132、`34.124.129.64` 5,804；合计 104,324 字节，约占当天已记录响应的 **0.0034%**。第五个 IP `35.214.36.191` 在 10 月 6 日已观察到的扫描路径响应为 20,276 字节，不能混入前一天的分母。

这些数字只覆盖已识别的扫描路径，未汇总这五个 IP 的所有请求，因此不能据此给出封禁整个 IP 的精确节省比例或上界。从已观察行为看，它们不是主要流量来源，不能期待封禁后节省几成带宽。

如果限制的是 DataForSeoBot、ClaudeBot、GPTBot、DotBot 四类高流量爬虫，按同日已记录响应计算，合计约 373 MB，约占 12%。这是历史请求不再发生时的响应正文节省估算；不含未记录静态资源、协议开销，也不保证未来 IP 或抓取频率不变。搜索/AI 爬虫的取舍应单独决定，不应一概归为漏洞攻击。
