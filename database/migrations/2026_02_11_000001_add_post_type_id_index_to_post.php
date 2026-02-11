<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('post')) {
            return;
        }

        if (Schema::hasColumn('post', 'post_type') &&
            Schema::hasColumn('post', 'id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD INDEX `post_type_id` (`post_type`, `id`)');
            } catch (Throwable $e) {
                // Index might already exist
            }
        }
    }

    public function down() {
        if (!Schema::hasTable('post')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `post_type_id`');
        } catch (Throwable $e) {
            // Index might not exist
        }
    }
};
