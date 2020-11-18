<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWiki extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('wikidata', function (Blueprint $table) {
            $table->dropColumn('label_lang');
        });
        Schema::table('alias', function (Blueprint $table) {
            $table->dropColumn('author_id');
            // $table->unique(['author_id', 'locale', 'name'], 'wikidata_id_locale_name');
            $table->string('locale', 128)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('wikidata', function (Blueprint $table) {
            $table->json('label_lang');
            $table->unsignedBigInteger('author_id')->change();
            $table->string('locale', 128)->nullable(false)->change();
        });
    }
}
