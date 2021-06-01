<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplyToReview extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('review', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_id')->nullable(true);
            $table->unsignedInteger('like')->nullable(true)->change();
        });
        Schema::table('poem', function (Blueprint $table) {
            $table->string('weapp_url')->nullable(true);
        });
        Schema::table('campaign', function (Blueprint $table) {
            $table->json('weapp_url')->nullable(true);
        });
        Schema::create('likes', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('likeable_id')->index()->nullable(false);
            $table->string('likeable_type');
            $table->dateTime('created_at')->default(DB::raw('NOW()'))->nullable(false);
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('likes');
        Schema::table('review', function (Blueprint $table) {
            $table->dropColumn('reply_id');
            // $table->dropColumn('like');
        });
        Schema::table('poem', function (Blueprint $table) {
            $table->dropColumn('weapp_url');
        });
        Schema::table('campaign', function (Blueprint $table) {
            $table->dropColumn('weapp_url');
        });
    }
}
