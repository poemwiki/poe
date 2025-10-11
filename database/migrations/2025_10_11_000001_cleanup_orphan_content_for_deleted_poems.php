<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup orphan `content` records for poems that were soft-deleted.
 * This fixes duplicate-content detection after poem deletion.
 */
class CleanupOrphanContentForDeletedPoems extends Migration {
    /**
     * Run the migrations.
     *
     * Deletes `content` rows (type=0) whose `entry_id` points to poems
     * that are currently soft-deleted.
     */
    public function up(): void {
        // Hard-delete content linked to soft-deleted poems (type=0)
        DB::statement(<<<'SQL'
DELETE c FROM `content` c
JOIN `poem` p ON p.`id` = c.`entry_id`
WHERE c.`type` = 0
  AND p.`deleted_at` IS NOT NULL;
SQL
        );

        // Additionally, hard-delete orphan content where the poem row no longer exists
        DB::statement(<<<'SQL'
DELETE c FROM `content` c
LEFT JOIN `poem` p ON p.`id` = c.`entry_id`
WHERE c.`type` = 0
  AND p.`id` IS NULL;
SQL
        );
    }

    /**
     * Reverse the migrations.
     *
     * We do not resurrect content for deleted poems.
     */
    public function down(): void {
        // Intentionally left blank: no-op rollback.
    }
}
