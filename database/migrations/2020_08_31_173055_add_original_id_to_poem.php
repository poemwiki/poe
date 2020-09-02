<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalIdToPoem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedInteger('original_id')->nullable(true);
            DB::raw('UPDATE poem AS p LEFT JOIN poem as o
ON o.bedtime_post_id=p.bedtime_post_id
SET p.original_id = o.id
WHERE p.is_original=0 AND o.is_original=1');
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
            $table->dropColumn(['original_id']);
        });
    }
}
