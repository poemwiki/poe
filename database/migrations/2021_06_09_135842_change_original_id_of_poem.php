<?php

use App\Models\Poem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOriginalIdOfPoem extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedBigInteger('original_id')->nullable(false)->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('poem', function (Blueprint $table) {
            $table->unsignedInteger('original_id')->nullable(true)->default(null);
        });
    }
}
