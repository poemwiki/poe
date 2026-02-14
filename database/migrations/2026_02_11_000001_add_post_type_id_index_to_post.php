<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('post')) {
            return;
        }

        // Make content column non-nullable
        if (Schema::hasColumn('post', 'content')) {
            try {
                DB::statement('ALTER TABLE `post` MODIFY `content` TEXT NOT NULL');
            } catch (Throwable $e) {
                // Column might already be non-nullable
            }
        }

        // Rename 'subject' index to 'post_type_root_post_id'
        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `subject`');
            DB::statement('ALTER TABLE `post` ADD INDEX `post_type_root_post_id` (`post_type`, `root_post_id`)');
        } catch (Throwable $e) {
            // Index might not exist or already renamed
        }

        // Add post_type_id index
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

        // Revert content column to nullable
        if (Schema::hasColumn('post', 'content')) {
            try {
                DB::statement('ALTER TABLE `post` MODIFY `content` TEXT NULL');
            } catch (Throwable $e) {
                // Column might already be nullable
            }
        }

        // Revert index rename: post_type_root_post_id back to subject
        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `post_type_root_post_id`');
            DB::statement('ALTER TABLE `post` ADD INDEX `subject` (`post_type`, `root_post_id`)');
        } catch (Throwable $e) {
            // Index might not exist
        }

        // Drop post_type_id index
        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `post_type_id`');
        } catch (Throwable $e) {
            // Index might not exist
        }
    }
};
