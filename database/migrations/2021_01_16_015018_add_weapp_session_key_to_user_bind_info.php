<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeappSessionKeyToUserBindInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_bind_info', function (Blueprint $table) {
            $table->string('weapp_session_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_bind_info', function (Blueprint $table) {
            $table->dropColumn('weapp_session_key');
        });
    }
}
