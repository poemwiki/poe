# 请求日志与爬虫排查

请求日志沿用 `storage/logs/request-YYYY-MM-DD.log`，每行是一条固定顺序的文本，不重复写字段名、协议和站点域名。请求记录通过 `RequestHandled` 事件写入独立的 `requests` 通道，应用错误日志继续使用原配置。

## 格式

```text
[时间] "IP" "方法 路径" 状态码 耗时毫秒 正文字节数 "UA" "来源域名"
```

例如：

```text
[2026-10-06T09:29:35+00:00] "43.138.19.222" "GET /poems/contribution/MTA0NDY4MzYzNjM1NjEzMjU=" 200 279.383 4787 "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36" "poemwiki.org"
```

| 顺序 | 含义 |
| --- | --- |
| 1 | 带时区的记录时间；项目默认 UTC |
| 2 | Laravel 根据可信代理配置解析的客户端 IP |
| 3 | 请求方法和原始路径，保留 `/index.php`，不含协议、站点域名、查询参数 |
| 4 | HTTP 响应状态码 |
| 5 | 原有 application benchmark 的处理耗时，单位毫秒 |
| 6 | 应用响应正文的字节数；流式或文件响应为 `-` |
| 7 | 完整 User-Agent |
| 8 | Referer 的域名；没有时为 `"-"` |

可变字符串使用双引号包围，并按 JSON 字符串规则转义引号、反斜杠和控制字符，例如换行写为 `\n`。每条记录始终只占一行，UA 中的空格和竖线不会混入其他字段。缺失的 IP、UA 或来源域名写为 `"-"`。格式化器直接写入各字段，不会将 UA 中的 `%context%` 等文本解释为模板。这里使用的是字符串转义规则，日志本身不再是 JSON 对象。

不记录 Cookie、Authorization、GET/POST 参数或完整 Referer。这样仍能按 IP、UA、路径、频率、状态和响应大小排查采集与扫描，但无法从请求日志还原查询参数。应用异常日志仍使用原来的日志配置。

第 6 列是应用压缩前的正文大小，不是计费出口流量。核算带宽应使用 Nginx 的实际发送字节及云厂商统计；当前服务器还有部分静态资源未记录 access log。

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

下面用 Python 标准库解析固定列并统计 IP、UA。切换当天可能混有旧文本和 JSON，示例只统计匹配新格式的记录；完整对比应分别解析旧格式，或选择切换后的完整日期。

```bash
python3 - storage/logs/request-2026-10-07.log <<'PY'
import collections
import json
import re
import sys

quoted = r'"(?:[^"\\]|\\.)*"'
pattern = re.compile(
    r'^\[([^\]]+)\] (' + quoted + r') (' + quoted +
    r') (\d{3}) ([\d.]+) (\d+|-) (' + quoted + r') (' + quoted + r')$'
)
ips, agents = collections.Counter(), collections.Counter()
with open(sys.argv[1], encoding='utf-8') as logs:
    for line in logs:
        match = pattern.fullmatch(line.rstrip('\n'))
        if match:
            ips[json.loads(match[2])] += 1
            agents[json.loads(match[7])] += 1
for label, counts in [('IP', ips), ('UA', agents)]:
    print(label)
    for value, count in counts.most_common(20):
        print(count, json.dumps(value, ensure_ascii=False))
PY
```

反向代理/CDN 接入变化时，需要同步核对 Nginx 和 Laravel 的可信代理配置，不能直接信任任意来源提供的转发 IP 请求头。

## 爬虫规则

`public/robots.txt` 为已有的登录、创建、验证码、贡献记录和查询禁抓规则补充对应的 `/index.php/...` 路径。公开诗歌和作者页仍允许抓取。本次不修改 URL 重定向，也不全站屏蔽 AI 或搜索爬虫。

页面链接生成时会移除入口文件 `/index.php`，canonical 也会规范化已有的带前缀地址。访问旧地址仍能打开页面，但生成的链接使用无前缀路径；请求日志继续保留原始路径，便于分析重复地址访问。

robots.txt 是爬虫自愿遵守的规则，不是访问控制。忽略规则的分布式采集仍需结合访问行为设置 WAF 挑战或其他限制，不能仅凭 Mac Chrome UA 或单次访问就封禁。

## 2026-10-06 排查参考

10 月 5 日北京时间的 Nginx 日志中，550,331 次已记录请求发送正文约 3.09 GB。相似 Mac Chrome UA 的请求占约 54%，发送约 1.51 GB，来自约 25.2 万个 IP；这是需要重点处理的疑似分布式采集。

10 月 5 日，四个已列出的扫描 IP 在命中扫描路径的请求中，响应字节分别为：`20.219.185.206` 50,740、`104.208.69.113` 41,648、`34.52.150.124` 6,132、`34.124.129.64` 5,804；合计 104,324 字节，约占当天已记录响应的 **0.0034%**。第五个 IP `35.214.36.191` 在 10 月 6 日已观察到的扫描路径响应为 20,276 字节，不能混入前一天的分母。

这些数字只覆盖已识别的扫描路径，未汇总这五个 IP 的所有请求，因此不能据此给出封禁整个 IP 的精确节省比例或上界。从已观察行为看，它们不是主要流量来源，不能期待封禁后节省几成带宽。

如果限制的是 DataForSeoBot、ClaudeBot、GPTBot、DotBot 四类高流量爬虫，按同日已记录响应计算，合计约 373 MB，约占 12%。这是历史请求不再发生时的响应正文节省估算；不含未记录静态资源、协议开销，也不保证未来 IP 或抓取频率不变。搜索/AI 爬虫的取舍应单独决定，不应一概归为漏洞攻击。
