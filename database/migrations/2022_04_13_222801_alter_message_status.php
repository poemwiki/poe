<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterMessageStatus extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('message_status', function (Blueprint $table) {
            $table->renameColumn('notice_id', 'message_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('message_status', function (Blueprint $table) {
            $table->renameColumn('message_id', 'notice_id');
        });
    }
}
