<?php

namespace App\Console\Author;

use App\Models\Author;
use App\Models\MediaFile;
use App\Repositories\AuthorRepository;
use App\Services\Tx;
use App\Services\Weapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeappCode extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'author:weapp {fromId?} {toId?} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weapp code image and save it to Tencent COS.';
    /**
     * @var AuthorRepository
     */
    private $authorRepository;
    /**
     * @var TX
     */
    private $TXCloud;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AuthorRepository $authorRepo) {
        parent::__construct();

        $this->authorRepository = $authorRepo;
        $this->TXCloud          = new Tx();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $fromId = $this->argument('fromId') ?: 0;
        $toId   = $this->argument('toId') ?: 0;
        $force  = $this->option('force');

        DB::enableQueryLog();
        $query = Author::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ]);

        if (!$force) {
            $query->whereNull('short_url');
        }

        $authors = $query
         ->orderBy('id')->get();

        try {
            $authors->each(function ($author) {
                $this->process($author);
                sleep(3);
            });
        } catch (\Exception $e) {
            $this->error('error while put file to COS ' . $e->getMessage());

            return -1;
        }

        return 0;
    }

    public function process(Author $author): int {
        $TXCloud = $this->TXCloud;

        $tmpFilePath = $this->fetchAppCodeImg($author->id);

        $format      = TX::SUPPORTED_FORMAT['webp'];
        $fileID      = config('app.avatar.author_path') . '/' . $author->fakeId . '-weapp.' . $format;
        $fileContent = file_get_contents($tmpFilePath);
        $result      = $TXCloud->upload($fileID, $fileContent, $format);

        $image = $result['Data']['ProcessResults']['Object'][0];
        if (isset($image['Location'])) {
            $objectUrlWithoutSign   = 'https://' . $image['Location'];

            // Tencent cos client has set default timezone to PRC
            date_default_timezone_set(config('app.timezone', 'UTC'));
            $author->short_url = $objectUrlWithoutSign . '?v=' . now()->timestamp;
            $author->save();

            $this->authorRepository->saveAuthorMediaFile($author, MediaFile::TYPE['weapp_code'], $image['Key'],
                    md5($fileContent), $format, $image['Size']);
        }

        logger()->info('uploaded:', $result);

        return 0;
    }

    private function fetchAppCodeImg(int $id, bool $force = false, string $appCodeFileName = 'weapp.jpg') {
        $relativeStoreDir = 'app/public/tmp-author-weapp/' . $id;
        $appCodeImgDir    = storage_path($relativeStoreDir);

        return (new Weapp())->fetchAppCodeImg($id, $appCodeImgDir, 'pages/author/author', $force, $appCodeFileName);
    }
}
