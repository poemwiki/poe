<?php

namespace App\Console\Commands;

use App\Models\Poem;
use App\Models\Wikidata;
use Illuminate\Console\Command;
use BorderCloud\SPARQL\SparqlClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        // YOU NEED TO fill poem.poet_wikidata_id and poem.translator_wikidata_id FIRST,
        // by initialAlias command matchAliasFor()

        // import from wikidata. it's too slow and not necessary. DEPRECATED
        // $this->importAuthorFromWikiData(0);//9334924);

        // import author only for who has related poem
        $this->importAuthorFromPoem('poet', 0);
        $this->importAuthorFromPoem('translator', 0);

        // if poem.poet not matched any alias, create a author for it
        // $this->createAuthorFor('poet', 0, 999999);
        // $this->createAuthorFor('translator', 0, 999999);

        return 0;
    }

    public function createAuthorFor($field, $fromId = 0, $toId = 9999999) {
        // $idField = $field.'_id';
        // $poems = DB::table('poem')->whereBetween('id', [$fromId, $toId])
        //     ->whereNotNull($field)->whereNull($idField)->get();
    }

    public function importAuthorFromPoem($field, $fromId = 0) {
        $idField = $field . '_id';
        $wikiIDField = $field . '_wikidata_id';

        Poem::query()->where('id', '>=', $fromId)
            // TODO should add ->whereNull($idField)
            ->whereNotNull($wikiIDField)->orderBy('id')->chunk(46, function ($poems) use ($idField, $wikiIDField) {

            $ids = $poems->map(function ($poem) {
                return 'Q' . $poem->id;
            })->implode('|');
            $options = config('app.env') === 'production' ? [] : [
                'http' => 'tcp://localhost:1087',
                'https' => 'tcp://localhost:1087',
            ];

            Log::info('Fetching: ' . $this->entityApiUrl . $ids);
            $response = Http::withOptions($options)->timeout(30)->retry(5, 10)->get($this->entityApiUrl . $ids);
            $body = (string)$response->getBody();
            $data = json_decode($body);

            if (!$data->success) return false;

            foreach ($poems as $poem) {
                $entityId = 'Q' . $poem->$wikiIDField;
                $this->_processEntity($poem, $idField, $wikiIDField, $data->entities->$entityId);
            }
            return true;
        });
    }

    /**
     * @param Poem $poem
     * @param $data
     */
    private function _processEntity(Poem $poem, $idField, $wikiIDField, $entity): void {
        // write poet detail data into wikidata.data
        DB::table('wikidata')->where('id', $poem->$wikiIDField)
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
                if (!isset($image->mainsnak->datavalue->value)) {
                    continue;
                }
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
            'wikidata_id' => $poem->$wikiIDField,
            'wikipedia_url' => json_encode($entity->sitelinks),
            'describe_lang' => json_encode((object)$descriptionLang),
            "created_at" => now(),
            "updated_at" => now(),
        ];
        DB::table('author')->updateOrInsert(['wikidata_id' => $poem->$wikiIDField], $insert);

        $author = DB::table('author')->where(['wikidata_id' => $poem->$wikiIDField])->first();

        echo $author->id . 'added to author.' . PHP_EOL;
        echo "update poem id $poem->id .$idField to $author->id" . PHP_EOL;

        $poem->update([$idField => $author->id]);
    }
}

