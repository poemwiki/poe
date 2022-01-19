<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArTxIdToContent extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('content', function (Blueprint $table) {
            $table->string('ar_tx_id', '64')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('content', function (Blueprint $table) {
            $table->dropColumn('ar_tx_id');
        });
    }
}
