<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Poem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class initialAuthor extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'author:import {fromId?} {toId?} {--poem_id=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update poem.poet_id & poem.translator_id by poet_wikidata_id & translator_wikidata_id.
        This command will retrieve data from wikidata table, and update author table.';
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
        // Update poem.poet_id & poem.translator_id by poet_wikidata_id & translator_wikidata_id.
        // author only for who has related poem


        $fromId = $this->argument('fromId') ?? 0;
        $toId = $this->argument('toId') ?? 0;

        $force = $this->option('force');
        $poemId = $this->option('poem_id');
        if (App::runningInConsole() && !$this->option('poem_id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $poemId = $this->ask('Input wikidata id: ');
            }
        }


        if (is_numeric($poemId)) {
            $this->importAuthorFromPoem('poet', $poemId, $poemId, $force);
            $this->importAuthorFromPoem('translator', $poemId, $poemId, $force);
            return 0;
        }

        $this->importAuthorFromPoem('poet', $fromId, $toId, $force);
        $this->importAuthorFromPoem('translator', $fromId, $toId, $force);

        // if poem.poet not matched any alias, create a author for it
        // $this->createAuthorFor('poet', 0, 999999);
        // $this->createAuthorFor('translator', 0, 999999);

        return 0;
    }

    public function importAuthorFromPoem(string $field, int $fromId = 0, int $toId = 0, $force = false) {
        $idField = $field . '_id';
        $wikiIDField = $field . '_wikidata_id';
        $relation = $field . 'ThroughWikidata';
        $wikidataRelation = $field . 'Wikidata';

        $query = Poem::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ])
            ->whereNotNull($wikiIDField);

        if(!$force) {
            $query = $query->whereNull($idField);
        }

        $poems = $query->orderBy('id')->get();

        $poems->each(function (Poem $poem) use ($idField, $wikiIDField, $relation, $wikidataRelation) {
            // if exists author has same wikidata_id
            if($poem->$relation) {
                $this->info("Get author $relation from wikidata_id");
                $this->_setPoemAuthorId($poem, $idField, $poem->$relation->id);
                return;
            }

            // if no exists author related, create one
            if($poem->$wikidataRelation) {
                $entity = json_decode($poem->$wikidataRelation->data);
                $author = $this->_storeToAuthor($poem, $idField, $wikiIDField, $entity);

                $this->_setPoemAuthorId($poem, $idField, $author->id);
                return;
            }
            $this->error("Poem id: $poem->id, not found $wikidataRelation relation by $wikiIDField $poem->$wikiIDField");

        });
    }

    /**
     * @param Poem $poem
     * @param string $idField
     * @param string $wikiIDField
     * @param $entity
     */
    private function _storeToAuthor(Poem $poem, string $idField, string $wikiIDField, object $entity) {

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

        // insert or update poet detail data into author
        $insert = [
            'name_lang' => $authorNameLang,         // Don't json_encode translatable attributes
            'pic_url' => $picUrl ? json_encode($picUrl) : null,
            'wikidata_id' => $poem->$wikiIDField,
            'wikipedia_url' => json_encode($entity->sitelinks),
            'describe_lang' => $descriptionLang,    // Don't json_encode translatable attributes
            "created_at" => now(),
            "updated_at" => now(),
        ];
        $author = Author::updateOrCreate(['wikidata_id' => $poem->$wikiIDField], $insert);

        $this->info("Author added or updated: {$author->id}\t{$author->name_lang}");
        return $author;
    }

    private function _setPoemAuthorId(Poem $poem, $idField, $authorId) {
        $poem->$idField = $authorId;
        $poem->save();
        $this->info("Poem {$poem->id} .{$idField} updated to {$authorId}");
    }
}

