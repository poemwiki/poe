<?php

namespace App\Console\Wiki;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class Translate extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:translate {fromId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'translate wikidata from wikidata_poet.';

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
        // YOU NEED TO IMPORT wikidata_poet from JSON file FIRST, manually.

        $fromId = $this->argument('fromId') ?: 87902;

        $wikidataId = null;
        if (App::runningInConsole()) {
            $needID = $this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0);
            if($needID) {
                $wikidataId = $this->ask('Input wikidata id: ');
            }
        }

        // run this for first time, and don't need run it second time
        $this->translateFromWikiDataPoem($fromId, $wikidataId);

        return 0;
    }

    public function translateFromWikiDataPoem($fromId = 0, $wikidataId = null) {
        $criteria = ['id', '>=', $fromId];
        if (is_numeric($wikidataId)) {
            $criteria = ['wikidata_id', '=', $wikidataId];
        }
        $poets = DB::table('wikidata_poet')->where([
            $criteria
        ]);

        foreach ($poets->get() as $poet) {
            $insert = [
                'id' => $poet->wikidata_id,
                'type' => '0',
                // TODO remove label_lang after import all data.labels into alias
                'label_lang' => json_encode((object)['zh-CN' => $poet->label_zh, 'en' => $poet->label_en]),
            ];

            DB::table('wikidata')->updateOrInsert(['id' => $poet->wikidata_id], $insert);
        }
    }
}

