<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsForAuthor extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('author', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->nullable();
            $table->unsignedBigInteger('dynasty_id')->nullable();
            $table->string('short_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('author', function (Blueprint $table) {
            $table->dropColumn('short_url');
            $table->dropColumn('nation_id');
            $table->dropColumn('dynasty_id');
        });
    }
}
