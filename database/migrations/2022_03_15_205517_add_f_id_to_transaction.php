<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFIdToTransaction extends Migration {
    public function up() {
        Schema::table('transaction', function (Blueprint $table) {
            $table->unsignedBigInteger('f_id')->default(0);
        });
    }

    public function down() {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn('f_id');
        });
    }
}
