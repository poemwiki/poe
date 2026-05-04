# Code Review: Import/Update Metadata Fields (staged changes, 2026-05-04)

> Scope: 8 files, +439/-18 lines. Changes cover the poem import API adding metadata fields,
> the update API hardening `translator_ids` and `original_id` handling, and a refactor of the
> `original_id / original_link` normalisation logic into a Template Method.

---

## 1. `prepareForValidation()` creates a pointless `new_` round-trip

**File:** `app/Http/Requests/API/UpdatePoem.php`

`prepareForValidation()` prefixes every plain-string translator name with `new_` so that the
inherited `ValidTranslatorId` rule accepts it. Then `UpdatePoemRequest::getSanitized()` strips
that very same prefix with `mb_substr($id, strlen('new_'))`.

```
caller sends "张三"
  → prepareForValidation: "new_张三"
  → ValidTranslatorId::isNew() passes
  → getSanitized():  mb_substr("new_张三", 4) → "张三"
```

**Problem (Carmack: explicit over implicit; Torvalds: no clever tricks):**  
The prefix is a pass-through artefact with zero semantic value. The real intent is "bare string =
text translator name". There is no comment explaining the coupling to the parent's rule.

**Suggestion:** Either add a one-line comment explaining the contract with `ValidTranslatorId`, or
extend `getSanitized()` to handle bare strings directly (avoiding the prefix altogether). At
minimum:

```php
// Prefix bare strings with 'new_' so the inherited ValidTranslatorId rule accepts them.
// getSanitized() in the parent class will strip this prefix before persisting.
```

**回复：** 已在 `prepareForValidation()` 旁补上注释，明确这是为了兼容共享的 `ValidTranslatorId` 规则，避免 `new_` 前缀看起来像无意义的魔法值。

---

## 2. `original_id = 0` silently rejected at the API update path

**File:** `app/Http/Requests/API/UpdatePoem.php` → `rules()`

```php
$rules['original_id'] = ['nullable', 'integer', 'exists:' . Poem::class . ',id'];
```

`0` is the sentinel value for "this poem has no original" (documented in
`WebUpdatePoemRequest::normalizeOriginalRelation`). The `exists:` rule rejects `0` because no
poem with `id = 0` exists. The Web path uses a custom closure that explicitly allows `0`.

**Problem (Carmack: no hidden states; SSOT):**  
- There is no test for `PATCH /api/v1/poem/update/{id}` with `original_id: 0`.
- If an API caller attempts to clear the translation link by passing `0`, they get a silent
  validation failure with no hint that `null` is the correct value to use.
- The docs do not mention this limitation.

**Suggestion:** 应该和 web 端 poem update 保持一致, 允许 `original_id` 传 `0` 来表示当前译作没有原作, 或者用来解除原作关联。补充测试覆盖这个 case，并在 API 文档里明确说明。

**回复：** 已把 `original_id` 的校验收回 `UpdatePoemRequest` 作为共享规则，web/api 都允许 `0`；同时新增了 API update 的原作/译作切换测试，并在文档里写明 `0` 的语义。

---

## 3. `normalizeOriginalRelation()` no-op stub adds indirection without value

**File:** `app/Http/Requests/UpdatePoemRequest.php`

```php
protected function normalizeOriginalRelation(array $sanitized): array {
    return $sanitized;
}
```

The base class stub does nothing. Only `WebUpdatePoemRequest` provides a real implementation.
`UpdatePoem` (API) inherits the no-op.

**Problem (Torvalds: don't introduce abstractions for one thing):**  
The Template Method pattern here has one real implementor and one permanent no-op. A reader
must trace two classes to determine that the API path simply *does not* normalise this
relation. The intent would be clearer as a direct `if`/`else` at the call site, or with a
docblock on the base method:

```php
/**
 * Subclasses may override to normalise original_id based on is_original / original_link.
 * Default (API path): no mutation — callers must pass original_id explicitly.
 */
protected function normalizeOriginalRelation(array $sanitized): array {
    return $sanitized;
}
```

**Suggestion:** api 的 poem update 要和 web 端 poem update 保持一致, 那么一些公用的部分可以放到 `app/Http/Requests/UpdatePoemRequest.php` 里

**回复：** 已把“切换为译作且未显式提交原作关系时，将 `original_id` 归一化为 `0`”的逻辑移到 `UpdatePoemRequest`。`WebUpdatePoemRequest` 现在只保留 `original_link` 的 web 专属校验。

---

## 4. `translator_wikidata_id` removed; `poet_wikidata_id` kept — unexplained asymmetry

**File:** `app/Http/Requests/API/UpdatePoem.php` → `rules()`

```php
unset(
    $rules['bedtime_post_id'],
    $rules['bedtime_post_title'],
    $rules['need_confirm'],
    $rules['translator_wikidata_id']  // removed; poet_wikidata_id is NOT removed
);
```

**Problem (Carmack: explicit code; missing "why" comment):**  
No comment explains why `translator_wikidata_id` is excluded from the API while
`poet_wikidata_id` is kept. A future maintainer cannot tell whether this is intentional product
policy or an oversight.

**Suggestion:** poet_wikidata_id 也是遗留字段，应该一起去掉

**回复：** 已从 API update 的对外规则里一并去掉 `poet_wikidata_id`，并删除了 controller 中对应的处理分支；测试现在会同时验证 `poet_wikidata_id` 和 `translator_wikidata_id` 提交后都不会覆盖已有值。

---

## 5. `nation` field: validated and tested, absent from the updated docs table

**File:** `docs/api-import-guide.md`

The updated import-field table adds `poet_cn`, `subtitle`, `preface`, `year`, `month`, `date`,
`location`, `dynasty` — but omits `nation`, despite:
- `nation` being added to the import validator (`PoemAPIController.php` line 1029).
- `test_import_supports_metadata_fields` asserting `$importedPoem->nation`.

Likewise, the validation-rules summary list in the same file does not mention `nation`.

**Suggestion:** nation, dynasty 都是遗留字段（以后会用对应的 id 代替）, api 文档里不要提及这两个遗留字段.

**回复：** 已从公开 API 文档移除 `dynasty` / `nation` 的对外字段说明，同时保留现有后端兼容和导入测试，避免影响旧数据或内部调用。

---

## 7. DRY violations in tests

### 7a. Boilerplate `Poem::create([...])` repeated 5+ times

**Files:** `tests/APIs/PoemUpdateApiTest.php`, `tests/Feature/PoemWebUpdateTest.php`

Every test that needs a poem duplicates the same block:

```php
$poem = Poem::create([
    'title'             => '... ' . uniqid(),
    'poet'              => '...',
    'poem'              => "...\n...",
    'language_id'       => $this->validLanguageId,
    'original_id'       => 0,
    'is_owner_uploaded' => Poem::$OWNER['none'],
    'upload_user_id'    => $user->id,
    'flag'              => Poem::$FLAG['none'],
]);
```

A private `makePoem(User $user, array $overrides = []): Poem` helper in each test class would
eliminate the duplication and make per-test intent clearer via `$overrides`.

### 7b. `$validLanguageId` setup duplicated in `PoemWebUpdateTest`

`PoemUpdateApiTest` stores `$validLanguageId` as a class property populated in `setUp()`.
`PoemWebUpdateTest` re-implements the same lookup inline inside the test method. Extract to an
`ApiTestTrait` method or base `TestCase` helper.

**回复：** 已按 SSOT 抽到 `tests/TestCase.php`：新增共享的 `getOrCreateValidLanguageId()` 和 `makePoem()` helper，`PoemUpdateApiTest` 与 `PoemWebUpdateTest` 都改为复用同一份实现。

---

## 8. Weak assertion in `test_update_poem_ignores_internal_only_fields`

**File:** `tests/APIs/PoemUpdateApiTest.php`

```php
'translator_wikidata_id' => null,   // initial value
// ...test sends 999999...
$this->assertNull($poem->translator_wikidata_id);  // still null → trivially true
```

The initial `translator_wikidata_id` is already `null`, so the assertion `assertNull` passes
whether or not the field is correctly blocked. The test does not actually prove the field is
ignored — it just proves a `null` is `null`.

**Fix:** Set a non-null initial value (e.g., `1`) and assert it is unchanged after the update.

**回复：** 已将测试初始值改为非空，并把断言加强为“更新后保持原值不变”；同时覆盖了 `poet_wikidata_id` 与 `translator_wikidata_id` 两个 legacy 字段。


---

## 11. Missing newline at end of file

**File:** `tests/Feature/PoemWebUpdateTest.php`

```
}
\ No newline at end of file
```

POSIX requires a terminating newline. Most linters/editors flag this.

**回复：** 已补文件末尾换行。

---
