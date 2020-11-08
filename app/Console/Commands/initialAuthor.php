<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use BorderCloud\SPARQL\SparqlClient;
use Illuminate\Support\Facades\DB;

class initialAuthor extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:initialAuthor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'initial author and alias table, initial wikidata.data, initial poem.poet_id & poem.translator_id';
    protected $entityApiUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=Q';

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
        // fill wikidata with wikidata_poem
        $this->translateFromWikiDataPoem();


        // import author
        $this->importAuthorFromWikiData();

        // add author.describe_lang name_lang wikipedia_url

        // add alias, alias.author_id

        // match poem.poet to alias to author_id,
        // if poem.poet not matched any alias?

        // add poem.poet_id
        // add poem.translator_id

        return 0;
    }

    public function getWiki($poet) {
        $endpoint = "https://query.wikidata.org/sparql";
        $sp_readonly = new SparqlClient();
        $sp_readonly->setEndpointRead($endpoint);
        $q = <<<EOD
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX wd: <http://www.wikidata.org/entity/>
select  ?value
where {
        wd:Q41861 skos:altLabel ?value .
FILTER (langMatches(lang(?value), "fr"))
      }
EOD;
        $rows = $sp_readonly->query($q, 'rows');
        $err = $sp_readonly->getErrors();
        if ($err) {
            print_r($err);
            throw new Exception(print_r($err, true));
        }

        foreach ($rows["result"]["variables"] as $variable) {
            printf("%-20.20s", $variable);
            echo '|';
        }
        echo "\n";

        foreach ($rows["result"]["rows"] as $row) {
            foreach ($rows["result"]["variables"] as $variable) {
                printf("%-20.20s", $row[$variable]);
                echo '|';
            }
            echo "\n";
        }
    }

    public function translateFromWikiDataPoem() {
        $poets = DB::table('wikidata_poet')->select()->get();
        foreach ($poets as $poet) {
            $insert = [
                'id' => $poet->wikidata_id,
                'type' => '0',
                'label_lang' => json_encode((object)['zh-CN' => $poet->label_zh, 'en' => $poet->label_en]),
                // 'data' => json_encode()
            ];
            DB::table('wikidata')->insert($insert);
        }
    }

    public function importAuthorFromWikiData() {
        // write poet detail data into wikidata.data
        $poets = DB::table('wikidata')->where(['type' => 0])->get();
        foreach ($poets as $poet) {
            $poets = DB::table('wikidata_poet')->select()->get();
            foreach ($poets as $poet) {
                $res = file_get_contents($this->entityApiUrl . $poet->id);
                $data = json_decode($res);
                if (!$data->success) continue;

                // DB::table('wikidata')->
            }

            die();
            $insert = [
                'id' => $poet->wikidata_id,
                'name_lang' => $poet->label_lang,
                'wiki_data_id' => $poet->id,
            ];
            DB::table('wikidata')->insert($insert);
        }
    }
}

