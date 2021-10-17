<?php

namespace App\Console\Alias;

use App\Models\Author;
use App\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ImportFromAuthor extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alias:importFromAuthor {fromId?} {toId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Import alias from author.'\$->name_lang'";

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
        $fromId = $this->argument('fromId') ?: 101247956;
        $toId   = $this->argument('toId') ?: 101247956;

        $authorId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify author id?', ['yes', 'no'], 0) === 'yes') {
                $authorId = $this->ask('Input author id: ');
            }
        }

        if (is_numeric($authorId)) {
            $author = Author::where('id', $authorId)->get();
            $this->_process($author);

            return 0;
        }

        // add alias, if author exists, set alias.author_id to author id
        $this->importFromAuthor($fromId, $toId);

        return 0;
    }

    private function _process(Collection $authors) {
        foreach ($authors as $poet) {
            $names = json_decode($poet->getAttributes()['name_lang']);

            collect($names)->each(function ($name, $locale) use ($poet) {
                if (!is_string($name) or $name == '') {
                    return;
                }

                // TODO select language that has name(enabled)
                $language = Language::where('locale', '=', $locale)->first();
                // insert alias data into alias
                $insert = [
                    'locale'      => $locale,
                    'language_id' => $language ? $language->id : null,
                    'name'        => $name,
                    'wikidata_id' => $poet->wikidata_id ?? null,
                    'author_id'   => $poet->id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                DB::table('alias')->updateOrInsert([
                    'author_id' => $poet->id,
                    'name'      => $name
                ], $insert);

                $this->info("Label added to alias: author.id: $poet->id \t $name");
            });
        }
    }

    public function importFromAuthor($fromId = 0, $toId = 0) {
        Author::where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId],
        ])->orderBy('id')->chunk(400, function ($authors) {
            $this->_process($authors);
        });
    }
}
