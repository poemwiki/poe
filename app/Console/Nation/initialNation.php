<?php

namespace App\Console\Nation;

use App\Models\Author;
use App\Models\Nation as NationModel;
use App\Models\Wikidata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class initialNation extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nation:init {fromId?} {fromAuthorId?} {--author_id=} {--force} {--skip-import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update author.nation_id by wikidata_id.
        This command will retrieve data from wikidata table, and update nation table.';

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

        $fromId = $this->argument('fromId') ?? 0;

        $force = $this->option('force');
        $authorId = $this->option('author_id');
        $skipImport = $this->option('skip-import');
        $fromAuthorId = $this->argument('fromAuthorId') ?? 0;
        if (App::runningInConsole() && !$this->option('author_id')) {
            if ($this->choice('Do you wants specify author id?', ['yes', 'no'], 0) === 'yes') {
                $authorId = $this->ask('Input author id: ');
            }
        }


        if (is_numeric($authorId)) {
            if(!$skipImport) $this->import($fromId);
            $this->updateAuthor($authorId, $authorId, $force);
            return 0;
        }

        if(!$skipImport) $this->import($fromId);
        $this->updateAuthor($fromAuthorId, 9999999, $force);

        return 0;
    }

    /**
     * @param int $fromId
     * import nation data from wikidata
     */
    public function import(int $fromId = 0){
        $nations = Wikidata::query()->where([
            ['id', '>=', $fromId],
            ['type', '=', Wikidata::TYPE['country']]
        ])->orderBy('id');

        $nations->each(function (Wikidata $nation) {
            $this->_storeToNation($nation);
        });
    }

    /**
     * @param string $field
     * @param int $fromId
     * @param int $toId
     * @param bool $force
     * Update author.nation_id by wikidata_id
     */
    public function updateAuthor(int $fromId = 0, int $toId = 0, $force = false) {
        $query = Author::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ])
            ->whereNotNull('wikidata_id');

        if(!$force) {
            $query = $query->whereNull('nation_id');
        }

        $items = $query->orderBy('id')->get();

        $items->each(function (Author $item) {
            // if exists nation has same wikidata_id
            $nationWikiId = $item->getWikiDataNationId();
            if($nationWikiId) {
                $nation = NationModel::where('wikidata_id', '=', $nationWikiId)->first();

                if($nation) {
                    $this->_setAuthorNationId($item, $nation->id);
                    return;
                }
            }

            // if no exists nation related
            $this->error("Author id: $item->id(wikidata_id $item->wikidata_id), not found nation related by nation wikidata_id $nationWikiId");

            // TODO if the nationWikiId in dynasty list, update author.dynasty_id
        });
    }

    /**
     * @param Wikidata $item
     */
    private function _storeToNation(Wikidata $item) {
        $entity = json_decode($item->data);
        $nameLang = [];
        foreach ($entity->labels as $locale => $label) {
            $nameLang[$locale] = $label->value;
        }
        $descriptionLang = [];
        foreach ($entity->descriptions as $locale => $description) {
            $descriptionLang[$locale] = $description->value;
        }

        // insert or update poet detail data into author
        $insert = [
            'name_lang' => $nameLang,         // Don't json_encode translatable attributes
            'wikidata_id' => $item->id,
            'describe_lang' => $descriptionLang,    // Don't json_encode translatable attributes
            "created_at" => now(),
            "updated_at" => now(),
        ];
        $nation = NationModel::updateOrCreate(['wikidata_id' => $item->id], $insert);

        $this->info("Nation added or updated: {$nation->id}\t{$nation->name_lang}");
        return $nation;
    }

    private function _setAuthorNationId(Author $author, $nationId) {
        $author->nation_id = $nationId;
        $author->save();
        $this->info("Author {$author->id} .nation_id updated to {$nationId}");
    }
}

