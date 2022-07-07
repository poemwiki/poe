<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAuthorUploadUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('update author a set upload_user_id = (
SELECT l.causer_id from activity_log l
WHERE l.subject_id=a.id and l.causer_type="App\\User" and l.subject_type="App\\Models\\Author"
AND l.description="created"
)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
