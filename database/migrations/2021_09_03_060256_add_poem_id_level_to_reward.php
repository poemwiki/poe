<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPoemIdLevelToReward extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('award', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->string('name', 128);
            $table->unsignedTinyInteger('result_type')->nullable(false)->default(0);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->unsignedBigInteger('campaign_id')->nullable(true);
        });
        Schema::table('reward_result', function (Blueprint $table) {
            $table->unsignedBigInteger('poem_id')->after('campaign_id')->nullable(true);
            $table->dropUnique(['campaign_id', 'user_id']);
        });
        Schema::table('reward', function (Blueprint $table) {
            $table->unsignedBigInteger('award_id')->after('campaign_id')->nullable(false)->default(3);
        });

        // already executed manually
        // Schema::table('poem', function (Blueprint $table) {
        //     $table->index('upload_user_id', 'upload_user_id');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('award');
        Schema::table('reward_result', function (Blueprint $table) {
            $table->dropColumn('poem_id');
        });
        Schema::table('reward', function (Blueprint $table) {
            $table->dropColumn('award_id');
        });
    }
}
