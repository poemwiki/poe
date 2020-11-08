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
        $this->importAliasFromWikiData(1376522, 4255778);

        // match poem.poet to alias, update poem.poet_id poem.translator_id
        // $this->matchAliasForPoem(0, 999999);
        // if poem.poet not matched any alias?

        // add poem.poet_id
        // add poem.translator_id

        return 0;
    }

    public function matchAliasForPoem($fromId = 0, $toId = 0) {
        $poems = DB::table('poem')->whereBetween('id', [$fromId, $toId])
            ->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereNull('poet_id')
                        ->whereNotNull('poet');
                })->orWhere(function (Builder $query) {
                    $query->whereNull('translator_id')
                        ->whereNotNull('translator');
                });
            })->get();


        Log::info('Need update author id: ' . count($poems));

        foreach ($poems as $poem) {
            $poet = $poem->poet;
            $translator = $poem->translator;

            if ($poet) {
                $poetAlia = DB::table('alias')->where('name', $poet)->first();
                if ($poetAlia) {
                    DB::table('poem')->where('id', $poem->id)
                        ->update([
                            'poet_id' => $poetAlia->id
                        ]);
                    Log::info("poem.poet_id updated: poem_id: $poem->id \t $poet \t alias_id $poetAlia->id \t $poetAlia->name \t $poetAlia->locale");
                }
            }

            if ($translator) {
                $translatorAlia = DB::table('alias')->where('name', $translator)->first();
                if ($translatorAlia) {
                    DB::table('poem')->where('id', $poem->id)
                        ->update([
                            'poet_id' => $translatorAlia->id
                        ]);
                    Log::info("poem.translator_id updated: poem_id: $poem->id \t $translator \t $translatorAlia->id \t $translatorAlia->name \t $translatorAlia->locale");
                }
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

