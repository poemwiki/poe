<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Taggable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedBigInteger('upload_user_id')->nullable();
        });
        Schema::create('taggable', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('tag_id')->nullable(false);
            $table->unsignedBigInteger('taggable_id')->nullable(false);
            $table->string('taggable_type');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('taggable');
        Schema::table('poem', function (Blueprint $table) {
            $table->dropColumn('upload_user_id');
        });
    }
}
