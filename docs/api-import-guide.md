# 数据导入接口使用指南（面向 AI Agent / 开发者）

适用接口（全部基于前缀 `/api/v1`）：
- 用户登录：`POST /user/login`
- 作者搜索：`POST /author/search`
- 作者导入：`POST /author/import` （代码中对应 `AuthorAPIController@importSimple`）
- 诗歌检索（仅支持 poem-select 模式）：`POST /poem/q` （`PoemAPIController@query`）
- 诗歌批量导入：`ANY /poem/import` （推荐使用 `POST` 且 `Content-Type: application/json`）

本文面向需要自动化批量向 PoemWiki 导入（或同步）作者与诗歌数据的智能体（AI Agent）或脚本开发者，介绍端到端流程、字段、校验、去重策略与错误处理。

> 快速使用提示：建议先在 shell 中设置环境变量，后续所有 curl 可直接复制：
> 
> ```bash
> WIKI_API_BASE="https://example.com/api/v1"   # 替换为实际域名
> EMAIL="user@example.com"
> PASS="secret"
> TOKEN=$(curl -s -X POST "$WIKI_API_BASE/user/login" -H 'Content-Type: application/json' \
>   -d '{"email":"'"$EMAIL"'","password":"'"$PASS"'"}' | jq -r '.data.access_token')
> echo "Token: $TOKEN"
> ```
> 需要 jq，如无请安装或改为手动复制 token。

---
## 1. 总览流程
1. `POST /user/login` 获取访问令牌（Passport personal access token，`token_type=Bearer`）。
2. 对每个待导入作者：
   1. 先用 `POST /author/search` 按名称探测是否已存在；
   2. 若不存在或需要 Wikidata 映射：调用 `POST /author/import` 以最小必要字段创建 / 关联；
   3. 处理返回状态：`created | existed | ambiguous`。
3. 为每个作者的诗歌集合准备批量 payload：
  - 可选：使用 `POST /poem/q` (poem-select) 以标题 / 句子片段检索潜在重复。
   - 过滤掉明显重复者。
4. 聚合 ≤200 条诗歌为一批，`POST /poem/import`。
5. 解析返回数组：成功元素为 URL，失败元素为 `{ errors: {...} }`。
6. 必要时再次用 `POST /poem/q` 验证入库情况或做后续链路（如翻译关联）。

---
## 2. 认证与请求头
登录成功后返回：
```json
{
  "data": {
    "access_token": "<token>",
    "token_type": "Bearer",
    "issued_at": "2025-08-21T09:30:00Z",
    "expires_in": 31536000,
    "expires_at": "2026-08-21T09:30:00Z"
  },
  "success": true
}
```
后续所有受保护导入接口（作者导入 / 诗歌导入）需在 Header 中携带：
```
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

重复登录会自动吊销旧 token（同名 `openapi`），可安全刷新。

---
## 3. 接口详解
### 3.1 POST /user/login
请求 JSON：
```json
{ "email": "user@example.com", "password": "******" }
```
校验：`email` 和 `password` 均必填字符串。失败返回 422；频繁失败触发 429（内置限流）。

示例 curl：
```bash
curl -X POST "$WIKI_API_BASE/user/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"'$EMAIL'","password":"'$PASS'"}'
```

### 3.2 POST /author/search
用途：根据关键字（含别名、名称映射）在本库中搜索作者。
请求 JSON：
```json
{ "keyword": "李白" }
```
可选 query param：`limit`（默认 10）。
成功返回 `data.authors` 数组，元素包含：`id,label,wikidata_id,avatar_url,desc,source`。若 `keyword` 为空返回错误（`success=false`）。

示例 curl：
```bash
curl -X POST "$WIKI_API_BASE/author/search" \
  -H 'Content-Type: application/json' \
  -d '{"keyword":"李白"}'
```

### 3.3 POST /author/import
控制器：`AuthorAPIController@importSimple`
请求 JSON 允许字段：
- `name` (string, 1-50, 必填)：作者名称
- `describe` (string, ≤2000，可选)：简介（将写入 `describe_lang` 的指定 locale）
- `describe_locale` (string, ≤10，可选，默认系统 locale)
- `wikidata_id` (integer，可选)：提供则尝试以 wikidata 导入或绑定

返回：
- `status`: `created` | `existed` | `ambiguous`
- `author`: { id,label,label_cn,label_en,wikidata_id,url,avatar_url } （`ambiguous` 时无 `author` 而是 `candidates`）
- `candidates` (当 `status=ambiguous`)：带 `id,label,wikidata_id,poem_count,score` 的候选数组。

判定逻辑摘要：
1. 传 `wikidata_id`：若库中已存在同 wikidata => `existed`；否则若本地有 Wikidata 详细记录 => 走仓储导入；没有则创建最小记录。
2. 仅传 `name`：标准化（去点/空格/大小写）后搜索别名与名称；无匹配 => 新建；单匹配 => `existed`；多匹配 => 评分（精确匹配 +50, 有 wikidata +20, 诗歌量上限 +30），若第一比分第二高 ≥25 => 视为 `existed`，否则 `ambiguous`。

客户端策略：
- 遇到 `ambiguous` 需人工 / 智能二次裁决（可提示用户选择或再检索 Wikidata 确认）。

示例 curl（带 wikidata 导入）：
```bash
curl -X POST "$WIKI_API_BASE/author/import" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"Li Bai","wikidata_id":12345,"describe":"Tang dynasty poet"}'
```
示例 curl（仅名称）：
```bash
curl -X POST "$WIKI_API_BASE/author/import" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"李白"}'
```

### 3.4 POST /poem/q 
控制器：`PoemAPIController@query`
说明：当前对外仅开放 `poem-select` 用途（检索诗歌用于导入前查重）。作者搜索请使用 `POST /author/search`，不要依赖 `/poem/q` 返回作者结果。

请求 JSON：
```json
{ "keyword": "静夜思", "mode": "poem-select" }
```
查询参数（可选）：`mode=poem-select`（如不传也视为诗歌检索，但请统一显式带上以便脚本稳定）。

返回 `data.poems` 元素关键字段：`id,title,poet_label,poem (截取段),poet_contains_keyword,poem_contains_keyword,translator_label`。
使用建议：
- 用于导入前重复检测：比对 `poem_contains_keyword` 或对正文片段做二次相似度。

示例 curl（poem-select）：
```bash
curl -X POST "$WIKI_API_BASE/poem/q?mode=poem-select" \
  -H 'Content-Type: application/json' \
  -d '{"keyword":"举头望明月"}'
```

### 3.5 POST /poem/import
控制器：`PoemAPIController@import`
请求头：必须 `Content-Type: application/json`。
请求 JSON：
```json
{
  "poems": [
    {
      "title": "静夜思",
      "poet": "李白",
      "poem": "床前明月光\n疑是地上霜\n举头望明月\n低头思故乡",
      "from": "唐诗三百首",
      "language_id": 1
    }
  ]
}
```
限制：一次最多 200 条。逐条独立校验/插入，返回数组与传入顺序对齐：
- 成功：对应元素为诗歌 URL（字符串）
- 失败：对应元素为 `{ "errors": { field: [msg...] } }`

服务端追加/覆盖字段（客户端无需提供也不应自设）：
- `original_id` 固定 0
- `is_owner_uploaded` 设为 `Poem::$OWNER['none']`
- `upload_user_id` = 当前登录用户
- `flag` = `Poem::$FLAG['botContentNeedConfirm']` （标记机器人待确认）

校验规则：
- `title`: required|string|max:255
- `poet`: required|string|max:255
- `poem`: required|string|min:10|max:65500 且通过 `NoDuplicatedPoem` 规则（语义去重检测）
- `from`: nullable|string|max:255
- `language_id`: required 且在 `LanguageRepository::idsInUse()` 返回集合内

重复判定：`NoDuplicatedPoem` 触发时会记录日志并返回错误；客户端可捕获并决定跳过或重试（例如微调换行 / 标点）。

示例 curl（单批导入）：
```bash
curl -X POST "$WIKI_API_BASE/poem/import" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"poems":[{"title":"静夜思","poet":"李白","poem":"床前明月光\n疑是地上霜\n举头望明月\n低头思故乡","language_id":1}]}'
```

### 3.6 POST /poem/detect
用途：根据提供文本自动检测语言，返回可直接用于 `poem/import` 的 `language_id`（如果系统支持且已启用）。

请求 JSON：
```json
{ "text": "床前明月光，疑是地上霜。" }
```
返回（示例）：
```json
{ "data": { "language_id": 1 }, "success": true }
```
若无法识别或未映射到启用语言：
```json
{ "data": { "language_id": null }, "success": true }
```
错误：
```json
{ "success": false, "message": "<error>" }
```

兼容性说明：早期版本曾直接返回裸整数或 null；现统一包装。旧客户端若仍假设裸值，请升级为读取 `data.language_id`。

使用建议：
- 不确定来源语言时先调用；
- 返回 null：回退默认语言（如 zh-CN）或拼接更多上下文重试；
- 批量性能：抽取前几行 + 中间一行组合检测；
- 可在本地做缓存（同一文本 hash -> language_id）。

示例 curl（检测单段）：
```bash
curl -X POST "$WIKI_API_BASE/poem/detect" \
  -H 'Authorization: Bearer $TOKEN' -H 'Content-Type: application/json' \
  -d '{"text":"床前明月光，疑是地上霜。"}'
```
示例（流水线：检测 -> 导入）：
```bash
LANG_ID=$(curl -s -X POST "$WIKI_API_BASE/poem/detect" \
  -H 'Authorization: Bearer $TOKEN' -H 'Content-Type: application/json' \
  -d '{"text":"床前明月光，疑是地上霜。"}' | jq -r '.data.language_id')
curl -X POST "$WIKI_API_BASE/poem/import" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"poems":[{"title":"静夜思","poet":"李白","poem":"床前明月光\n疑是地上霜\n举头望明月\n低头思故乡","language_id":'"${LANG_ID:-1}"'}]}'
```

---
## 4. 典型导入工作流（伪代码）
```pseudo
login() -> token
for author_raw in source_authors:
  res = search_author(author_raw.name)
  if not found:
     import_res = import_author({...})
     if import_res.status == 'ambiguous':
        resolve via heuristics / human
     author_id = pick(import_res)
  else:
     author_id = matched.id

  poems_batch = []
  for poem_raw in author_raw.poems:
     if maybe_duplicate(poem_raw):
        check = poem_query(sample_line(poem_raw))
        if high_similarity(check.poems): continue
     poems_batch.append(normalize(poem_raw))

     if len(poems_batch) == 200:
        submit(poems_batch); poems_batch = []

  if poems_batch: submit(poems_batch)
```

关键策略：
- `maybe_duplicate` 可基于行数 <5 且长度短的诗优先查重。
- `sample_line` 取中间或第一行 5~10 个词作为 `keyword`。

---
## 5. 去重与匹配策略说明
### 作者
- 名称标准化：移除点 / 间隔符，合并多空格并转小写。
- 候选评分：精确匹配 (50) + 是否有 wikidata (20) + 作品数量 capped(30)。领先 ≥25 判定唯一。
### 诗歌
- 规则 `NoDuplicatedPoem`：服务器端文本级别查重（具体实现在规则类中，未在此文展开）。
- 建议客户端再做：
  1. 行/句长度归一（去多余空白）
  2. 与已收录样本做指纹（如 simhash）预过滤，减少失败请求。

---
## 6. Curl 示例
### 登录
```bash
curl -X POST https://example.com/api/v1/user/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"secret"}'
```
### 作者导入（带 wikidata）
```bash
curl -X POST https://example.com/api/v1/author/import \
  -H 'Authorization: Bearer <TOKEN>' -H 'Content-Type: application/json' \
  -d '{"name":"Li Bai","wikidata_id":12345,"describe":"Tang dynasty poet"}'
```
### 诗歌批量导入
```bash
curl -X POST https://example.com/api/v1/poem/import \
  -H 'Authorization: Bearer <TOKEN>' -H 'Content-Type: application/json' \
  -d '{"poems":[{"title":"静夜思","poet":"李白","poem":"床前明月光\n疑是地上霜\n举头望明月\n低头思故乡","language_id":1}]}'
```
### 查重（诗句，仅 poem-select）
```bash
curl -X POST https://example.com/api/v1/poem/q?mode=poem-select \
  -H 'Content-Type: application/json' \
  -d '{"keyword":"床前明月光", "mode": "poem-select"}'
```

---
## 7. 错误与状态处理
| 场景 | 表现 | 处理建议 |
|------|------|----------|
| 登录 422 | `{ message: "用户名或密码错误" }` | 校验凭证或回退人工 |
| 登录 429 | 过多尝试 | 指数退避重试 |
| 作者导入 ambiguous | 返回 candidates | 选高分或再拉 wikidata 数据 disambiguation |
| 作者导入 invalid name | `invalid name` | 清洗输入（去掉纯符号/数字） |
| 诗歌导入重复 | `errors.poem` 包含重复提示 | 记录映射，跳过后续同文 |
| 诗歌导入超过 200 | 返回 `Limit 200 poems per request` | 分批 |
| Content-Type 错误 | `Request content type must be application/json` | 修正头 |
| language_id 不在集合 | `errors.language_id` | 先获取有效语言列表（需阅读仓库 `LanguageRepository::idsInUse()`） |

响应统一封装：成功 `success=true`，错误 `success=false`（伴随 message / errors）。

---
## 8. 最佳实践
1. 批量分块：100~200/批权衡延迟与失败重试。
2. 幂等处理：客户端维护 `source_poem_id -> imported_url` 映射，避免重复发送。
3. 失败分类重试：网络/超时与校验错误分离；校验错误不重试。
4. 速率控制：适当 sleep（如 5~10 RPS）保护服务；遇 429 backoff。
5. 结构化日志：记录请求 payload hash 与响应，便于追踪重复过滤策略效果。
6. 预清洗：统一换行 `\n`，去 BOM，修正全角空格，便于服务器判重。
7. 语言检测：使用 `/poem/detect` 预填 `language_id`，再做人工/模型校验（空结果时回退默认语言或重试）。
8. 异常恢复：批处理失败时从首个失败索引继续；保持输入序列化（可保存 checkpoint JSON）。
9. 歧义作者先收集再人工确认，避免误绑定导致后续大量诗歌错归属。
10. 安全：妥善保管 Bearer Token，最小权限账号运行批量导入。

---
## 9. 附：字段快速参考
| 字段 | 位置 | 说明 |
|------|------|------|
| name | author/import | 作者名称（多语言初始只写默认 locale） |
| wikidata_id | author/import | Wikidata 实体 ID，可触发自动富化 |
| title | poem/import | 诗歌标题 |
| poet | poem/import | 作者名（字符串，会在后续人工/系统归并到 author_id） |
| poem | poem/import | 正文（\n 分行） |
| from | poem/import | 来源或出处，可选 |
| language_id | poem/import | 语言 ID，需在有效列表中 |

---
## 10. 进一步扩展（可选）
- 当需要建立翻译/原文关系，可在导入完成后使用相关关系接口（当前未公开，需要参考 `Relatable` 模型及仓储方法扩展）。
- 可为 AI Agent 增加“作者歧义判定”模型，自动选择最高置信候选；如得分差 <25 触发人工审阅。

---
## 结语
以上文档覆盖从登录、作者识别到诗歌批量导入的核心路径。AI Agent 可据此实现高可靠、可追踪的自动同步流程。若内部模型或仓储逻辑更新（如去重算法升级），需同步调整客户端判重策略。
