<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('alias', function (Blueprint $table) {
            // Drop foreign key constraint on wikidata_id if it exists
            try {
                $table->dropForeign(['wikidata_id']);
            } catch (\Exception $e) {
                // ignore if the foreign key does not exist
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('alias', function (Blueprint $table) {
            // Recreate foreign key constraint (restrict semantics)
            $table->foreign('wikidata_id')
                ->references('id')
                ->on('wikidata')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }
};

