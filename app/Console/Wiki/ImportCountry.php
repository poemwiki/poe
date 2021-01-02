<?php

namespace App\Console\Wiki;

use App\Models\Poem;
use App\Models\Wikidata;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportCountry extends Command {
    const CHUNK_SIZE = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:importCountry {fromId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve newest country data from wikidata entity API, and update wikidata table.';
    protected $entityApiUrl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=';

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
        // YOU NEED TO IMPORT wikidata_poet from JSON file
        // then run wiki:translate to initial wikidata

        $fromId = $this->argument('fromId') ?? 0;

        $wikidataId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $wikidataId = $this->ask('Input wikidata id: ');
            }
        }

        if (is_numeric($wikidataId)) {
            return $this->_process(collect($wikidataId));
        }

        $this->import($fromId);

        return 0;
    }

    public function import(int $fromId = 0) {
        $poets = Wikidata::query()->where([
            ['id', '>=', $fromId],
            ['type', '=', Wikidata::TYPE['country']]
        ])->orderBy('id');

        $bar = $this->output->createProgressBar($poets->count());
        $bar->start();

        $poets->chunk(self::CHUNK_SIZE, function (Collection $poets) use ($bar) {
            $ids = $poets->pluck('id');
            $continue = $this->_process($ids);
            if ($continue) {
                $bar->advance(self::CHUNK_SIZE);
                return true;
            }
            $bar->finish();
            return false;
        });

        $bar->finish();
    }

    /**
     * @param Collection $poets
     * @return bool
     */
    private function _process(Collection $ids): bool {
        $qIds = $ids->map(function ($id) {
            return 'Q' . $id;
        })->implode('|');
        $options = config('app.env') === 'production' ? [] : [
            'http' => 'tcp://localhost:1087',
            'https' => 'tcp://localhost:1087',
        ];


        $this->info('Fetching: ' . $this->entityApiUrl . $qIds);
        $response = Http::withOptions($options)->withHeaders([
            'Accept' => 'application/sparql-results+json',
            'User-Agent' => 'PoemWiki-bot/0.1 (https://poemwiki.org; poemwiki@126.com) PHP/' . PHP_VERSION, // TODO adjust this; see https://w.wiki/CX6
        ])->timeout(30)->retry(5, 10)->get($this->entityApiUrl . $qIds);
        $body = (string)$response->getBody();
        $data = json_decode($body);

        if (!$data->success) return false;

        foreach ($ids as $id) {
            $entityId = 'Q' . $id;
            $entity = $data->entities->$entityId;
            $insert = [
                'id' => $id,
                'type' => Wikidata::TYPE['country'],
                'data' => json_encode($entity)
            ];
            DB::table('wikidata')->updateOrInsert(['id' => $id], $insert);
        }
        return true;
    }

}

