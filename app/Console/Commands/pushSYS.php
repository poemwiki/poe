<?php

namespace App\Console\Commands;

use App\Models\Poem;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class pushSYS extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:sys {fromId?} {toId?} {--limit=} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'push url to wechat Sou Yi Sou';

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
        $fromId = $this->argument('fromId') ?: 0;
        $toId   = $this->argument('toId') ?: 0;

        $poemId = $this->option('id');
        $limit  = $this->option('limit');
        // if (App::runningInConsole() && !$this->option('poem_id')) {
        //     if ($this->choice('Do you wants specify wikidata id?', ['yes', 'no'], 0) === 'yes') {
        //         $poemId = $this->ask('Input wikidata id: ');
        //     }
        // }

        if (is_numeric($poemId)) {
            $poems = Poem::where([
                ['id', '=', $poemId]
            ])->whereRaw('NOT(`need_confirm` <=> 1)')->get();
        } else {
            $poems = Poem::where([
                ['id', '>=', $fromId],
                ['id', '<=', $toId]
            ])->whereRaw('NOT(`need_confirm` <=> 1)')->limit($limit)->get();
        }
        dd($poems->toArray());

        $this->push($poems);

        return 0;
    }

    public function push(Collection $poems) {
        $wechatApp = app('wechat.mini_program');
        $client    = new \EasyWeChat\Kernel\BaseClient($wechatApp);

        $pages = [];
        foreach ($poems as $poem) {
            if (!$poem->poetLabel) {
                continue;
            }

            $tags = ['诗歌', '现代诗', 'poemwiki', $poem->poetLabel, $poem->title];
            if ($poem->translatorLabel) {
                $tags[] = $poem->translatorLabel;
            }

            $data = array_filter([
                '@type'        => 'wxsearch_testcpdata',
                'update'       => 1, // 1-新增；3-删除；内容更新按照新增处理，如果页面路径（page+query）相同，微信会做覆盖更新
                'content_id'   => $poem->fakeId,
                'page_type'    => 2,
                'category_id'  => 14, // 14-文化 3-教育 1-综合
                'h5_url'       => route('p/show', $poem->fakeId),
                'title'        => $poem->poetLabel . '-' . $poem->title,
                'subtitle'     => $poem->subtitle ? [$poem->subtitle] : null,
                'abstract'     => [$poem->firstLine], // TODO firstLine should not be empty; TODO secondLine and endLine
                'mainbody'     => $poem->poem,
                'author'       => ['author_name' => $poem->poetLabel],
                'time_publish' => $poem->created_at->timestamp,
                'time_modify'  => $poem->updated_at->timestamp,
                'tag'          => $tags, // 文章的keyword，支持多个， 《水煮肉片的做法》建议 tag=[“水煮肉片”，“煮”,“猪肉”，“川菜”，“四川”]， 《煎牛排》建议 tag=[“牛排”，“煎”,“西式菜”]
                // 'searchword' => ['searchword'] // 用于绑定微信官方提供的query
                // 'pv' => 22,
                'like' => $poem->scores->count()

            ], function ($item) {
                return $item !== null;
            });

            // dump($data);

            $pages[] = [
                'path'      => 'pages/poems/index',
                'query'     => 'id=' . $poem->id,
                'data_list' => [$data]
            ];
        }

        $this->info('last push:', $poems->last()->id);
        $ret = submitPage2SYS($pages, $client);
        $this->info('push result:', var_export($ret));
        dump($ret);
    }
}
