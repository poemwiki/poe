<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSendUserIdToMessage extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('message', function (Blueprint $table) {
            $table->renameColumn('user_id', 'receiver_id');
            $table->unsignedBigInteger('sender_id')->after('id')
                ->nullable(false)->default(0)
                ->comment('0: send from system, other: send from user');
            $table->index(['receiver_id', 'sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('message', function (Blueprint $table) {
            $table->renameColumn('receiver_id', 'user_id');
            $table->dropColumn('sender_id');
            $table->dropIndex(['receiver_id', 'user_id']);
        });
    }
}
