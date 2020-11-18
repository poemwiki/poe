<?php

namespace App\Console\Bedtime;

use App\Models\Review as ReviewModel;
use App\Models\Score;
use App\Models\WxPost;
use Illuminate\Console\Command;

class Review extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bedtime:review';

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
        $this->addBedtimeReview(2651625905, 44);
        return 0;
    }


    public function addBedtimeReview($maxId = 0, $userId = 44) {
        WxPost::where('appmsgid', '<=', $maxId)->whereNotNull('poem_id')
            ->get()->each(function ($post) use ($userId) {
            ReviewModel::updateOrInsert([
                'poem_id' => $post->poem_id,
                'user_id' => $userId
            ], [
                'poem_id' => $post->poem_id,
                'user_id' => $userId,
                'content' => <<<blade
我在《{$post->title}》&nbsp;&nbsp; <a href="{$post->link}" target="_blank">{$post->link}</a> &nbsp;&nbsp;这篇公众号文章里提到了这首诗
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
            if(in_array($id, $exceptIds)) continue;
            $data[] = ['poem_id' => $id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(), 'score' => 4, 'weight' => 1.0];
        }
        Score::updateOrInsert([
            'poem_id' => $id,
            'user_id' => $userId
        ], $data);
    }
}
