<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Genre extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('genre', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 128)->unique();
            $table->json('name_lang');
            $table->unsignedBigInteger('f_id')->default(0);

            $table->text('wikidata_id')->nullable();
            $table->json('describe_lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dynasty', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 128)->unique();
            $table->json('name_lang');
            $table->unsignedBigInteger('f_id')->default(0);

            $table->text('wikidata_id')->nullable();
            $table->json('describe_lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nation', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 128)->unique();
            $table->json('name_lang');
            $table->unsignedBigInteger('f_id')->default(0);

            $table->text('wikidata_id')->nullable();
            $table->json('describe_lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tag', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 128)->unique();
            $table->json('name_lang');

            $table->text('wikidata_id')->nullable();
            $table->json('describe_lang')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('category', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name', 128)->unique();
            $table->json('name_lang');

            $table->text('wikidata_id')->nullable();
            $table->json('describe_lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('author', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->json('name_lang');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('pic_url')->nullable();

            $table->unsignedBigInteger('wikidata_id')->nullable(false)->unique();
            $table->json('wikipedia_url')->nullable();
            $table->json('describe_lang')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('poem', function (Blueprint $table) {
            $table->renameColumn('language', 'language_id');
            $table->unsignedBigInteger('genre_id')->nullable();
            $table->unsignedBigInteger('dynasty_id')->nullable();
            $table->unsignedBigInteger('nation_id')->nullable();
        });
        Schema::table('language', function (Blueprint $table) {
            $table->removeColumn('name_cn');
            $table->json('name_lang');
            $table->string('locale')->default('');
            $table->string('pic_url')->nullable();
            $table->text('wikidata_id')->nullable();
            $table->json('wikipedia_url')->nullable();
        });
        Schema::drop('lang');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('genre');
        Schema::drop('dynasty');
        Schema::drop('nation');
        Schema::drop('tag');
        Schema::drop('category');
        Schema::drop('author');
    }
}
