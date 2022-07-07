<?php

namespace App\Console\Poem;

use App\Models\Crawl;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use QL\QueryList;

class crawlAllPoetry extends Command {
    protected $signature = 'poem:crawlAllPoetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'craw author & poem from allpoetry';

    public $authorExportSetting = [
        'model'  => \App\Models\Author::class,
        'fields' => [
            'poet'      => 'name_lang',
            'poet_desc' => 'describe_lang',
        ]
    ];
    public $poemExportSetting = [
        'model'  => \App\Models\Poem::class,
        'fields' => [
            'title'   => 'title',
            'content' => 'poem',
        ],
    ];
    public $requestConfig = [
        'headers' => [
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding'           => 'gzip, deflate',
            'Accept-Language'           => 'en-US,en-GB;q=0.9,en;q=0.8,zh-CN;q=0.7,zh;q=0.6',
            'Cache-Control'             => 'no-cache',
            'Connection'                => 'keep-alive',
            'Cookie'                    => '122_vq=133; route=8b99f870b9e93c0bc131e09a39fdc3f6',
            'DNT'                       => '1',
            'Host'                      => 'www.zgshige.com',
            'Pragma'                    => 'no-cache',
            'Referer'                   => 'http://www.zgshige.com',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36',
        ]
    ];
    public static $source = 'allpoetry';

    public function __construct() {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function fetch($url, $model, $cb, $sleep = null) {
        $crawl = Crawl::where([
            'model' => $model,
            'url'   => $url,
        ])->first();

        if ($crawl) {
            logger()->info('use previous result:' . $crawl->result);
            $result         = json_decode($crawl->result, true);
            $result['html'] = isset($result['html']) ? $result['html'] : $crawl->html;
        } else {
            $result = $this->fetchPoem($url);
            if ($sleep) {
                sleep($sleep + random_int(0, 5));
            }
        }

        if ($result['content']) {
            $cb($result);
        } else {
            throw new \Exception('fetch failed:' . $url);
        }
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle() {
        $jsonFile = file_get_contents(storage_path('allpoetry.json'));
        $json     = json_decode($jsonFile, true);
        // dd(array_keys($json)); // 5525 authors, 103266 poem

        $failedPoemUrl = [];
        foreach ($json as $authorName => $author) {
            $poetCrawl = Crawl::updateOrCreate([
                'model'          => \App\Models\Author::class,
                'url'            => $author['poet_link'],
                'source'         => self::$source,
            ], [
                'admin_user_id'  => 1,
                'name'           => $author['poet'],
                'export_setting' => json_encode($this->authorExportSetting),
                'result'         => json_encode($author)
            ]);

            $poemList = $author['poem_list'];
            foreach ($poemList as $title => $poemUrl) {
                logger()->info('crawling item page: ' . $poemUrl);

                try {
                    $sleep = rand(0, 10) * 0.2;
                    $this->fetch($poemUrl, \App\Models\Poem::class, function ($poem) use ($poetCrawl, $title, $poemUrl) {
                        // logger()->info('crawled item: ', $poem);

                        return Crawl::updateOrCreate([
                            'url'    => $poemUrl,
                            'model'  => \App\Models\Poem::class,
                            'source' => self::$source,
                        ], [
                            'name'           => $title,
                            'export_setting' => json_encode($this->poemExportSetting),
                            'result'         => json_encode(collect($poem)->except('html')),
                            'html'           => $poem['html'],
                            'f_crawl_id'     => $poetCrawl->id
                        ]);
                    }, $sleep);
                } catch (\Exception $e) {
                    $failedPoemUrl[] = $poemUrl;
                }
            }
        }

        if (!empty($poemUrl)) {
            logger()->error('failed poem url: ', $failedPoemUrl);
        }

        return 0;
    }

    /**
     * @param $url
     * @return array
     * @throw \GuzzleHttp\Exception\ClientException
     */
    public function fetchPoem($url) {
        $rules = [
            'html' => [
                '.sub',
                'htmlOuter'],
            'title' => [
                '.fonted>h1.title',
                'text'],
            'languages' => [
                '.big_nav.toggle_tabs',
                'html'
            ],
            'content' => [
                '.sub',
                'htmlOuter', '-.copyright',
                function ($content) {
                    $ql = QueryList::html($content);
                    $id = $ql->find('.sub')->eq(0)->attr('data-id');
                    $original = $ql->find('.orig_' . $id)->eq(0)->html();

                    return Str::of($original)
                        ->replaceMatches('@<div class="tr_\d+">[^>]*</div>$@sU', '')
                        ->when(true, function (\Illuminate\Support\Stringable $string) {
                            if ($string->length() <= 0) {
                                return '';
                            }

                            if (!strstr("\n", $string->__toString())) {
                                return Str::of(strip_tags($string->replaceMatches('@<br\s*/?>@', "\n")));
                            }

                            return Str::of(strip_tags($string));
                        })
                        ->replaceMatches('@^\s*\n@', "\n")
                        ->replaceMatches('@\n{3,}@s', "\n\n")
                        ->trim()
                        ->__toString();
                }
            ],
        ];

        return QueryList::get($url)->rules($rules)->queryData();
    }
}
