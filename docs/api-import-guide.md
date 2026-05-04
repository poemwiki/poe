API Documentation：[PoemWiki Open API Documentation](https://api-doc.poemwiki.org) (password: poemwiki)  

这个文档站点由 Apifox 生成，包含 Testing 和 Production 两个环境，建议先在 Testing 环境测试你的导入脚本，再切换到 Production 环境。

适用接口（全部基于前缀 `/api/v1`）：

- 用户登录：`POST /user/login`
- 作者搜索：`POST /author/search`
- 作者导入：`POST /author/import` （暂不对外公开，因为导入诗歌的时候可以根据 wikidata QID 自动创建作者）
- 作者更新：`POST /author/update/{id}`
- 诗歌检索（仅支持 poem-select 模式）：`POST /poem/q` （`PoemAPIController@query`）
- 诗歌批量导入：`POST /poem/import` 

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
>
> 需要 jq，如无请安装或改为手动复制 token。

---

## 1. 基础流程

0. 在 Testing/Production 环境的登录页面注册一个邮箱账号。
1. `POST /user/login` 获取访问令牌（Passport personal access token，`token_type=Bearer`）。
2. （可选）对来源作者执行 `POST /author/search`：
  - 若返回匹配（单一且可信）则记录其 author id 或 wikidata_id 用作后续导入诗歌时的 `poet_id` 或 translator id；
  - 若没有搜索到匹配的作者：使用 /author/import（暂未开放）创建一个新的作者，或在 [poemwiki.org](https://poemwiki.org/author/create) 手动创建这个作者。
3. 为每批诗歌构建 payload：
  - 标题、正文、语言；
  - `poet_id` 可为 数值类型的 author id 或 字符串 `Q<wikidata_id>`（自动解析/创建）；
  - `translator_ids` 同样支持 `Q<wikidata_id>` 自动创建。
  - 可选：先用 `POST /poem/q?mode=poem-select` 通过关键句片段做重复探测，过滤明显重复。
4. 聚合 ≤200 条为一批调用 `POST /poem/import`。
5. 解析响应数组：成功元素为 URL；失败元素为 `{ errors: {...} }`，按 payload 的索引定位。
6. （可选）再次用 `POST /poem/q` 验证抽样入库情况。

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

> 状态：暂不公开外部使用（internal / restricted）。
>
> 原因：
>
> 1. 已支持在 `/poem/import` 中通过 `poet_id: "Q<wikidata_id>"` / `translator_ids` 自动创建缺失作者，常规导入无需显式调用；
> 2. 避免脚本滥用导致短时间大量重复的“空壳作者”占位；
> 3. 后续可能补充更丰富的校验（别名冲突、来源可信度评分）后再考虑开放。
>
> 仍保留此文档，便于：
> - 内部或受信任 Agent 在需要补充作者描述 / 手动歧义裁决时使用；
> - 了解自动创建逻辑所依赖的最小字段结构。
控制器：`AuthorAPIController@importSimple`
请求 JSON 支持的公开字段见第 9 节“字段快速参考”中位置为 `author/import` 的条目。

返回：

- `status`: `created` | `existed` | `ambiguous`
- `author`: { id,label,label_cn,label_en,wikidata_id,url,avatar_url } （`ambiguous` 时无 `author` 而是 `candidates`）
- `candidates` (当 `status=ambiguous`)：带 `id,label,wikidata_id,poem_count,score` 的候选数组。

校验与行为说明：

- `describe_locale` 只允许 `zh-CN` 和 `en`
- 未提交 `describe_locale` 时，默认按 `zh-CN` 写入简介

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

### 3.4 POST /author/update/{id}

用途：更新一位已存在作者的基础信息。当前接口主要用于补充或修正作者名称、简介和生日字段。

控制器：`AuthorAPIController@update`

路径参数：

- `id`: 已存在的 author id

请求头：需要认证，建议 `Content-Type: application/json`。

请求 JSON：支持部分字段更新；未提交字段通常保持原值。可用请求字段见第 9 节“字段快速参考”中位置为 `author/update` 的条目。

示例：

```json
{
  "name": {"zh-CN": "李白", "en": "Li Bai"},
  "desc": {"zh-CN": "唐代诗人"},
  "birth": "0701-02",
  "birth_fields": "month"
}
```

校验与行为说明：

- 需要登录认证
- `id` 找不到对应 author 时，返回失败响应
- `name` 会直接写入 `author.name_lang`
- `desc` 会直接写入 `author.describe_lang`
- `birth_fields` 支持 `year`、`month`、`day`
- `birth_fields = year` 时，`birth` 应为年份数字，例如 `0701`
- `birth_fields = month` 时，`birth` 应为 `YYYY-MM`，例如 `0701-02`
- `birth_fields = day` 时，`birth` 应为 `YYYY-MM-DD`，例如 `0701-02-28`
- 当前接口不会清空未提交的生日分量；它只会按 `birth_fields` 覆盖对应的 `birth_year` / `birth_month` / `birth_day`

成功返回：

```json
{
  "success": true,
  "data": {
    "id": 123
  }
}
```

示例 curl：

```bash
curl -X POST "$WIKI_API_BASE/author/update/123" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":{"zh-CN":"李白"},"desc":{"zh-CN":"唐代诗人"},"birth":"0701","birth_fields":"year"}'
```

### 3.5 POST /poem/q

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

### 3.6 POST /poem/import

控制器：`PoemAPIController@import`
请求头：必须 `Content-Type: application/json`。
请求 JSON：顶层为 `poems` 数组；每个 poem 对象支持的公开请求字段见第 9 节“字段快速参考”中位置为 `poem/import` 的条目。`is_owner_uploaded`、`upload_user_id`、`flag` 这类内部字段由服务端写入，调用方不需要也不应传入。

示例：

```json
{
  "poems": [
    {
      "title": "静夜思",
      "poet": "李白",
      "poet_id": 123, // 可选：已知作者 id，则可直接绑定；仍建议同时带 poet 便于人工校验
      "poem": "床前明月光\n疑是地上霜\n举头望明月\n低头思故乡",
      "original_id": 321, // 可选：若导入的是译作，可指定已存在 poem.id 作为原作
      "from": "唐诗三百首",
      "language_id": 1,
      "genre_id": 13, // 可选：体裁（参见 10.2 Genres 表）
      "translator_ids": [456, "张三", "Q789"] // 可选：数组；元素含义见下文
    }
  ]
}
```

限制：一次最多 200 条。逐条独立校验/插入，返回数组与传入顺序对齐：

- 成功：对应元素为 `{ "id": number, "url": string }`
- 失败：对应元素为 `{ "errors": { field: [msg...] } }`

服务端追加/覆盖字段（客户端无需提供也不应自设）：

- `is_owner_uploaded` 设为 `Poem::$OWNER['none']`
- `upload_user_id` = 当前登录用户
- `flag` = `Poem::$FLAG['botContentNeedConfirm']` （标记机器人待确认）

`original_id` 处理规则：

- 未提供时默认写入 `0`
- 提供时必须是已存在的 `poem.id`
- 提供后该条诗歌会按译作处理，写入 `original_id`，并将 `is_original` 设为 `0`

校验规则：

- `title`: required|string|max:255
- `poet`: required_without:poet_id|string|max:255 （提供 poet_id 时可不填，但推荐保留 poet 原始名称供比对）
- `poet_id`: nullable|integer|exists:author,id （现在额外支持以字符串 `Q<wikidata_id>` 形式传递 Wikidata QID，服务端会在自动创建/解析为对应的 author，把 `Q<wikidata_id>` 替换为 对应的 author id）
- `poet_cn`: nullable|string
- `poem`: required|string|min:10|max:65500 且通过 `NoDuplicatedPoem`
- `original_id`: nullable|integer|exists:poem,id
- `from`: nullable|string|max:255
- `language_id`: required 且在 `LanguageRepository::idsInUse()` 集合内
- `genre_id`: nullable|integer|exists:genre,id （参见附录 10.2）
- `subtitle`: nullable|string|max:128
- `preface`: nullable|string|max:10000
- `year`: nullable|string
- `month`: nullable|string
- `date`: nullable|string
- `location`: nullable|string
- `translator_ids`: nullable|array （元素允许：现有作者数值 ID；任意非空字符串作为译者名；`Q<wikidata_id>` Wikidata ID——支持自动创建对应的 author）

`translator_ids` 解析逻辑（已更新：支持按 Wikidata 自动创建缺失译者）：

1. `Q123`：若存在 `wikidata_id=123` 的作者 => 使用其 author id；若不存在 => 自动根据 wikidata 的数据创建一个 Author，再把这个新建的 author 作为这个 poem 关联的 translator。
2. 纯数字：视为已存在的作者 id（若不存在会触发校验失败）。
3. 其他非空字符串：作为自由文本译者名（建立 Entry 关联，不创建 Author）。
   最终顺序写入 `poem.translator`（JSON 数组），并建立 `relatable` 关系；存在 id 的译者建立 Author 关系，自由文本保持原样以便后续人工归并。

重复判定：`NoDuplicatedPoem` 触发时会记录日志并返回错误；客户端可捕获并决定跳过或重试（例如微调换行 / 标点）。

示例 curl（单批导入，含作者/译者）：

```bash
curl -X POST "$WIKI_API_BASE/poem/import" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"poems":[{"title":"静夜思","poet":"李白","poet_id":123,"poem":"床前明月光\n疑是地上霜\n举头望明月\n低头思故乡","language_id":1,"translator_ids":[456,"张三","Q789"]}]}'
```

### 3.7 POST /poem/update/{idOrFakeId}

用途：更新一首已存在的诗歌。该接口复用站内 poem update 的主要校验与更新逻辑，包括：重复诗歌校验、译者关系更新、原作链路更新、以及在修改顶层原作作者时同步更新译作作者。

控制器：`PoemAPIController@update`

路径参数：

- `idOrFakeId`: 支持两种形式
- 已存在的内部 `poem.id`
- poem 的公开 `fakeId`

请求头：建议 `Content-Type: application/json`。

请求 JSON：支持部分字段更新；未提交字段保持不变。可用请求字段见第 9 节“字段快速参考”中位置为 `poem/update` 的条目。

示例：

```json
{
  "title": "新的标题",
  "poem": "更新后的正文第一行\n更新后的正文第二行",
  "from": "新的来源",
  "language_id": 1,
  "genre_id": 13,
  "poet_id": 123,
  "original_id": 321,
  "is_original": 0,
  "translator_ids": [456, "Q789", "某位译者"]
}
```

说明：API update 不接受 `original_link`；如需调整原作链路，请直接传 `original_id`（已有 `poem.id` 或 `0`）。

校验与行为说明：

- 需要登录认证
- 路由中的 `idOrFakeId` 找不到对应 poem 时，返回 `Poem not found`
- `poem` 若提交，会按更新场景执行 `NoDuplicatedPoem($currentPoemId)` 去重校验
- `original_id` 若提交，可为已存在的 `poem.id`，也可显式传 `0` 表示解除原作关联或者译作没有原作
- `is_original = 0` 且未提交 `original_id` 时，服务端会将 `original_id` 归一化为 `0`
- `is_original = 1` 时，保存后 `original_id` 会归一化为当前 poem 自身的 id
- `translator_ids` 在 update API 中支持现有 Author ID / `Q<wikidata_id>`，也支持直接传裸字符串文本名称；裸字符串会按文本译者处理
- `translator_ids` 未提交时保持原有译者关系不变；若显式传 `[]`，当前也不会清空现有译者关系，仍沿用站内既有更新逻辑

成功返回：

```json
{
  "success": true,
  "data": {
    "id": 123,
    "fakeId": "abc123",
    "url": "https://example.com/p/abc123"
  }
}
```

失败返回（示例）：

```json
{
  "success": false,
  "message": "Poem not found"
}
```

示例 curl：

```bash
curl -X POST "$WIKI_API_BASE/poem/update/123" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"title":"新的标题","from":"新的来源"}'
```

也可以用 `fakeId` 调用：

```bash
curl -X POST "$WIKI_API_BASE/poem/update/abc123" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"title":"新的标题","from":"新的来源"}'
```

### 3.8 POST /poem/detect

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

使用建议：

- 如果有大批量同语言的诗歌导入，可人工从本文末尾的附录中查询，不要重复调用 /poem/detect
- 不确定来源语言时先调用；
- 返回 null：回退默认语言（如 zh-CN）或拼接更多上下文重试。

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

| 场景                  | 表现                                            | 处理建议                                                          |
| --------------------- | ----------------------------------------------- | ----------------------------------------------------------------- |
| 登录 422              | `{ message: "用户名或密码错误" }`               | 校验凭证或回退人工                                                |
| 登录 429              | 过多尝试                                        | 指数退避重试                                                      |
| 作者导入 ambiguous    | 返回 candidates                                 | 选高分或再拉 wikidata 数据 disambiguation                         |
| 作者导入 invalid name | `invalid name`                                  | 清洗输入（去掉纯符号/数字）                                       |
| 诗歌导入重复          | `errors.poem` 包含重复提示                      | 记录映射，跳过后续同文                                            |
| 诗歌导入超过 200      | 返回 `Limit 200 poems per request`              | 分批                                                              |
| Content-Type 错误     | `Request content type must be application/json` | 修正头                                                            |
| language_id 不在集合  | `errors.language_id`                            | 先获取有效语言列表（需阅读仓库 `LanguageRepository::idsInUse()`） |

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
9. 导入诗歌时 `translator_ids` 中的 `Q<wikidata_id>` 如果没有对应的 author，会在导入的过程中自动创建作者，可以免于通过 API 查询和创建 author。
10. 歧义作者先收集再人工确认，避免误绑定导致后续大量诗歌错归属。
11. 安全：妥善保管 Bearer Token，最小权限账号运行批量导入。

---

## 9. 附：字段快速参考

| 字段           | 位置          | 说明                                                                                                 |
| -------------- | ------------- | ---------------------------------------------------------------------------------------------------- |
| name           | author/import | 作者名称（多语言初始只写默认 locale）                                                                |
| describe       | author/import | 作者简介，可选；写入 `describe_lang` 对应 locale                                                    |
| describe_locale| author/import | 作者简介的 locale，可选；只允许 `zh-CN` 和 `en`，未提交时默认 `zh-CN`                              |
| wikidata_id    | author/import | Wikidata 实体 ID                                                                                     |
| name           | author/update | 作者名称；直接写入 `author.name_lang`                                                               |
| desc           | author/update | 作者简介；直接写入 `author.describe_lang`                                                           |
| birth          | author/update | 作者生日输入值，可选；格式取决于 `birth_fields`                                                          |
| birth_fields   | author/update | 生日精度，可选, 应该和 birth 同时出现；支持 `year`、`month`、`day`                                         |
| title          | poem/import, poem/update | 诗歌标题, 导入时必填                                                                         |
| poet           | poem/import, poem/update | 作者名文本；导入时与 `poet_id` 二选一，更新时可单独改作者名文本                               |
| poet_id        | poem/import, poem/update | 作者 ID；导入时支持 `Q<wikidata_id>`，更新时支持现有 author id 或 `new`                     |
| poet_cn        | poem/import, poem/update | 作者中文名文本                                                                                 |
| poem           | poem/import, poem/update | 正文（\n 分行）；更新时提交会执行重复诗歌校验                                                |
| original_id    | poem/import, poem/update | 原作 poem.id；导入时未提供默认写入 `0`，更新时也可显式传 `0` 表示解除原作关联或译作没有原作 |
| is_original    | poem/update   | 是否原作；`1` 表示原作，`0` 表示译作                                                            |
| from           | poem/import, poem/update | 来源或出处                                                                                    |
| language_id    | poem/import, poem/update | 语言 ID，需在有效列表中                                                                       |
| genre_id       | poem/import, poem/update | 体裁 ID，可选（参见附录 10.2）                                                                |
| subtitle       | poem/import, poem/update | 副标题，可选                                                                                  |
| preface        | poem/import, poem/update | 前言 / 题记，可选                                                                             |
| year           | poem/import, poem/update | 写作年，可选                                                                                  |
| month          | poem/import, poem/update | 写作月，可选                                                                                  |
| date           | poem/import, poem/update | 写作日，可选                                                                                  |
| location       | poem/import, poem/update | 写作地点，可选                                                                                |
| translator_ids | poem/import, poem/update | 译者数组；支持作者 ID、`Q<wikidata_id>`、文本名称，保持顺序；导入时缺失的 `Q<wikidata_id>` 会自动创建作者 |

---

## 10. 附录：数据字典（Languages / Genres / …）

### 10.1 Languages（Language ID / Locale）

Supported Languages:

| id  | name                                    | name_cn      | locale  |
| --- | --------------------------------------- | ------------ | ------- |
| 1   | 简体中文                                 | 简体中文     | zh-CN   |
| 2   | English                                 | 英语         | en      |
| 3   | Deutsch                                 | 德语         | de      |
| 4   | français                                | 法语         | fr      |
| 5   | Italiano                                | 意大利语     | it      |
| 6   | Español                                 | 西班牙语     | es      |
| 7   | にほんご                                 | 日语         | ja      |
| 8   | 조선말                                    | 朝鲜语       | kr      |
| 9   | Ελληνικά                                | 希腊语       | el      |
| 10  | ру́сский язы́к                            | 俄语         | ru      |
| 11  | Português                               | 葡萄牙语     | pt      |
| 12  | Polski                                  | 波兰语       | pl      |
| 13  | svenska                                 | 瑞典语       | sv      |
| 14  | हिन्दी                                     | 印度语       | hi      |
| 15  | اَلْعَرَبِيَّةُ                                 | 阿拉伯语     | ar      |
| 16  | עִבְרִית                                   | 希伯来语     | he      |
| 46  | अवधी                                    | 阿瓦德语     | awa     |
| 68  | བོད་སྐད།                                    | 藏文         | bo      |
| 103 | čeština                                 | 捷克语       | cs      |
| 108 | dansk                                   | 丹麦语       | da      |
| 128 | eesti keel                              | 爱沙尼亚语   | et      |
| 132 | زبان فارسی                              | 波斯語       | fa      |
| 160 | Galego                                  | 加利西亚语   | gl      |
| 178 | hrvatski jezik                          | 克罗地亚语   | hr      |
| 229 | Latine                                  | 拉丁语       | la      |
| 263 | македонски јазик                        | 马其顿语     | mk      |
| 292 | Nederlands                              | 荷兰语       | nl      |
| 294 | norsk                                   | 挪威语       | no      |
| 367 | Sängö                                   | 桑戈语       | sg      |
| 381 | slovenščina                             | 斯洛文尼亚语 | sl      |
| 391 | Gjuha shqipe                            | 阿尔巴尼亚语 | sq      |
| 416 | Wikang Tagalog                          | 他加禄语     | tl      |
| 421 | Türkçe                                  | 土耳其语     | tr      |
| 436 | Українська мова                         | 乌克兰语     | uk      |
| 487 | ئەرەب ھەرپلىرى ئاساسىدىكى ئۇيغۇر يېزىقى | 传统维文     | ug-arab |
| 491 | 繁體中文                                | 繁体中文     | zh-hant |

### 10.2 Poem Genres（Genre ID & Name）

Supported poem genres:

| id  | name     |
| --- | -------- |
| 1   | 五言绝句 |
| 2   | 七言绝句 |
| 4   | 古体诗   |
| 5   | 楚辞     |
| 6   | 赋       |
| 7   | 乐府     |
| 8   | 五言律诗 |
| 9   | 七言律诗 |
| 10  | 排律     |
| 11  | 词       |
| 12  | 曲       |
| 13  | 现代诗   |
