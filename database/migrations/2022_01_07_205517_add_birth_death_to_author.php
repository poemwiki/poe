<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBirthDeathToAuthor extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('author', function (Blueprint $table) {
            $table->mediumInteger('birth_year')->nullable(true);
            $table->unsignedTinyInteger('birth_month')->nullable(true);
            $table->unsignedTinyInteger('birth_day')->nullable(true);
            $table->mediumInteger('death_year')->nullable(true);
            $table->unsignedTinyInteger('death_month')->nullable(true);
            $table->unsignedTinyInteger('death_day')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('author', function (Blueprint $table) {
            $table->dropColumn('birth_year');
            $table->dropColumn('birth_month');
            $table->dropColumn('birth_day');
            $table->dropColumn('death_year');
            $table->dropColumn('death_month');
            $table->dropColumn('death_day');
        });
    }
}
