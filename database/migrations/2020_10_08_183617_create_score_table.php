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
            $table->unsignedBigInteger('poem_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('score');
            $table->float('weight')->default(1);
            $table->primary(['poem_id', 'user_id']);
        });
        Schema::create('comment', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('poem_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('like')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->text('content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('score');
        Schema::drop('comment');
    }
}
