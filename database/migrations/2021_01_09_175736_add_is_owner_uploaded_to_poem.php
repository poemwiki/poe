<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsOwnerUploadedToPoem extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_owner_uploaded')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('poem', function (Blueprint $table) {
            $table->dropColumn('is_owner_uploaded');
        });
    }
}
