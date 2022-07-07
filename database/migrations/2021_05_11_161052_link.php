<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Link extends Migration
{
    /**
     * 外部商品链接
     * @return void
     */
    public function up() {
        Schema::create('link', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->tinyInteger('type')->default(0);
            $table->json('setting');
            $table->unsignedBigInteger('language_id')->default(1)->nullable(false);
            $table->string('desc')->nullable();
            $table->string('memo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->nullable();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('link');
    }
}
