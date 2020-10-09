<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoreTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('score', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('poem_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('score');
            $table->unsignedBigInteger('content_id')->nullable();
            $table->float('factor')->default(1);
            $table->timestamps();
            $table->index('poem_id');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('score');
    }
}
