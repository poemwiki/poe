<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTable extends Migration {
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('file', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->morphs('model');
            $table->string('path')->nullable(false)->comment('full path to the file');
            $table->string('name')->nullable(false)->comment('display name');
            $table->unsignedTinyInteger('type')->nullable(false)->comment('image, thumb, audio, video');
            $table->string('mime_type')->nullable(false);
            $table->string('disk')->nullable(false);
            $table->unsignedBigInteger('size')->nullable(false);
            $table->unsignedBigInteger('fid')->nullable(false)->default(0)->comment('father id');
            $table->json('props')->nullable(true);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        Schema::dropIfExists('file');
    }
}
