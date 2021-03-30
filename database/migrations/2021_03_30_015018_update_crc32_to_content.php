<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCrc32ToContent extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('content', function (Blueprint $table) {
            DB::statement('UPDATE `content` set `full_hash_crc32`=CRC32(`full_hash`)');
            DB::statement('UPDATE `content` set `hash_crc32`=CRC32(`hash`)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('content', function (Blueprint $table) {
            DB::statement('UPDATE `content` set `full_hash_crc32`=0');
            DB::statement('UPDATE `content` set `hash_crc32`=0');
        });
    }
}
