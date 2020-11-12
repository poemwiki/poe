<?php

namespace App\Console\Alias;

use App\Models\Wikidata;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Import extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alias:import {fromId?} {toId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import alias from wikidata.data.'\$->labels'";

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
        // YOU NEED TO execute php artisan wiki:import to import wikidata.org's poet data to wikidata table

        $fromId = $this->argument('fromId') ?? 101247956;
        $toId = $this->argument('toId') ?? 101247956;

        $wikidataId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $wikidataId = $this->ask('Input wikidata id: ');
            }
        }

        if (is_numeric($wikidataId)) {
            $poet = Wikidata::where('id', $wikidataId)->get();
            $this->_process($poet);
            return 0;
        }

        // add alias, if author exists, write alias.author_id
        $this->importFromWikiData($fromId, $toId);

        return 0;
    }

    private function _process(Collection $poets) {
        foreach ($poets as $poet) {
            $entity = json_decode($poet->data);

            collect(['labels', 'alias'])->each(function ($field) use ($entity, $poet) {

                if(!isset($entity->$field)) return;

                foreach ($entity->$field as $locale => $item) {
                    // insert alias data into alias
                    $author = DB::table('author')->select('id')->where('wikidata_id', $poet->id)->first();
                    $language = DB::table('language')->select('id')->where('locale', $locale)->first();

                    $insert = [
                        'name' => $item->value,
                        'locale' => $locale,
                        'wikidata_id' => $poet->id,
                        'author_id' => $author ? $author->id : null,
                        'language_id' => $language ? $language->id : null,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ];
                    DB::table('alias')->updateOrInsert([
                        'wikidata_id' => $poet->id,
                        'locale' => $locale,
                        'name' => $item->value,
                    ], $insert);

                    Log::info("Label added to alias: wikidata_id: $poet->id \t $locale \t $item->value");
                }
            });
        }
    }

    public function importFromWikiData($fromId = 0, $toId = 0) {
        $poets = DB::table('wikidata')->where([
            ['type', '=', Wikidata::TYPE['poet']],
            ['id', '>=', $fromId],
            ['id', '<=', $toId],
        ])->get();
        $this->_process($poets);
    }

}

