<?php

namespace App\Console\Alias;

use App\Models\Wikidata;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportAlias extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alias:importAlias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import alias from wikidata.data.'\$->alias'";

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
        // YOU NEED TO IMPORT wikidata_poem from json file FIRST,
        // then run php artisan wiki:

        // and execute artisan wiki:initialAuthor


        // add alias, alias.author_id
        $this->importAliasFromWikiData(101247956, 101247956);

        return 0;
    }

    public function importAliasFromWikiData($fromId = 0, $toId = 0) {
        $poets = DB::table('wikidata')->where([
            ['type', '=', Wikidata::TYPE['poet']],
            ['id', '>=', $fromId],
            ['id', '<=', $toId],
        ])->get();

        foreach ($poets as $poet) {
            $entity = json_decode($poet->data);

            foreach ($entity->aliases as $locale => $items) {
                // insert alias data into alias
                $author = DB::table('author')->select('id')->where('wikidata_id', $poet->id)->first();
                $language = DB::table('language')->select('id')->where('locale', $locale)->first();

                foreach ($items as $item) {
                    $insert = [
                        'name' => $item->value,
                        'locale' => $locale,
                        'wikidata_id' => $poet->id,
                        'author_id' => $author->id,
                        'language_id' => $language ? $language->id : null,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ];
                    DB::table('alias')->updateOrInsert([
                        'wikidata_id' => $poet->id,
                        'locale' => $locale,
                        'name' => $item->value,
                    ], $insert);
                    Log::info("Alias added: wikidata_id: $poet->id \t $locale \t $item->value");
                }
            }
        }
    }
}

