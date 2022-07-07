<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('review', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('poem_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('like')->default(0);
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('title', 128)->nullable();
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::drop('comment');
        Schema::create('comment', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->morphs('subject', 'subject');
            $table->nullableMorphs('reply', 'reply');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('like')->default(0);
            $table->text('content')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('review');
        Schema::drop('comment');
    }
}
