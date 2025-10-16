<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Use raw SQL to avoid Doctrine DBAL type mapping issues with 'timestamp'.
        // Make timestamps NOT NULL since defaults are provided.
        DB::statement('ALTER TABLE `review` MODIFY `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE `review` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Revert to nullable timestamps with no defaults.
        DB::statement('ALTER TABLE `review` MODIFY `created_at` TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE `review` MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL');
    }
};
