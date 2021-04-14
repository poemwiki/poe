<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSharePicsToPoem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poem', function (Blueprint $table) {
            $table->json('share_pics')->nullable();
            // ALTER TABLE `poe`.`poem`
            // MODIFY COLUMN `updated_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0) AFTER `nation`,
            // MODIFY COLUMN `preface` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `content_id`;

            DB::statement('ALTER TABLE `poe`.`poem`
MODIFY COLUMN `updated_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
MODIFY COLUMN `preface` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `is_owner_uploaded`');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poem', function (Blueprint $table) {
            $table->dropColumn('share_pics');
        });
    }
}
