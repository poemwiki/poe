# Translated Poem Domain

This document explains how poemwiki models originals, translations, and translators today. It is intended as a hand-off reference for the future Next.js + Prisma + tRPC rewrite. The goal is to preserve behaviour, data semantics, and cache invariants when rebuilding the feature set.

## Data Model Snapshot

### `poem` table (`app/Models/Poem.php`)
- `id` *(PK)* and fake IDs (via `HasFakeId`).
- `original_id`:
  - Equals `id` for an original poem.
  - Equals `0` for translations without a known original (legacy state).
  - Equals another poem ID for translations tied to an original.
- `is_original`: legacy flag; current logic uses `original_id` instead.
- `translator` (string JSON) and `translator_id`: legacy ways to pin translators; retained for backward compatibility and migration.
- `poet_id`, `translator_wikidata_id`, `translator_avatar`, etc. support presentation fields.
- `is_owner_uploaded` indicates authorship/ownership semantics (see static `$OWNER`).

### `relatable` table (`app/Models/Relatable.php`)
Polymorphic graph with columns `start_type`, `start_id`, `end_type`, `end_id`, `relation`, optional `properties`.

Key relations:
- `translator_is` (value `1`): connects `Poem` → `Author` (preferred) or `Poem` → `Entry` (free-text translator).
- `merged_to_poem` (value `2`): links duplicate poems to a canonical poem; translations for merged poems are ignored in queries that exclude merged entries.

`RELATION_RULES` limits translator fan-out to six authors per poem.

### `entry` table (`app/Models/Entry.php`)
Stores free-form translator names when no `Author` exists yet. Entries participating as translators are linked through `relatable` with `end_type = App\Models\Entry`.

### Other supporting tables
- `content`: stores version history of poem text (not translation-specific but relevant to boot logic).
- `language`: used to label translated poems; `Poem::lang` relation supplies `language_id` names for tree rendering.

## Domain Concepts

### Originals and translations
- `Poem::getIsTranslatedAttribute()` (`is_translated`) checks `id !== original_id`.
- `Poem::topOriginalPoem` walks the `original_id` chain to the first reachable poem, guarding against loops.
- `Poem::translatedPoems()` returns immediate children (`original_id = current id` and `id <> original_id`).
- Boot hooks (`Poem::boot`) ensure `original_id` defaults to `id` for newly saved originals (based on `is_owner_uploaded` rules) and clear cached trees whenever a poem is created, updated, or deleted.

### Translators
- `Poem::relateToTranslators(array $ids)` accepts a mix of author IDs or raw strings, creates `relatable` rows, clears deprecated fields, and persists.
- `Relatable::translatorIs()` scopes translator relations by poem.
- Legacy fields:
  - `translator_id`: single-author pointer, being migrated to `relatable`.
  - `translator` (string/JSON): raw list for order preservation; still used as fallback text when no relations exist.

### Translator accessors (all in `app/Models/Poem.php`)
- `getTranslatorsAttribute()` resolves to a collection of `Author` or `Entry` instances, preferring preloaded `cached_translators`.
- `getTranslatorsLabelArrAttribute()`, `getTranslatorsStrAttribute()`, and `getTranslatorsApiArrAttribute()` derive array or string representations for API consumers.
- `getTranslatorAvatarAttribute()` picks the first related translator avatar (or uploader avatar if owner-uploaded translation) with fallback to config default.

### Preloading helpers
`PoemRepository::preloadTranslatorsForPoems($poems)` gathers translators in bulk to avoid N+1 queries:
1. Fetches direct `translator_id` authors plus full `relatable` translator rows.
2. Builds normalized arrays (`id`, `name`, `avatar`, `pic_url`, `user_id`).
3. Attaches the synthetic relations `cached_translators` and `cached_translators_str` so accessors use them without additional SQL.

The preload routine keeps legacy data alive by folding `translator_id` and `translator` field values into the cached relations when necessary.

## Translation Tree

`PoemRepository::getTranslatedPoemsTree($poem)` produces a hierarchical representation of originals and all descendants.

Workflow:
1. Determine the `topOriginalPoem` for the requested poem.
2. Cache key: `translated_poems_tree_{topOriginalId}` with TTL ≈ 250 days (360000 minutes). Cached payload is invalidated via `PoemRepository::clearTranslatedPoemsTreeCache()`.
3. `collectAllPoemsInTranslationTree($topOriginal, $maxDepth = 4)` performs a breadth-first crawl over `original_id` references (excluding self-links and repeated IDs). Depth cap prevents runaway recursion.
4. Bulk helpers (`preloadTranslatorsForPoems`, `getPoetLabelsForPoems`) prepare metadata.
5. `buildTranslationTree()` constructs nested arrays containing:
   - `id`, `fakeId`, `originalId`
   - `languageId`, localized `language` name
   - `translatorStr` and `poetLabel`
   - `title`, `url`, `isOriginal`
   - `translatedPoems` (child nodes)

### Cache invalidation
- `Poem::clearTranslatedPoemsTreeCache()` is triggered on create/update/delete via model events.
- Domain code must also call the static repository method after any change to translation relations (`relateToTranslators`, `mergeToMainPoem`, manual `original_id` edits, etc.).

## API Touch Points

### `app/Http/Controllers/API/PoemAPIController.php`
- `random()`, `randomNft()`, `detail()`, and `infoByFakeId()` rely on `translatorsStr`, `translatorsApiArr`, and `translator_avatar_true` flags. These endpoints expect preloading to have run to avoid null translators when only legacy fields are populated.
- `query()` uses translator strings to flag keyword matches and surfaces translation data in search results.

### Web views (`resources/views/poems/*`)
- The poem detail blade expects `translatedPoemsTree` containing the cached hierarchy to render the translation graph and label translators or poets per node.

## Legacy Considerations

- `translator_id` and `translator` fields are slated for removal once all poems expose translators through `relatable`. Migration scripts (e.g., `database/migrations/2025_09_30_migrate_poem_translator_id.php`) backfill missing relations before dropping the columns.
- `is_original` remains in the schema but is considered deprecated; new code should rely on `original_id` logic.
- Some older translations have `original_id = 0`. These represent translations without a confirmed source; rebuild logic should treat them as leaf nodes without a parent.
- The relational graph allows duplicate suppression (`merged_to_poem`) and translator limits; maintain equivalent constraints when porting to Prisma.

## Reimplementation Checklist (Next.js + Prisma + tRPC)

1. **Schema design**
   - Model `Poem` with `originalId` (self-reference) and ownership flags.
   - Model `Relatable` as a junction table with discriminated union on `relation` (translator vs merged_to).
   - Provide auxiliary `Entry` table for anonymous translators.

2. **Relations & accessors**
   - Implement computed fields mirroring `translators`, `translatorsStr`, `translatorsApiArr`, `poetLabel`, `translatorAvatar`.
   - Recreate `topOriginalPoem`, `translatedPoems`, and tree traversal helpers.

3. **Caching strategy**
   - Introduce a cache (Redis or similar) keyed by top-original ID for translation trees.
   - Ensure CRUD mutations and relation edits invalidate/refresh the tree cache.

4. **Bulk loading**
   - Port `preloadTranslatorsForPoems` ideas: batch fetch translator authors, entries, and fallback strings. The frontend consumers assume translator arrays contain avatar, URL, and verification data when available.

5. **API parity**
   - REST endpoints (or tRPC procedures) should return the same shapes for `translators`, `translator_label`, `translator_avatar_true`, and translation tree structures.
   - Maintain filtering behaviour around `merged_to_poem` (exclude merged poems from random/search results).

6. **Migration path**
   - Before deprecating legacy columns, run an equivalent backfill to populate junction tables.
   - Audit endpoints to confirm none read directly from `translator_id` once the rewrite is live.

## Known Edge Cases & TODOs

- Infinite loops in translation chains are mitigated by `processedIds` tracking; ensure the rewrite preserves this guard.
- Cached translator data should include avatar and `user_id` so consumer code can correctly set `avatar_true` and verification badges.
- When a translation is uploaded by a translator (`is_owner_uploaded === translatorUploader`), avatar resolution should prefer uploader avatar before falling back to related translators.
- Cache TTL is generous to avoid churn; consider shorter TTL combined with explicit busting in the new stack.
- `PoemRepository::updateAllTranslatedPoemPoetId()` propagates poet ownership to descendant translations when the original’s poet changes—replicate or replace with triggers in the new system.

By mirroring these structures, access patterns, and cache behaviours, the rewritten application will faithfully reproduce current translated/original poem functionality while removing the legacy `translator_id` footprint.
