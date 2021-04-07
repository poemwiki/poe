<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateScores extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('content', function (Blueprint $table) {
            DB::statement('UPDATE `poem` SET `score`=`score` * 2 WHERE score IS NOT NULL');
            // score
            DB::statement('UPDATE `score` SET `score`=`score` * 2');
            // activity log
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('content', function (Blueprint $table) {

        });
    }
}
