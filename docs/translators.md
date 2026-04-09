# Translator System（legacy PHP Laravel Implementation）

## 总览
- 译者体系通过 `poem` 表字段 + `relatable` 多态关联记录一个诗歌对应 0~6 个译者，可以是站内作者（`author`）或仅输入名字的临时条目（`entry`）。
- 译者信息不仅决定前台展示，还影响“原创/译作”判定、作者主页、翻译树以及搜索索引，因此在读写时伴随大量派生逻辑与缓存。
- 现有实现仍保留 legacy 字段（`translator_id`、`translator_wikidata_id`、`poem.translator`）做兼容；真正的权威数据来自 `relatable` 表中的 `translator_is` 记录。

## 数据模型

### `poem` 表相关字段
- `translator`：字符串或 JSON（`CreatePoemRequest::getSanitized` 里写入 ID 顺序），仅用于回显/排序；当存在 `relatable` 记录时它不再代表实际译者。
- `translator_id` / `translator_wikidata_id`：单译者时代遗留字段，`Poem::relateToTranslators()` 会置空它们，读取侧只作为最后兜底。
- `original_id` / `is_original` / `is_owner_uploaded`：区分原作与译作。`Poem::$OWNER` 中 `translatorUploader`、`translatorAuthor`、`multiTranslator*` 等枚举决定“谁拥有译作”，`getTranslatorAvatarAttribute()` 等 accessor 会根据这些枚举回退到对应用户。

### `relatable` 表（`App\Models\Relatable`）
- `relation = translator_is`，`start_type = App\Models\Poem`，`end_type` 可为 `App\Models\Author` 或 `App\Models\Entry`。
- `RELATION_RULES['translator_is']`（定义在 `App\Models\Relatable` 静态常量中）将译者数量上限标记为 6，目前尚无运行时校验逻辑；`properties` 字段暂未使用，可作为未来保存排序信息的地方。
- `relatedTranslators()` / `Relatable::translatorIs()` 负责查询；`Author::poemsAsTranslator()` 通过同一表反查译者参与的译作。

### `entry` 表（`App\Models\Entry`）
- 仅包含 `name` 与 `type`；当表单里录入了自由文本译者时由 `Poem::relateToTranslators()` 自动创建，用于表示“尚未有作者条目的译者”。

### 缓存字段
- `PoemRepository::preloadTranslatorsForPoems()` 会为每首诗设置虚拟关联 `cached_translators` / `cached_translators_str`，供 `Poem` 各种 accessor 直接复用以避免 N+1。

## 写入流程

### Web / Admin 表单
- `resources/views/poems/components/form-elements.blade.php` 使用 `v-select` 多选，`translator_ids` 保存一个混合数组。
- 每个元素可以是：
  1. 站内作者 ID（数字）；
  2. Wikidata 形式 `Q<id>`（`ValidTranslatorId::isWikidataQID()` 会触发 `AuthorRepository::getExistedAuthor()` 以作者入库）；
  3. 新建标记 `new_<name>`（最终写入 `entry` 表）。
- `CreatePoemRequest` / `UpdatePoemRequest` 统一验证与归一化，生成 `translator_ids`（纯数字或字符串）和顺序数组 `translatorsOrder`，后者会以 JSON 写回 `poem.translator` 保持原始排序。

### API / 小程序
- `App\Http\Controllers\API\PoemAPIController::create()`（微信小程序）和 `::store()`（活动投稿）沿用同一 `StorePoem` 请求对象，因此和 Web 端共享验证、归一流程。
- 批量导入接口 `PoemAPIController::import()` 接受最多 200 首诗，`translator_ids` 同样支持 ID、`Q` 前缀与自由文本；导入完成后也会调用 `relateToTranslators()` 建立关联。

### 关联写入
- `Poem::relateToTranslators(array $ids)` 逐一处理：
  - 若数字且能找到 `Author`，就写 `relatable` 指向作者；
  - 否则自动 `Entry::create(['name' => $id])`，并将关联指向该 entry。
- 建立多译者后清空 `translator_id`、`translator_wikidata_id`，确保读取端只依赖多对多关系。
- `PoemController::update()` 中如译者集合有变化，会先 `relatedTranslators()->delete()` 再重建，目前尚无增量 diff（TODO）。

## 读取与缓存

### `Poem` 动态属性
- `getTranslatorsAttribute()`：优先返回 `cached_translators`，否则查询 `relatedTranslators()`，并根据 `end_type` 实例化 `Author` 或 `Entry`。
- `getTranslatorsLabelArrAttribute()`、`getTranslatorsStrAttribute()`、`getTranslatorsApiArrAttribute()` 基于上述集合构建数组 / 字符串；若没有关联，则后退到 `translator` 字段。
- `getTranslatorLabelAttribute()`、`getTranslatorAvatarAttribute()` 以译者集合、legacy 字段与 `is_owner_uploaded` 枚举综合决定展示名称 / 头像。

### 批量预加载
- `PoemRepository::preloadTranslatorsForPoems($poems)`：
  1. 收集所有 `translator_id`，一次性查 `author` 表作为“直连”译者；
  2. 再查 `relatable` + `author`/`entry`，分组到每首诗；
  3. 合并并去重得到集合，写入 `cached_translators` 与 `cached_translators_str`；如果仍没有译者但 `poem.translator` 非空，则缓存该字符串，否则缓存空串。
- 该方法在作者页（`AuthorController::show()`）、翻译树（`PoemRepository::getTranslatedPoemsTree()`）以及部分 API 列表（例如 `PoemAPIController::list` 搜索结果中显式调用）都会执行，以避免在循环里触发 N+1 查询。

### 翻译树缓存
- `PoemRepository::getTranslatedPoemsTree($poem)` 以顶层原作为根，调用 `collectAllPoemsInTranslationTree()` 广度优先抓取多层译作，并对整棵树执行 `preloadTranslatorsForPoems()`。
- 构建过程中 `translatorStr` 来自 `Poem->translatorsStr`，因此需要确保缓存或关系已经就绪。
- `Poem::clearTranslatedPoemsTreeCache()` 在 `created`、`updated`、`deleted` 事件里被触发，用以失效 `translated_poems_tree_{topOriginalId}`；如果未来重构需要在“仅修改译者关联”时刷新缓存，需记得在对应 mutation 中重复这一逻辑。

## 展示与 API 输出
- 前台诗歌详情 `resources/views/poems/fields/translator.blade.php` 迭代 `Poem->translators`，对 `Author` 输出作者页链接，对 `Entry` 输出站内搜索链接；若译者集合为空，则回退到 `translatorLabel`。
- API 多处输出译者信息：
  - 列表接口（例如 `PoemAPIController::suggest`, `::search`）在响应中包含 `translator_label`；
  - `PoemAPIController::show()` 返回 `translators_str` 以及 `translators`（带头像、URL 的结构化数组）；
  - `original_poem` 字段及翻译树节点也会携带译者字符串，供客户端构建“原作→译作”链路。
- 作者详情页会把作为译者的作品单独列出，之前加载的 `cached_translators` 让 Blade 视图可以直接渲染译者信息。
- 搜索 MeiliSearch 索引中把 `relatedTranslators` 配置成 `filterableAttributes`（`config/scout.php`），未来可以考虑在重构时显式同步译者 ID/名称到搜索文档。

## 重构要点（Next.js + Prisma + tRPC）
- Legacy 字段：重构时可直接抛弃 `poem.translator_id`、`translator_wikidata_id`、`poem.translator` 等兼容字段，前提是上线前通过迁移脚本补齐 `relatable` 记录。
- 建模：保留等价的 Poem ↔ Author/Entry 关系表即可（当前 `relatable` 通过 `end_type` 区分作者与自由名称）；仍需考虑如何显式持久化译者顺序（现阶段借助 `poem.translator` JSON 暂存）。
- 输入协议：保持对三种输入格式的支持（站内 ID、`Q` 前缀、自由文本），以及批量导入接口中“自动把 `Q` 转成作者”的行为。
- 缓存与查询：翻译树、作者页、API 列表都依赖批量预加载与字符串缓存，在新栈中应提供批量查询 + in-memory 缓存或 DataLoader，以避免多次 round-trip。
- 事件与失效：当前通过 Eloquent 事件清理翻译树缓存，且 `relateToTranslators()` 不会自动清理；重构时应统一在 mutation 完成后失效相关缓存。
- 所有引用点：重建时需覆盖 Web 表单、Poem API、作者页、搜索、翻译树、原创/译作判定与头像展示等逻辑，确保“单译者/多译者/纯文本译者”三种场景都能一致复现。
