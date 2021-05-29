<?php

namespace App\Console\Bedtime;

use App\Models\Poem;
use App\Models\Review as ReviewModel;
use App\Models\Score;
use App\Models\WxPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Review extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bedtime:review {fromTimestamp?} {toTimestamp?} {--id=}';

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
        $poemId = $this->option('id');
        if (App::runningInConsole() && !$this->option('id')) {
            if ($this->choice('Do you wants specify a poem id?', ['yes', 'no'], 0) === 'yes') {
                $authorId = $this->ask('Input author id: ');
            }
        }

        if (is_numeric($poemId)) {
            $this->addBedtimeReview(0, 0, 44, $poemId);
            return 0;
        }

        // $this->addBedtimeScore(3054, 44);
        $this->addBedtimeReview(
            $this->argument('fromTimestamp') ?: Date::createFromDate(2016, 4, 10, 'Asia/ShangHai')->getTimestamp(),
            $this->argument('toTimestamp') ?: Date::createFromDate(2016, 4, 18, 'Asia/ShangHai')->getTimestamp()
        );
        return 0;
    }


    public function addBedtimeReview($minUpdateTime = 0, $maxUpdateTime = 0, $userId = 44, $poemId = null) {

        $q = is_numeric($poemId)
            ? WxPost::where('poem_id', '=', $poemId)
            : WxPost::whereBetween('update_time', [$minUpdateTime, $maxUpdateTime])
                ->whereNotNull('poem_id');

        $q->get()->each(function ($post) use ($userId) {

            $origin = Str::of($post->link)->replaceMatches('@&chksm=[^#&]*@', '')
                ->replace('#rd', '')
                ->replace('#wechat_redirect', '')
                ->replace('http://', 'https://');

            $link = $origin;
            // 小程序中不支持打开第三方短链接
            // todo 获取 https://mp.weixin.qq.com/s/iluK_83NX7xgx2WOPc6i_A 这样的链接
            // $link = mp_short_url($origin, function ($short) use ($origin, $post) {
            //     if($short !== $origin) {
            //         $post->short_url = $short;
            //         $post->save();
            //     }
            // });

            // $link = short_url(str_replace('http://', 'https://', $link));
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

        for ($id = 1; $id <= $maxId; $id++) {
            if (in_array($id, $exceptIds)) continue;
            if (!Poem::find($id)) continue;

            $data = ['poem_id' => $id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(), 'score' => 10, 'weight' => 1.0];
            Score::updateOrInsert([
                'poem_id' => $id,
                'user_id' => $userId
            ], $data);
        }
    }
}
