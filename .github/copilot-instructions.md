# AI Coding Agent Instructions (poemwiki / poe)

Purpose: poemwiki is a multilingual poetry knowledge base & community. Backend: Laravel 9 (PHP 8.3) with MySQL 8, Redis, Meilisearch, Tailwind + Mix/PNPM for assets.
Keep responses concrete, reflect existing patterns—do not introduce speculative architectures.

## Architecture & Domain Map
- Core domain models in `app/Models`: `Poem`, `Author`, `Campaign`, `Score`, `Review`, `Relatable`, etc. Relationships + rich domain logic live directly on models (Eloquent traits + accessors) plus repository layer for complex querying.
- Repositories (`app/Repositories/*Repository.php`) encapsulate higher‑level business queries & aggregation (sorting, scoring, tree building, duplication detection). Use them instead of re‑implementing multi-step queries (e.g. `PoemRepository::preloadTranslatorsForPoems`, `PoemRepository::getTranslatedPoemsTree`).
- Translation / author / poet / translator graph managed via polymorphic `relatable` table + constants in `Relatable::RELATION[...]`. Prefer repository helpers and model accessors (e.g. `Poem->translators`, `Poem->translatedPoems`, `Poem->topOriginalPoem`).
- Activity & version tracking: Spatie Activitylog on major models; poem content version chain stored in `content` table with hash & full_hash fields (see `Poem::boot()` creating/updating logic). Do not bypass events unless you intentionally suppress logging (`Model::withoutEvents`).
- Fake ID obfuscation for public URLs via `HasFakeId` trait (`Poem->fakeId`, `Author->fakeId`). Public routes use fake IDs (`/p/{fakeId}`, `/author/{fakeId}`); internal queries convert with `Poem::getIdFromFakeId` via repository helper `getPoemFromFakeId` when needed.
- Search: Laravel Scout + Meilisearch. Only `Poem` and `Author` implement `Searchable`. Index payloads defined in `toSearchableArray`; filter out records using `shouldBeSearchable`. When adding fields, update both methods and re-import (`php artisan scout:import "App\Models\Poem"`).
- Caching: Heavy tree / aggregation structures cached with long TTL (`translated_poems_tree_{id}`) and invalidated by explicit calls (`Poem::clearTranslatedPoemsTreeCache()` / repository static method). Always clear/forget when changing translation relationships or original/translated linkage.
- Scoring: Central logic in `ScoreRepository` (batchCalc / calc / calcCount). When needing score arrays, use repository instead of ad‑hoc aggregates to stay consistent with weight rules.

## Conventions & Patterns
- Avoid direct `DB::table` unless mirroring existing performance patterns (see preload methods in `PoemRepository`). Start with Eloquent relations + repository functions.
- For list / API output: Repositories expose curated column subsets via static arrays (`PoemRepository::$listColumns`, `$relatedReviewColumns`). Reuse these to keep payload shape stable.
- Sorting of poems for author pages centralized: use `PoemRepository::sortAuthorPoems` or `prepareAuthorPoemsForAPI` instead of duplicating score ordering logic.
- Model boot event responsibilities (e.g. trimming poem text, computing length, setting default flag, creating content version) MUST NOT be duplicated externally—supply raw user intent data and let events normalize.
- Translator / Poet resolution favors cached relations set by preload helpers; when adding list endpoints returning many poems, call `PoemRepository::preloadTranslatorsForPoems($collection)` and `getPoetLabelsForPoems` to dodge N+1.
- Flags & ownership semantics use enumerated static arrays on `Poem` (`$OWNER`, `$FLAG`). Always reference these constants; never hard-code integers.
- Soft delete is pervasive (`SoftDeletes` on major models). Prefer repository `delete` (soft) and `forceDelete` (hard) semantics; check expectations before permanent removal.
- Always write comment in English.

## Typical Workflows
- Environment bootstrap (non-Docker): install PHP deps (`composer install`), JS deps (`pnpm install`), run migrations/seed if provided, then build assets (`pnpm run watch` or `pnpm run prod`).
- Docker build omits auto package discovery; after container start run: `php artisan package:discover --ansi && php artisan vendor:publish --force --tag=livewire:assets --ansi`.

## Adding / Modifying Features
- Prefer adding a repository method when combining: filtering, scoring, caching, translation tree traversal, or multi-table joins—then call from controllers/API.
- When exposing new API output shapes, keep consistency with existing snake_case keys and reuse dynamic accessors (`poetLabel`, `translatorsStr`, `firstLine`).
- When manipulating translation relations (merge, relate translators), ensure cache invalidation via `PoemRepository::clearTranslatedPoemsTreeCache($poem)`.
- For performance, batch preload before iterating large poem collections (scores via `ScoreRepository::batchCalc`, translators via preload method).

## Testing & Safety Notes
- Respect boot logic: constructing `Poem` with partially cleaned text is fine; trimming & hashing handled automatically.
- Avoid triggering infinite recursion when updating `Poem->content_id`: logic already guards by using `withoutEvents` or conditional content creation—follow existing pattern if adjusting.
- Use model / repository public APIs over crafting manual JSON for responses to preserve versioned behaviors.

## Examples
- Get random poem URL (used on landing): `PoemRepository::randomOne()->url`.
- Build translation tree for a poem instance `$poem`: `(new PoemRepository(app()))->getTranslatedPoemsTree($poem)`; invalidate on updates: `PoemRepository::clearTranslatedPoemsTreeCache($poem)`.
- Preload translators for a collection `$poems`: `PoemRepository::preloadTranslatorsForPoems($poems)` then access `$poem->translatorsStr` without extra queries.

## When Unsure
Anchor solutions in existing repository/model methods. If a needed abstraction seems absent, search under `app/Repositories` or dynamic accessors on models before adding new code.

(End of file — update with concrete patterns only as they evolve.)
