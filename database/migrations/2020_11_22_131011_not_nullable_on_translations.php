<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NotNullableOnTranslations extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('translations', function (Blueprint $table) {
            // do it manually
            // $table->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP')->change();
            // $table->timestamp('updated_at')->nullable(false)->default('CURRENT_TIMESTAMP')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        //
    }
}
