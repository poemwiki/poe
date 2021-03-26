<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use BorderCloud\SPARQL\SparqlClient;
use Illuminate\Support\Facades\DB;

class initialLanguage extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'language:initial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'initial language table from json file';

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

        $languages = json_decode(file_get_contents(storage_path('/language.json'))); // language_written.json

        // group by entity_id
        $entities = [];
        $translations = [];
        foreach ($languages as $key => $record) {
            if(!isset($entities[$record->code])) {
                $entities[$record->code] = [];
            }

            $entities[$record->code] = [
                'locale' => $record->code,
                'wikidata_id' => $record->entity_id,
                'name_cn' => $record->itemLabel_zh ?? ''
            ];
            $translations[$record->code][$record->lang] = $record->val;
            if($record->lang === $record->code) {
                $entities[$record->code]['name'] = $record->val;
            }
        }

        foreach ($entities as $code => $entity) {
            $insertData = $entity;
            $insertData['name'] = $entity['name'] ?? '';
            $insertData['name_lang'] = json_encode((object)$translations[$code]);
            DB::table('language')->insert($insertData);
        }

        return 0;
    }
}



