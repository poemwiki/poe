<?php

namespace App\Console\Poem;

use App\Models\Author;
use App\Models\Poem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ShortUrl extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poem:shortUrl {fromId?} {toId?} {--id=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update poem.short_url.
        This command will retrieve a short url from api.xiaomark.com';

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
        $toId = $this->argument('toId') ?? 0;

        $force = $this->option('force');
        $poemId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $poemId = $this->ask('Input wikidata id: ');
            }
        }


        if (is_numeric($poemId)) {
            $this->updateShortUrl($poemId, $poemId, $force);
            return 0;
        }

        $this->updateShortUrl($fromId, $toId, $force);

        return 0;
    }

    public function updateShortUrl(int $fromId = 0, int $toId = 0, $force = false) {
        $query = Poem::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ]);

        if(!$force) {
            $query = $query->whereNull('short_url');
        }

        $query->orderBy('id')->get()->each(function (Poem $poem) {
            $short = short_url($poem->url);

            if($short === $poem->url) {
                return;
            }

            $poem->short_url = $short;
            $poem->save();
            $this->info("Poem id: $poem->id, short_url: ", $short);

        });
    }

}

