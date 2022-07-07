<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagToPoem extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedInteger('flag')->default(0)->nullable(false)->after('need_confirm');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('poem', function (Blueprint $table) {
            $table->removeColumn('flag');
        });
    }
}
