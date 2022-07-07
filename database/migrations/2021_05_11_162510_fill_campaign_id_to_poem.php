<?php

use App\Models\Poem;
use App\Models\Campaign;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FillCampaignIdToPoem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ([2,3,4,5,6] as $campaignId) {
                $campagin = Campaign::find($campaignId);
                $tagId = $campagin->tag->id;
                $poemIds = Poem::select('id')->whereHas('tags', function($q) use ($tagId) {
                    $q->where('tag.id', '=', $tagId);
                })->pluck('id');

                Poem::whereIn('id', $poemIds)->update(['campaign_id' => $campagin->id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poem', function (Blueprint $table) {
            //
        });
    }
}
