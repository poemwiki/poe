<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToCrawl extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('crawl', function (Blueprint $table) {
            $table->string('source', '128')->nullable(false);
            $table->longText('html')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('crawl', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
