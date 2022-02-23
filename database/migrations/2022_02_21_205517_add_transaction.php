<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransaction extends Migration {
    public function up() {
        Schema::create('nft', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedTinyInteger('type')->default(0)->nullable(false); // 0: ERC721, 1: ERC1155
            $table->unsignedBigInteger('poem_id')->nullable(false);
            $table->unsignedBigInteger('content_id')->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->string('hash')->nullable(false);
            $table->json('poemwiki')->nullable(false);
            // metadata
            // @see https://docs.opensea.io/docs/metadata-standards
            $table->string('external_url')->nullable(false);  // URL to poem page
            $table->string('animation_url')->nullable(true);  // URL to poem animation page
            $table->string('description')->nullable(true);    // description
            $table->string('image')->nullable(true);          // URL of nft image
            $table->longText('image_data')->nullable(true);   // Raw SVG image data, if you want to generate images on the fly (not recommended). Only use this if you're not including the image parameter.
            $table->char('background_color', 6)->nullable(true);  // Background color of the item on OpenSea. Must be a six-character hexadecimal without a pre-pended #.
            $table->string('name')->nullable(true);           // poem title
            $table->json('attributes')->nullable(true);       // attributes for the item, which will show up on the OpenSea page for the item.
            $table->index(['poem_id']);
        });

        Schema::create('transaction', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedTinyInteger('nft_id')->default(0)->nullable(false); // 0: poem gold, other: nft id
            $table->unsignedTinyInteger('action')->default(0)->nullable(false);
            $table->unsignedBigInteger('from_user_id')->nullable(false);
            $table->unsignedBigInteger('to_user_id')->nullable(false);
            $table->decimal('amount', 27, 18)->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->index(['to_user_id', 'from_user_id']);
            $table->index(['nft_id']);
        });

        Schema::create('balance', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedTinyInteger('nft_id')->default(0)->nullable(false); // 0: poem gold, other: nft id
            $table->decimal('amount', 27, 18)->nullable(false);
            $table->dateTime('created_at')->useCurrent()->nullable(false);
            $table->dateTime('updated_at')->useCurrent()->nullable(false);
            $table->index(['nft_id']);
            $table->index(['user_id']);
        });
    }

    public function down() {
        Schema::dropIfExists('nft');
        Schema::dropIfExists('transaction');
        Schema::dropIfExists('balance');
    }
}
