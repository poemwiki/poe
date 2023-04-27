<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddListTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('list', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('name');
            $table->string('desc');
        });
        Schema::create('list_poem', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('list_id');
            $table->unsignedBigInteger('poem_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('list');
        Schema::dropIfExists('list_poem');
    }
}
