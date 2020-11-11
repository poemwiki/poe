<?php

namespace App\Console\Commands;

use App\Models\Wikidata;
use Illuminate\Console\Command;
use BorderCloud\SPARQL\SparqlClient;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class initialAlias extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:initialAlias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'initial alias table, initial wikidata.data, initial poem.poet_id & poem.translator_id';
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
        // YOU NEED TO IMPORT wikidata_poem from json file FIRST, manually

        // and execute artisan wiki:initialAuthor


        // add alias, alias.author_id
        // $this->importAliasFromWikiData(101247956, 101247956);

        // match poem.poet to author.name_lang or alias.name, update poem.poet_id poem.translator_id
        $this->matchAliasFor('poet', 0, 999999);
        $this->matchAliasFor('translator', 0, 999999);


        return 0;
    }


    public function matchAliasFor($field, $fromId = 0, $toId = 9999999) {
        // $idField = $field.'_id';
        $wikidataIDField = $field.'_wikidata_id';
        $poems = DB::table('poem')->whereBetween('id', [$fromId, $toId])
            ->whereNotNull($field)->whereNull($wikidataIDField)->get();

        Log::info('Need update poem.' . $wikidataIDField . ': ' . count($poems));

        foreach ($poems as $poem) {
            $authorName = $poem->$field;

            echo "Matching poem id=$poem->id $field $authorName " . PHP_EOL;

            $wikiEntry = DB::table('wikidata')->select('id')->whereRaw(
                "JSON_SEARCH(label_lang, 'one', JSON_UNQUOTE(:name))",
                ['name' => $authorName]
            )->first();

            if(empty($wikiEntry)) {
                $wikiEntry = DB::table('wikidata')->select('id')->whereRaw(
                    "JSON_SEARCH(`data`->'$.labels', 'one', JSON_UNQUOTE(:name))",
                    ['name' => $authorName]
                )->first();
            }
            if ($wikiEntry) {
                DB::table('poem')->where('id', $poem->id)
                    ->update([
                        $wikidataIDField => $wikiEntry->id
                    ]);
                echo ("poem.$wikidataIDField updated to $wikiEntry->id : poem_id: $poem->id \t $authorName");
                Log::info("poem.$wikidataIDField updated to $wikiEntry->id : poem_id: $poem->id \t $authorName");
                continue;
            }

            $alia = DB::table('alias')->where('name', $authorName)->first();
            if ($alia) {
                DB::table('poem')->where('id', $poem->id)
                    ->update([
                        $wikidataIDField => $alia->wikidata_id
                    ]);
                echo ("poem.$wikidataIDField updated to $alia->wikidata_id : poem_id: $poem->id \t $authorName \t alias_id $alia->id \t $alia->locale");
                Log::info("poem.$wikidataIDField updated to $alia->wikidata_id : poem_id: $poem->id \t $authorName \t alias_id $alia->id \t $alia->locale");
                continue;
            }

        }
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

