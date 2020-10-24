<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserBindInfo extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_bind_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('open_id', 64);
            $table->unsignedInteger('open_id_crc32');
            $table->char('union_id', 64);
            $table->unsignedInteger('union_id_crc32');
            $table->bigInteger('user_id');
            $table->tinyInteger('bind_status')->default(1);
            $table->tinyInteger('bind_ref')->comment('绑定来源：0：微信内授权 1：微信扫码登录');
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('gender')->comment('0:unknow 1:male 2:female');

            $table->json('info')->nullable();

            $table->softDeletes();
            $table->timestamps();
            $table->index('open_id_crc32');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('user_bind_info');
    }
}
