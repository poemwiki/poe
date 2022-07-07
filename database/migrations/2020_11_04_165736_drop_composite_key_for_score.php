<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCompositeKeyForScore extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('score', function (Blueprint $table) {
            $table->dropPrimary(['poem_id', 'user_id']);
            $table->unique(['poem_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('score', function (Blueprint $table) {
            $table->primary(['poem_id', 'user_id']);
            $table->dropUnique(['poem_id', 'user_id']);
        });
    }
}
