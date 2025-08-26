<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('activity_log', function (Blueprint $table) {
            // Add composite index to cover common query pattern:
            // WHERE causer_type = ? AND causer_id = ? AND subject_type = ? ORDER BY id DESC LIMIT ...
            // Putting id last allows efficient range / ordering scans.
            $table->index(['causer_type', 'causer_id', 'subject_type', 'id'], 'activity_log_causer_subject_id_idx');

            // Drop old separate causer index (no longer needed once composite index exists).
            // Original name follows Laravel's default naming convention for morph indexes.
            $table->dropIndex('causer');
        });
    }

    public function down(): void {
        Schema::table('activity_log', function (Blueprint $table) {
            // Restore original causer morph index.
            $table->index(['causer_type', 'causer_id'], 'causer');
            // Remove composite index.
            $table->dropIndex('activity_log_causer_subject_id_idx');
        });
    }
};
