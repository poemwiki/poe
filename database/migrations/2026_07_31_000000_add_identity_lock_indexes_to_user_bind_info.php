<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('user_bind_info', function (Blueprint $table) {
            $table->index(
                ['open_id_crc32', 'open_id', 'bind_ref', 'bind_status'],
                'user_bind_info_active_openid_lock_index'
            );
        });
    }

    public function down(): void {
        Schema::table('user_bind_info', function (Blueprint $table) {
            $table->dropIndex('user_bind_info_active_openid_lock_index');
        });
    }
};
