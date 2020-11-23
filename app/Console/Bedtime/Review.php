<?php

namespace App\Console\Bedtime;

use App\Models\Review as ReviewModel;
use App\Models\Score;
use App\Models\WxPost;
use EasyWeChat\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Review extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bedtime:review {fromTimestamp?} {toTimestamp?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add bedtimepoem.com comment';

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
        // $this->addBedtimeScore(3054, 44);
        $this->addBedtimeReview(
            $this->argument('fromTimestamp') ?? Date::createFromDate(2016, 4, 10, 'Asia/ShangHai')->getTimestamp(),
            $this->argument('toTimestamp') ?? Date::createFromDate(2016, 4, 18, 'Asia/ShangHai')->getTimestamp()
        );
        return 0;
    }


    public function addBedtimeReview($minUpdateTime = 0, $maxUpdateTime = 0, $userId = 44) {
        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'object',
        ];

        $app = Factory::officialAccount($config);

        WxPost::whereBetween('update_time', [$minUpdateTime, $maxUpdateTime])
            ->whereNotNull('poem_id')
            ->get()->each(function ($post) use ($userId, $app) {

                $link = Str::of($post->link)->replaceMatches('@&chksm=[^#&]*@', '')
                    ->replace('#rd', '')
                    ->replace('#wechat_redirect', '');

                $shortUrl = $app->url->shorten($link);
                if($shortUrl->errcode === 0) {
                    $link = $shortUrl->short_url;
                    $post->short_url = $link;
                    $post->save();
                }

                Log::info('Add bedtimepoem review link: ' . $link);

                $res = ReviewModel::updateOrInsert([
                    'poem_id' => $post->poem_id,
                    'user_id' => $userId
                ], [
                    'poem_id' => $post->poem_id,
                    'user_id' => $userId,
                    'content' => <<<blade
我在《{$post->title}》&nbsp;&nbsp; <a href="{$link}" target="_blank">{$link}</a> &nbsp;&nbsp;这篇公众号文章里提到了这首诗
blade,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function addBedtimeScore($maxId = 3054, $userId = 44) {
        $exceptIds = [514, 515, 516, 517, 518];
        $data = [];
        for ($id = 1; $id <= $maxId; $id++) {
            if (in_array($id, $exceptIds)) continue;
            $data[] = ['poem_id' => $id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(), 'score' => 5, 'weight' => 1.0];
        }
        Score::updateOrInsert([
            'poem_id' => $id,
            'user_id' => $userId
        ], $data);
    }
}
