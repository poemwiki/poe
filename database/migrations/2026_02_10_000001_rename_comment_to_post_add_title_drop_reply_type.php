<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (Schema::hasTable('comment') && !Schema::hasTable('post')) {
            Schema::rename('comment', 'post');
        }

        if (!Schema::hasTable('post')) {
            return;
        }

        if (!Schema::hasColumn('post', 'title')) {
            Schema::table('post', function (Blueprint $table) {
                $table->string('title', 255)->nullable()->after('like');
            });
        }

        if (Schema::hasColumn('post', 'reply_type')) {
            try {
                DB::statement('ALTER TABLE `post` DROP INDEX `reply`');
            } catch (Throwable $e) {
            }

            Schema::table('post', function (Blueprint $table) {
                $table->dropColumn('reply_type');
            });
        }

        if (Schema::hasColumn('post', 'subject_type') && !Schema::hasColumn('post', 'post_type')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `subject_type` `post_type` VARCHAR(255) NOT NULL'
            );
        }

        if (Schema::hasColumn('post', 'subject_id') && !Schema::hasColumn('post', 'root_post_id')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `subject_id` `root_post_id` BIGINT UNSIGNED NOT NULL DEFAULT 0'
            );
        }

        if (Schema::hasColumn('post', 'reply_id') && !Schema::hasColumn('post', 'parent_post_id')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `reply_id` `parent_post_id` BIGINT UNSIGNED NULL'
            );
        }

        if (Schema::hasColumn('post', 'root_post_id')) {
            DB::statement(
                'ALTER TABLE `post` MODIFY `root_post_id` BIGINT UNSIGNED NOT NULL DEFAULT 0'
            );
        }

        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `subject`');
        } catch (Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `reply`');
        } catch (Throwable $e) {
        }

        if (Schema::hasColumn('post', 'post_type') && Schema::hasColumn('post', 'root_post_id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD INDEX `subject` (`post_type`, `root_post_id`)');
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasColumn('post', 'parent_post_id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD INDEX `reply` (`parent_post_id`)');
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasColumn('post', 'post_type') && Schema::hasColumn('post', 'root_post_id')) {
            DB::statement(
                "UPDATE `post` SET `root_post_id` = `id` WHERE `post_type` IN ('post','poem') AND `root_post_id` = 0"
            );
        }
    }

    public function down() {
        if (!Schema::hasTable('post')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `reply`');
        } catch (Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `post` DROP INDEX `subject`');
        } catch (Throwable $e) {
        }

        if (Schema::hasColumn('post', 'parent_post_id') && !Schema::hasColumn('post', 'reply_id')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `parent_post_id` `reply_id` BIGINT UNSIGNED NULL'
            );
        }

        if (Schema::hasColumn('post', 'root_post_id') && !Schema::hasColumn('post', 'subject_id')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `root_post_id` `subject_id` BIGINT UNSIGNED NOT NULL DEFAULT 0'
            );
        }

        if (Schema::hasColumn('post', 'post_type') && !Schema::hasColumn('post', 'subject_type')) {
            DB::statement(
                'ALTER TABLE `post` CHANGE COLUMN `post_type` `subject_type` VARCHAR(255) NOT NULL'
            );
        }

        if (!Schema::hasColumn('post', 'reply_type') && Schema::hasColumn('post', 'subject_id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD COLUMN `reply_type` VARCHAR(255) NULL AFTER `subject_id`');
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasColumn('post', 'subject_type') && Schema::hasColumn('post', 'subject_id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD INDEX `subject` (`subject_type`, `subject_id`)');
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasColumn('post', 'reply_type') && Schema::hasColumn('post', 'reply_id')) {
            try {
                DB::statement('ALTER TABLE `post` ADD INDEX `reply` (`reply_type`, `reply_id`)');
            } catch (Throwable $e) {
            }
        }

        if (Schema::hasColumn('post', 'title')) {
            Schema::table('post', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }

        if (!Schema::hasTable('comment')) {
            Schema::rename('post', 'comment');
        }
    }
};
