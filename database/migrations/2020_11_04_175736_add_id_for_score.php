<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdForScore extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('score', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->first();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('score', function (Blueprint $table) {
            $table->dropPrimary('id');
        });
    }
}
