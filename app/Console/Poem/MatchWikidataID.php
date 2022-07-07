<?php

namespace App\Console\Poem;

use App\Models\Poem;
use App\Models\Wikidata;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchWikidataID extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poem:matchWikidataID {fromId?} {toId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update poem.poet_wikidata_id & poem.translator_wikidata_id by matching poem.poet & poem.translator with wikidata labels and alias.';

    protected $entityApiUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=';
    protected $picUrlBase = 'https://upload.wikimedia.org/wikipedia/commons/';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {

        $fromId = $this->argument('fromId') ?: 0;
        $toId = $this->argument('toId') ?: 9999999;

        $poemId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $poemId = $this->ask('Input wikidata id: ');
            }
        }

        if (is_numeric($poemId)) {
            $this->matchWikidatdaIDFor('poet', $poemId, $poemId);
            $this->matchWikidatdaIDFor('translator', $poemId, $poemId);
            $this->setPoetWikidataId($poemId, $poemId); // TODO move it to end
            return 0;
        }

        // match poem.poet to author.name_lang or alias.name,
        // update poem.poet_wikidata_id poem.translator_wikidata_id
        $this->matchWikidatdaIDFor('poet', $fromId, $toId);
        $this->matchWikidatdaIDFor('translator', $fromId, $toId);
        $this->setPoetWikidataId($fromId, $toId); // TODO move it to end

        return 0;
    }

    // set poet_wikidata_id if it's original poem has
    public function setPoetWikidataId($fromId = 0, $toId = 9999999) {

        Poem::withoutEvents(function () use ($fromId, $toId) {

            $poems = Poem::whereBetween('id', [$fromId, $toId])
                ->whereNull('poet_wikidata_id')
                ->whereHas('originalPoem', function($q){
                    $q->whereNotNull('poet_wikidata_id');
                })->get();

            $poems->each(function ($poem) {
                $poem->poet_wikidata_id = $poem->originalPoem->poet_wikidata_id;
                $poem->save();
            });
        });

    }

    public function matchWikidatdaIDFor($field, $fromId = 0, $toId = 9999999) {
        // $idField = $field.'_id';
        $wikidataIDField = $field.'_wikidata_id';
        $query = DB::table('poem')->whereBetween('id', [$fromId, $toId]);
        if($fromId !== $toId) {
            $query->whereNotNull($field)->whereNull($wikidataIDField);
        }

        $poems = $query->get();
        $this->info('Need update poem.' . $wikidataIDField . ': ' . count($poems));

        foreach ($poems as $poem) {
            $authorName = $poem->$field;
            if(empty($authorName)) continue;

            $this->info("Matching poem id=$poem->id $field $authorName");

            $alia = DB::table('alias')->where('name', $authorName)->first();
            if ($alia) {
                DB::table('poem')->where('id', $poem->id)
                    ->update([
                        $wikidataIDField => $alia->wikidata_id
                    ]);
                $this->info("poem.$wikidataIDField updated to $alia->wikidata_id : poem_id: $poem->id \t $authorName \t alias_id $alia->id \t $alia->locale");
            }
        }
    }

}

