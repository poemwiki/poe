<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReward extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('reward', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('campaign_id')->nullable(false);
            $table->string('reward')->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable(true);
            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('reward');
    }
}
