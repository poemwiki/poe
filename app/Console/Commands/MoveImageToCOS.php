<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\MediaFile;
use App\Services\Tx;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class MoveImageToCOS extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MoveImageToCOS {fromId?} {toId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'move wikidata\'s author images to COS';
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    private $disk;
    /**
     * @var \Qcloud\Cos\Client
     */
    private $cosClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->disk = Storage::disk('cosv5');

        $region    = config('filesystems.disks.cosv5.region');

        $this->cosClient = new \Qcloud\Cos\Client([
            'region'      => $region,
            'credentials' => [
                'secretId'  => config('filesystems.disks.cosv5.credentials.secretId'),
                'secretKey' => config('filesystems.disks.cosv5.credentials.secretKey')
            ]
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $fromId = $this->argument('fromId') ?: 0;
        $toId   = $this->argument('toId') ?: 0;

        // create a file
        $path           = 'avatar/';
        $fileName       = 'Elizabeth_Acevedo.jpg';
        $publicFile     = public_path('images/Elizabeth_Acevedo.jpg');
        if (!file_exists($publicFile)) {
            return -1;
        }

        $authors = Author::query()->where([
            ['id', '>=', $fromId],
            ['id', '<=', $toId]
        ])
            ->whereNotNull('pic_url')->orderBy('id')->get();

        try {
            $authors->each(function ($author) {
                $this->process($author);
            });
        } catch (\Exception $e) {
            dd($e);
            $this->error('error while put file to COS ' . $e->getMessage());

            return -1;
        }

        return 0;
    }

    public function process(Author $author): int {
        $urls = [];

        $picUrls = collect($author->pic_url)->filter(function ($url) {
            return isValidPicUrl($url) && isWikimediaUrl($url);
        })
            ->values();

        foreach ($picUrls as $index => $url) {
            $options = config('app.env') === 'production' ? [] : [
                'proxy' => 'http://127.0.0.1:1087',
                // 'https' => 'tcp://127.0.0.1:1087'
            ];

            $pathInfo = pathinfo($url);
            $ext      = $pathInfo['extension'];

            $response = \Illuminate\Support\Facades\Http::withOptions($options)->timeout(3)->retry(1, 1)->get($url);
            if ($response->status() !== 200) {
                return -1;
            }
            $imgContent = $response->body();

            $client   = new Tx();
            $toFormat = TX::SUPPORTED_FORMAT['webp'];

            try {
                $result                    = $this->uploadImage($author, $imgContent, $ext, $toFormat, $client);
                list($fileID, $compressed) = $result;
                $client->deleteObject($fileID);

                $compressedKey = $compressed['Key'];
                logger()->info('uploadImage finished:', $result);
            } catch (\Exception $e) {
                logger()->error('uploadImage Error:' . $e->getMessage() . "\n" . $e->getTraceAsString());

                return -2;
            }

            $urls[$index] = $client->getUrl($compressedKey);

            $MediaFile = $this->saveAuthorMediaFile($author, MediaFile::TYPE['image'], $compressedKey, $pathInfo['filename'], $toFormat, $compressed['Size']);

            if ($index === 0) {
                $scropSize      = min(600, $compressed['Width'], $compressed['Height']);
                $avatarResult   = $this->scropAvatar($compressedKey, $author->fakeId, $toFormat, $scropSize, $client);
                $author->avatar = 'https://' . $avatarResult['Location'];
                $author->save();

                $this->saveAuthorMediaFile($author, MediaFile::TYPE['avatar'], $avatarResult['Key'], $pathInfo['filename'], $toFormat, $avatarResult['Size'], $MediaFile->id);
            }

            // 获取 wikimedia 链接及版权信息，保存至 image 表
            $wikimediaPicInfo = collect(get_wikimedia_pic_info([
                'title' => $pathInfo['basename'],
            ])->query->pages)->first();

            $MediaFile->setProp('wikimediaPicInfo', $wikimediaPicInfo->imageinfo[0]->extmetadata->Artist->value)->save();
        }

        if (!empty($urls)) {
            $author->pic_url = $urls;
            $author->save();
        }

        return 0;
    }

    /**
     * @param $fileID
     * @param $fakeId
     * @param string $toFormat
     * @param Tx     $client
     * @return array
     */
    public function scropAvatar(string $fileID, $fakeId, string $toFormat, $scropSize, Tx $client): array {
        $toFilePath = config('app.avatar.author_path') . '/' . $fakeId . '.' . $toFormat;
        // dd($fileID, $toFilePath, $toFormat);
        $result     = $client->scropFile($fileID, $toFilePath, $toFormat, $scropSize, $scropSize);

        return $result['ProcessResults']['Object'][0];
    }

    /**
     * @param $author
     * @param string $imgContent
     * @param string $ext
     * @param string $toFormat
     * @param Tx     $client
     * @return array
     */
    public function uploadImage($author, string $imgContent, string $ext, string $toFormat, Tx $client): array {
        $md5        = md5($imgContent);
        $fileID     = config('app.cos_tmp_path') . '/' . $md5 . '.' . $ext;
        $toFileName = config('app.cos_author_path') . '/' . $md5 . '.' . $toFormat;
        $result     = $client->thumbnailAndUpload($fileID, $toFileName, $imgContent, $toFormat);

        return [$fileID, $result['Data']['ProcessResults']['Object'][0]];
    }

    /**
     * @param Author $author
     * @param string $type
     * @param string $path
     * @param string $name
     * @param string $toFormat
     * @param int    $size
     * @param int    $fid
     * @return MediaFile
     */
    protected function saveAuthorMediaFile(Author $author, string $type, string $path, string $name, string $toFormat, int $size, int $fid = 0): MediaFile {
        $mediaFile = MediaFile::updateOrCreate([
            'model_type'     => Author::class,
            'model_id'       => $author->id,
            'type'           => $type,
            'path'           => $path,
        ], [
            'model_type'     => Author::class,
            'model_id'       => $author->id,
            'path'           => $path,
            'name'           => $name,
            'type'           => $type,
            'mime_type'      => GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS[$toFormat],
            'disk'           => 'cosv5',
            'size'           => $size,
            'fid'            => $fid
        ]);

        switch ($type) {
            case MediaFile::TYPE['image']:
                $author->relateToImage($mediaFile->id);

                break;

            case MediaFile::TYPE['avatar']:
                $author->relateToAvatar($mediaFile->id);

                break;
        }

        /* @var MediaFile $mediaFile */
        return $mediaFile;
    }
}
