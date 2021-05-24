<?php

namespace App\Console\Commands;

use App\Models\Crawl;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use QL\QueryList;

class crawler extends Command {
    /**
     * The name and signature of the console command.
     * eg url: http://www.zgshige.com/c/2019-08-21/950036.shtml
     * @var string
     */
    protected $signature = 'crawler:import {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'craw author & poem from www.zgshige.com';

    public $authorExportSetting = [
        'model' => \App\Models\Author::class,
        'fields' => [
            'name' => 'name_lang',
            'desc' => 'desc_lang',
        ]
    ];
    public $poemExportSetting = [
        'model' => \App\Models\Poem::class,
        'fields' => [
            'title' => 'title',
            'content' => 'poem',
        ],
    ];
    public $requestConfig = [
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'en-US,en-GB;q=0.9,en;q=0.8,zh-CN;q=0.7,zh;q=0.6',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Cookie' => '122_vq=133; route=8b99f870b9e93c0bc131e09a39fdc3f6',
            'DNT' => '1',
            'Host' => 'www.zgshige.com',
            'Pragma' => 'no-cache',
            'Referer' => 'http://www.zgshige.com',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36',
        ]
    ];

    public function __construct() {
        parent::__construct();
    }

    public function fetch($url, $model, $fetchFunction, $cb) {
        $crawl = Crawl::where([
            'model' => $model,
            'url' => $url,
        ])->first();
        if ($crawl) {
            $result = json_decode($crawl->result, true);
            $result['html'] = $crawl->html;
        } else {
            $result = $this->$fetchFunction($url);
        }
        return $cb($result);
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle() {
        $url = $this->argument('url') ?? 'http://www.zgshige.com/c/2019-08-21/950036.shtml';


        // dd($this->fetchPoem('http://www.zgshige.com/c/2016-03-04/950042.shtml'));

        $poetCrawl = $this->fetch($url, \App\Models\Author::class, 'fetchPoetInfo', function($result) use($url) {
            logger()->info('crawled poetInfo');
            return Crawl::updateOrCreate([
                'model' => \App\Models\Author::class,
                'url' => $url,
            ], [
                'name' => $result['name'],
                'export_setting' => json_encode($this->authorExportSetting),
                'result' => json_encode($result)
            ]);
        });
        $poetInfo = json_decode($poetCrawl['result'], true);


        try{
            $poemList = [];
            $next = 1;

            while($next) {
                $listUrl = $poetInfo['poemListUrl'] . '&PageIndex=' . ($next - 1);

                logger()->info('crawling list page: ' . $listUrl);

                $listPage = $this->fetch($listUrl, 'App\Models\PoemList', 'fetchPoemUrls', function ($listPage) use ($listUrl, $poetCrawl, $poetInfo, $next){
                    logger()->info('crawled pormUrls: ', $listPage);
                    $html = $listPage['html'];
                    unset($listPage['html']);

                    $listCrawl = Crawl::updateOrCreate([
                        'url' => $listUrl,
                        'model' => 'App\Models\PoemList', // just for fill model field, PoemList not really existed
                    ], [
                        'name' => $poetInfo['name'].'-list-page-'.$next,
                        'export_setting' => null,
                        'html' => $html,
                        'result' => json_encode($listPage),
                        'f_crawl_id' => $poetCrawl->id
                    ]);
                    return json_decode($listCrawl['result'], true);
                });

                $poemList = array_merge(
                    $poemList,
                    $listPage['list']
                );

                $next = $listPage['next'] ?? null;
                logger()->info('list page next: ' . $next);
            }

        }catch(\GuzzleHttp\Exception\RequestException $e){
            logger()->error('诗歌列表爬取失败：' . $poetInfo['poemListUrl'] . $e->getMessage());
            echo 'Http Error';
            return -1;
        }


        foreach ($poemList as &$item) {
            logger()->info('crawling item page: ' . $item['poemUrl']);
            $poem = $this->fetch($item['poemUrl'], \App\Models\Poem::class, 'fetchPoem', function($poem) use ($poetCrawl, $item) {
                logger()->info('crawled item: ', $poem);
                Crawl::updateOrCreate([
                    'url' => $item['poemUrl'],
                    'model' => \App\Models\Poem::class,
                ], [
                    'name' => $item['title'],
                    'export_setting' => json_encode($this->poemExportSetting),
                    'result' => json_encode($poem['content']),
                    'html' => $poem['html'],
                    'f_crawl_id' => $poetCrawl->id
                ]);
            });
            sleep(1);
        }

        return 0;
    }

    public function fetchPoem($url) {
        $rules = [
            'html' => [
                '#content',
                'html', '-.signatureDiv -[id^=audio]',],
            'content' => [
                '#content',
                'html', '-.signatureDiv -[id^=audio]',
                function($content) {
                    return Str::of($content)
                        ->replaceMatches('@推荐语：.*$@s', '')
                        ->when(true, function ($string) {
                            if(!strstr("\n", $string->__toString())) {
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

    /**
     * @param $url string e.g http://www.zgshige.com/zcms/poem/list?SiteID=122&poetname=%E5%93%91%E7%9F%B3&articleID=1006823&articleContributeUID=22686&catalogID=15111
     * @return array
     */
    public function fetchPoemUrls($url) {
        $listRules = [
            'poemUrl' => [
                'a',
                'href',
            ],
            'title' => [
                'a',
                'text', '',
                function($content) {
                    return Str::of($content)->replaceMatches('@((^[\s\t\n\r\v《》<>]*)|([\s\t\n\r\v《》<>]*$))@u', '')->__toString();
                }
            ],
        ];
        $pageInfoRules = [
            'next' => [
                '.m-t-sm .pull-right .active+li>.page-num', 'text'
            ],
            'html' => [
                '.m-t-sm .pull-right .p-sm', 'html'
            ]
        ];

        $query = QueryList::get($url, null, [
            'timeout' => 1.5,
        ]);
        $pageInfo = $query->rules($pageInfoRules)->queryData();
        $list = $query->rules($listRules)->range('.sr_dt_title')->queryData();

        $pageInfo['list'] = $list;
        return $pageInfo;
    }

    public function fetchPoetInfo($url) {
        $rules = [
            'name' => [
                '.sr_w_name',
                'text', '',
                function($content) {
                    return trim(str_replace('笔名：', '', $content));
                }
            ],
            'desc' => [
                '#srzy_srjj+.p-sm',
                'text'
            ],
            'poemListUrl' => [
                '.sr_l_title small>a',
                'href', '',
                function($content) {
                    $str = Str::of($content)->match("@http://www.zgshige.com.+$@")
                        ->rtrim(" ';")
                        ->replaceMatches("@'\+encodeURI\('(.*)'\)\+'@", function($match) {
                            return urlencode($match[1]);
                        })
                    ;
                    return $str->__toString();
                }
            ],
        ];

        return QueryList::get($url, null, [
            'timeout' => 1.5,
        ])->rules($rules)->range('')->queryData();
    }
}
