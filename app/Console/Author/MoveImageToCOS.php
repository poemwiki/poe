<?php

namespace App\Console\Author;

use App\Models\Author;
use App\Models\MediaFile;
use App\Repositories\AuthorRepository;
use App\Services\Tx;
use Illuminate\Console\Command;

class MoveImageToCOS extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'author:moveImageToCOS {fromId?} {toId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'move wikidata\'s author images to COS';
    /**
     * @var AuthorRepository
     */
    private $authorRepo;
    /**
     * @var Tx
     */
    private $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AuthorRepository $authorRepo) {
        parent::__construct();

        $this->authorRepo = $authorRepo;

        $this->client = new Tx();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $fromId = $this->argument('fromId') ?: 0;
        $toId   = $this->argument('toId') ?: 0;

        $authors = Author::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ])
            ->whereNotNull('pic_url')->orderBy('id')->get();

        try {
            $authors->each(function ($author) {
                $this->process($author);
                logger()->info('process end:[author->id=' . $author->id . ']');
            });
        } catch (\Exception $e) {
            $this->error('error while put file to COS ' . $e->getMessage());

            return -1;
        }

        return 0;
    }

    public function process(Author $author): int {
        logger()->info('processing started:[author->id=' . $author->id . ']');

        $picUrls = collect($author->pic_url)->filter(function ($url) {
            return isValidPicUrl($url) && isWikimediaUrl($url);
        })
            ->values();

        foreach ($picUrls as $index => $url) {
            $pathInfo = pathinfo($url);
            $ext      = $pathInfo['extension'];

            logger()->info('fetching url:' . $url);

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->retry(3, 1)->get($url);
                if ($response->status() !== 200) {
                    continue;
                }
            } catch (\Exception $e) {
                logger()->warning('fetchImage failed:' . $e->getMessage());

                continue;
            }

            $imgContent = $response->body();

            $toFormat = TX::SUPPORTED_FORMAT['webp'];

            try {
                $MediaFile       = $this->upload($author, $imgContent, $ext, $toFormat, $index, $pathInfo['filename']);
                $picUrls[$index] = $MediaFile->path;
            } catch (\Exception $e) {
                logger()->error('uploadImage Error:' . $e->getMessage() . "\n" . $e->getTraceAsString());

                return -2;
            }

            // 获取 wikimedia 链接及版权信息，保存至 image 表
            $wikiMediaData = get_wikimedia_pic_info([
                'title' => $pathInfo['basename'],
            ]);
            if (!$wikiMediaData) {
                continue;
            }
            $wikimediaPicInfo = collect($wikiMediaData->query->pages)->first();

            if (isset($wikimediaPicInfo->imageinfo[0]->extmetadata->Artist)) {
                $MediaFile->setProp('wikimediaPicInfo', $wikimediaPicInfo->imageinfo[0]->extmetadata->Artist->value)->save();
            }
        }

        $author->pic_url = $picUrls;
        $author->save();

        return 0;
    }

    /**
     * @param string $fileID
     * @param string $fakeId
     * @param string $toFormat
     * @param int    $scropSize
     * @return array
     */
    public function scropAvatar(string $fileID, string $fakeId, string $toFormat, int $scropSize): array {
        $toFilePath = config('app.avatar.author_path') . '/' . $fakeId . '.' . $toFormat;
        $result     = $this->client->scropFile($fileID, $toFilePath, $toFormat, $scropSize, $scropSize);

        return $result['ProcessResults']['Object'][0];
    }

    /**
     * @param string $imgContent
     * @param string $ext
     * @param string $toFormat
     * @return array
     */
    public function uploadImage(string $imgContent, string $ext, string $toFormat): array {
        $md5        = md5($imgContent);
        $fileID     = config('app.cos_tmp_path') . '/' . $md5 . '.' . $ext;
        $toFileName = config('app.cos_author_path') . '/' . $md5 . '.' . $toFormat;
        $result     = $this->client->thumbnailAndUpload($fileID, $toFileName, $imgContent, $toFormat);

        return [$fileID, $result['Data']['ProcessResults']['Object'][0]];
    }

    /**
     * @param Author $author
     * @param string $imgContent
     * @param string $ext
     * @param string $toFormat
     * @param array  $urls
     * @param $index
     * @param $pathInfo
     * @return MediaFile
     */
    protected function upload(Author $author, string $imgContent, string $ext, string $toFormat, $index, $name): MediaFile {
        $result                    = $this->uploadImage($imgContent, $ext, $toFormat);
        list($fileID, $compressed) = $result;
        $this->client->deleteObject($fileID);

        $compressedKey = $compressed['Key'];
        logger()->info('uploadImage finished:[author->id=' . $author->id . ']', $result);

        $MediaFile = $this->authorRepo->saveAuthorMediaFile($author, MediaFile::TYPE['image'], $compressedKey, $name, $toFormat, $compressed['Size']);

        if ($index === 0 && !$author->avatar) {
            $scropSize      = min(600, $compressed['Width'], $compressed['Height']);
            $avatarResult   = $this->scropAvatar($compressedKey, $author->fakeId, $toFormat, $scropSize);
            $author->avatar = $avatarResult['Key'];
            $author->save();

            $this->authorRepo->saveAuthorMediaFile($author, MediaFile::TYPE['avatar'], $avatarResult['Key'], $name, $toFormat, $avatarResult['Size'], $MediaFile->id);
        }

        return $MediaFile;
    }
}
