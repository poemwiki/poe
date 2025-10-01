# Author Names and Aliases

This document explains how primary names (`name_lang`) and aliases (`alias` table) relate and how they are maintained.

## Data Model

- `author.name_lang` (translatable JSON): stores at most one primary display name per locale. Used for labels and UI display.
- `alias` (table): stores zero or more alternative names per locale, including variants, transliterations, etc.

## Update Flow (Controller)

Updates are handled in `app/Http/Controllers/AuthorController::updateAlias`:

- Only enabled languages are processed.
- Aliases are replaced in full: existing aliases for the author are deleted, then new aliases inserted.
- If a locale has no primary name in `name_lang`, a submitted alias for that locale is used to fill it.
- If a deleted alias equals `name_lang[locale]`, that locale's primary name is removed.
- Must keep at least one alias and at least one non-empty `name_lang` entry; otherwise the update is rejected.
- Alias writes and author save occur inside a single DB transaction for consistency.

## Rationale

- Clear separation: `name_lang` is the single, canonical display name per locale; `alias` captures other known names.
- Practical defaults: when users add aliases but no primary name, the system can derive a sensible default.
- Safe deletes: removing an alias that was the primary name clears the primary name instead of leaving stale data.

## Future Considerations

- UI could distinguish primary name from aliases per locale explicitly (e.g., mark an alias as primary).
- Validation could ensure aliases belong to enabled languages and provide clearer feedback when ignored.
- If alias rows require audit/history, avoid full delete + insert and use upserts with metadata.

