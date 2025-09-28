<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add a decimal field for ordering (allows values like 1, 1.1, 1.2)
        Schema::table('language', function (Blueprint $table) {
            // non-nullable decimal with two decimal places
            $table->decimal('sort_order', 8, 2)->after('locale');
        });

        // Ensure there are no duplicate locales before adding a unique constraint.
        $duplicates = DB::table('language')
            ->select('locale', DB::raw('count(*) as cnt'))
            ->groupBy('locale')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates->pluck('locale')->join(', ');
            throw new \RuntimeException("Cannot add unique constraint on `locale` because duplicate locales exist: {$list}. Please deduplicate them before running this migration.");
        }

        Schema::table('language', function (Blueprint $table) {
            $table->unique('locale', 'language_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('language', function (Blueprint $table) {
            $table->dropUnique('language_locale_unique');
            $table->dropColumn('sort_order');
        });
    }
};
