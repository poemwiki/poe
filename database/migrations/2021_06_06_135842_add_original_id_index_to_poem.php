<?php

use App\Models\Poem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalIdIndexToPoem extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('poem', function (Blueprint $table) {
            $table->bigIncrements('id')->change();

            $table->index('original_id', 'original_id_idx');

            DB::transaction(function () {
                DB::statement('UPDATE poem SET original_id=id WHERE is_original=1 and original_id IS NULL');
                DB::statement('UPDATE poem SET original_id=0 WHERE is_original=0 and original_id IS NULL');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('poem', function (Blueprint $table) {
            $table->dropIndex('original_id_idx');
        });
        DB::transaction(function () {
            DB::statement('UPDATE poem SET original_id=NULL WHERE is_original=1 and original_id=id');
            DB::statement('UPDATE poem SET original_id=NULL WHERE is_original=0 and original_id=0');
        });
    }
}
