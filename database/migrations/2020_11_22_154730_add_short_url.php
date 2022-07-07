<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShortUrl extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->string('short_url')->nullable();
        });
        Schema::table('wx_post', function (Blueprint $table) {
            $table->string('short_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('poem', function (Blueprint $table) {
            $table->dropColumn('short_url');
        });
        Schema::table('wx_post', function (Blueprint $table) {
            $table->dropColumn('short_url');
        });
    }
}
