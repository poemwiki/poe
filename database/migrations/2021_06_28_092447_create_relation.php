<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relatable', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->tinyInteger('relation')->comment('0: poet is, 1: translator is, 2: has link');
            $table->morphs('start', 'start');
            $table->morphs('end', 'end');
            $table->index(['start_id', 'end_id', 'relation']);

            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('relatable');
    }
}
