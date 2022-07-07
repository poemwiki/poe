<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticeStatus extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('notice', function (Blueprint $table) {
            $table->rename('message');
            $table->unsignedTinyInteger('type')->after('id')
                ->nullable(false)->default(0)
                ->comment('0: normal system notice, 1: msg to all, 2: private msg to one');
        });

        Schema::create('message_status', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('notice_id')->nullable(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedTinyInteger('status')->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->index(['user_id', 'notice_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('message', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->rename('notice');
        });
        Schema::dropIfExists('message_status');
    }
}
