<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemoToTransaction extends Migration {
    public function up() {
        Schema::table('transaction', function (Blueprint $table) {
            $table->string('memo')->nullable(true)->comment('can be price for listing transaction.');
        });
    }

    public function down() {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn('memo');
        });
    }
}
