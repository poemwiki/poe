<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEgg extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('egg', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->json('cfg');
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(true);
            $table->dateTime('deleted_at')->nullable(true);
        });
        Schema::table('relatable', function (Blueprint $table) {
            $table->json('properties')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('egg');
        Schema::table('relatable', function (Blueprint $table) {
            $table->dropColumn('properties');
        });
    }
}
