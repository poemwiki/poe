<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Services\Tx;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
            $this->error('error while put file to COS', $e->getMessage());

            return -1;
        }

        return 0;
    }

    public function process($author) {
        foreach ($author->pic_url as $key => $picUrl) {
            $url = isValidPicUrl($picUrl) ? $picUrl : config('app.avatar.default');

            if (isWikimediaUrl($url)) {
                $options = config('app.env') === 'production' ? [] : [
                    'proxy' => 'http://127.0.0.1:1087',
                    // 'https' => 'tcp://127.0.0.1:1087'
                ];

                $pathInfo = pathinfo($url);
                $ext      = $pathInfo['extension'];
                // dd($url);
                $response = \Illuminate\Support\Facades\Http::withOptions($options)->timeout(3)->retry(1, 1)->get($url);
                if ($response->status() !== 200) {
                    return responseFile(config('app.avatar.default'));
                }
                $imgContent = $response->body();

                $client                = new Tx();
                $toFormat              = TX::SUPPORTED_FORMAT['webp'];
                list($fileID, $result) = $this->uploadImage($author, $imgContent, $ext, $toFormat, $client);

                if (isset($result['Data']['ProcessResults']['Object'][0]['Location'])) {
                    $urls            = $author->pic_url;
                    $urls[$key]      = $client->getUrl($fileID);

                    if ($key === 0) {
                        $avatarResult = $this->scropAvatar($fileID, $author->fakeId, $toFormat, $client);

                        if (isset($avatarResult['ProcessResults']['Object'][0]['Location'])) {
                            $author->avatar = 'https://' . $avatarResult['ProcessResults']['Object'][0]['Location'];
                        }
                    }

                    $author->pic_url = $urls;
                    $author->save();
                }
                // 获取 wikimedia 链接及版权信息，保存至 image 表
                $wikimediaPicInfo = get_wikimedia_pic_info([
                    'title' => $pathInfo['basename'],
                ]);

                dd($result, $wikimediaPicInfo);
            // TODO save to image, and link to author
            } else {
                $storePath = $url;
            }
        }
    }

    /**
     * @param $fileID
     * @param $fakeId
     * @param string $toFormat
     * @param Tx     $client
     * @return array
     */
    public function scropAvatar($fileID, $fakeId, string $toFormat, Tx $client): array {
        $toFilePath = 'avatar/' . $fakeId . '.' . $toFormat;
        $result     = $client->scropFile($fileID, $toFilePath, $toFormat, 300, 300);

        return $result;
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
        $fileID     = config('app.cos_author_path') . '/' . md5($imgContent) . '.' . $ext;
        $toFileName = $author->fakeId . '.' . $toFormat;
        $result     = $client->thumbnailAndUpload($fileID, $toFileName, $imgContent, $toFormat, 300, 300, 70);

        return [$fileID, $result];
    }
}
