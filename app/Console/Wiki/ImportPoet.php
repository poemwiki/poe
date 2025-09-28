<?php

namespace App\Console\Wiki;

use App\Models\Wikidata;
use App\Services\WikiDataFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;


class ImportPoet extends Command {
    public const CHUNK_SIZE = 46;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wiki:importPoet {fromId?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve newest poet data from wikidata entity API, and update wikidata table.';

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

        $fromId = $this->argument('fromId') ?: 87902;

        $wikidataId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
                $wikidataId = $this->ask('Input wikidata id: ');
            }
        }

        if (is_numeric($wikidataId)) {
            $id       = (int)$wikidataId;
            $entities = WikiDataFetcher::fetchEntities([$id]);
            if (!isset($entities[$id])) {
                $this->error("Failed to fetch Wikidata entity Q{$id}.");

                return 1;
            }

            DB::table('wikidata')->updateOrInsert(
                ['id' => $id],
                ['type' => Wikidata::TYPE['poet'], 'data' => json_encode($entities[$id], JSON_UNESCAPED_UNICODE)]
            );

            $this->info("Imported Q{$id}.");

            return 0;
        }

        $this->import($fromId);

        return 0;
    }

    public function import(int $fromId = 0) {
        $poets = Wikidata::query()->where([
            ['id', '>=', $fromId],
            ['type', '=', Wikidata::TYPE['poet']],
        ])->orderBy('id');

        $bar = $this->output->createProgressBar($poets->count());
        $bar->start();

        $poets->chunk(self::CHUNK_SIZE, function (Collection $poets) use ($bar) {
            $ids      = $poets->pluck('id')->all();
            $entities = WikiDataFetcher::fetchEntities($ids);

            foreach ($ids as $id) {
                if (!isset($entities[$id])) {
                    // If any id missing from fetch result treat as failure to stop import.
                    $bar->finish();

                    return false;
                }

                DB::table('wikidata')->updateOrInsert(
                    ['id' => $id],
                    ['type' => Wikidata::TYPE['poet'], 'data' => json_encode($entities[$id], JSON_UNESCAPED_UNICODE)]
                );
            }

            $bar->advance(self::CHUNK_SIZE);

            return true;
        });

        $bar->finish();
    }
}
