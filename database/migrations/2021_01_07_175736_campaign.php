<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Campaign extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('campaign', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->timestamp('start')->useCurrent();
            $table->timestamp('end')->nullable(false);
            $table->string('image');
            $table->json('name_lang')->nullable(false);
            $table->json('describe_lang')->nullable(false);
            $table->unsignedBigInteger('tag_id')->nullable(false);
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
        Schema::drop('campaign');
    }
}
