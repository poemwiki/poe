<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reward_result', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('reward_id')->nullable(false)->unique();
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('campaign_id')->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->index('user_id');
            $table->unique(['campaign_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reward_result');
    }
}
