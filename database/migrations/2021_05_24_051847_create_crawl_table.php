<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlTable extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up() {
        // Schema::create('crawl_job', function (Blueprint $table) {
        //     $table->bigIncrements('id')->unsigned();
        //     // Artisan::call('alias:import', ['--id' => $wikidata_id]);
        //     $table->string('command')->nullable(false);
        //     $table->json('parameters')->nullable(true);
        //
        // });
        // Schema::create('import_job', function (Blueprint $table) {
        //     $table->bigIncrements('id')->unsigned();
        // });
        Schema::create('crawl', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('f_crawl_id')->default(0);
            $table->unsignedBigInteger('admin_user_id')->default(1);
            $table->string('url')->nullable(false);
            $table->string('model')->nullable(false);
            $table->string('name')->nullable(false);
            $table->index(['model', 'url']);
            $table->json('export_setting')->nullable(true);
            $table->unsignedBigInteger('exported_id')->nullable(true);
            $table->json('result')->nullable(true);
            $table->text('html')->nullable(true);
            $table->dateTime('created_at')->default(DB::raw('NOW()'))->nullable(false);
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('crawl');
        Schema::dropIfExists('crawl_job');
        Schema::dropIfExists('import_job');
    }
}
