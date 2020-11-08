<?php

namespace App\Console\Commands;

use App\Models\Wikidata;
use Illuminate\Console\Command;
use BorderCloud\SPARQL\SparqlClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
    protected $description = 'initial author table';
    protected $entityApiUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=Q';
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

        // fill wikidata with wikidata_poem
        // $this->translateFromWikiDataPoem();

        // import author
        $this->importAuthorFromWikiData(160422);


        return 0;
    }


    public function translateFromWikiDataPoem($fromId = 0) {
        $poets = DB::table('wikidata_poet')->where([
            ['id', '>=', $fromId]
        ])->get();
        foreach ($poets as $poet) {
            $insert = [
                'id' => $poet->wikidata_id,
                'type' => '0',
                'label_lang' => json_encode((object)['zh-CN' => $poet->label_zh, 'en' => $poet->label_en]),
                // 'data' => json_encode()
            ];
            DB::table('wikidata')->updateOrInsert(['id' => $poet->wikidata_id], $insert);
        }
    }

    public function importAuthorFromWikiData($fromId = 0) {
        $poets = DB::table('wikidata')->where([
            ['type', '=', Wikidata::TYPE['poet']],
            ['id', '>=', $fromId],
        ])->get();

        foreach ($poets as $poet) {
            var_dump($this->entityApiUrl . $poet->id);
            $response = Http::withOptions([
                'http' => 'tcp://localhost:1087',
                'https' => 'tcp://localhost:1087',
            ])->timeout(20)->retry(3, 10)->get($this->entityApiUrl . $poet->id);
            $body = (string)$response->getBody();

            $data = json_decode($body);

            if (!$data->success) continue;

            // write poet detail data into wikidata.data
            $entityId = 'Q' . $poet->id;
            $entity = $data->entities->$entityId;
            DB::table('wikidata')->where('id', $poet->id)
                ->update(['data' => json_encode($entity)]);


            $authorNameLang = [];
            foreach ($entity->labels as $locale => $label) {
                $authorNameLang[$locale] = $label->value;
            }
            $descriptionLang = [];
            foreach ($entity->descriptions as $locale => $description) {
                $descriptionLang[$locale] = $description->value;
            }

            $picUrl = null;
            if (isset($entity->claims->P18)) {
                $P18 = $entity->claims->P18;
                foreach ($P18 as $image) {
                    $fileName = str_replace(' ', '_', $image->mainsnak->datavalue->value);
                    $ab = substr(md5($fileName), 0, 2);
                    $a = substr($ab, 0, 1);
                    $picUrl[] = $this->picUrlBase . $a . '/' . $ab . '/' . $fileName;
                }
            }
            // insert poet detail data into author
            $insert = [
                'name_lang' => json_encode((object)$authorNameLang),
                'pic_url' => $picUrl ? json_encode($picUrl) : null,
                'wikidata_id' => $poet->id,
                'wikipedia_url' => json_encode($entity->sitelinks),
                'describe_lang' => json_encode((object)$authorNameLang),
                "created_at" => now(),
                "updated_at" => now(),
            ];
            DB::table('author')->updateOrInsert(['wikidata_id' => $poet->id], $insert);

        }
    }
}

