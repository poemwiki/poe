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
            $table->date('birth')->nullable(true);
            $table->date('death')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('author', function (Blueprint $table) {
            $table->dropColumn('birth');
            $table->dropColumn('death');
        });
    }
}
