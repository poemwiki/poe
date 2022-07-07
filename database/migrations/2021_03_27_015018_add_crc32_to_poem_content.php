<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCrc32ToPoemContent extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::statement(<<<'SQL'
ALTER TABLE `poe`.`content`
CHANGE COLUMN `new_hash` `hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `entry_id`,
CHANGE COLUMN `hash` `hash_f` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'current version\'s father\'s hash' AFTER `hash`,
ADD COLUMN `hash_crc32` int UNSIGNED NOT NULL AFTER `entry_id`,
ADD COLUMN `full_hash_crc32` int UNSIGNED NOT NULL AFTER `hash_crc32`,
MODIFY COLUMN `full_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `hash_f`,
ADD COLUMN `full_hash_f` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'current version\'s father\'s full_hash' AFTER `full_hash`,
ADD INDEX `hash_crc`(`hash_crc32`),
ADD INDEX `full_hash_crc`(`full_hash_crc32`);
SQL
    );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('content', function (Blueprint $table) {
            $table->dropColumn('hash_crc32');
            $table->dropColumn('full_hash_crc32');
            $table->dropColumn('full_hash_f');
            $table->renameColumn('hash_f', 'hash');
            $table->renameColumn('hash', 'new_hash');
            $table->renameColumn('full_hash_f', 'full_hash');
        });
    }
}
