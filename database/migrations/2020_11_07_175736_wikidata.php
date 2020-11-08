<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Wikidata extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // need to migrate wikidata_poet table first

        Schema::create('wikidata', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unsigned()->unique()->primary()->comment('wikidata entity ID');
            $table->enum('type', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10])->comment(
                '0:poet, 1:dynasty,
                2:nation/region/country of citizenship, 3:language/locale, 4:genre');
            $table->json('label_lang');
            $table->json('data')->nullable();
        });

        Schema::create('alias', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name');
            $table->string('locale', 128);

            $table->foreignId('author_id')->nullable()->constrained('author');
            $table->foreignId('language_id')->nullable()->constrained('language');
            $table->foreignId('wikidata_id')->nullable()->constrained('wikidata');

            $table->timestamps();
        });


        Schema::table('poem', function (Blueprint $table) {
            $table->foreignId('poet_id')->nullable()->constrained('author', 'id');
            $table->foreignId('translator_id')->nullable()->constrained('author', 'id');
        });

        Schema::table('author', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('language', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('category', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('tag', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('nation', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('dynasty', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });
        Schema::table('genre', function (Blueprint $table) {
            $table->unsignedBigInteger('wikidata_id')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('wikidata');
        Schema::drop('alias');
    }
}
