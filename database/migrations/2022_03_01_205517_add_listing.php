<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddListing extends Migration {
    public function up() {
        Schema::create('listing', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('nft_id')->nullable(false);
            $table->unsignedTinyInteger('currency')->default(0)->nullable(false); // 0: poem gold
            $table->decimal('price', 27, 18)->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->index(['nft_id']);
        });
    }

    public function down() {
        Schema::dropIfExists('listing');
    }
}
