<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Repositories\ScoreRepository;
use App\User;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Response;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use \PDO as PDO;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class BotController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;
    /**
     * @var \EasyWeChat\OfficialAccount\Application
     */
    private $wechatApp;

    public function __construct() {
        ini_set('memory_limit', '300M');
        Jieba::init(array('mode' => 'default', 'dict' => 'small'));
        Finalseg::init();

        $config = [
            'app_id' => env('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => env('WECHAT_OFFICIAL_ACCOUNT_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'object',
        ];

        $this->wechatApp = Factory::officialAccount($config);
        $cache = new RedisAdapter(app('redis')->connection()->client());
        $this->wechatApp->rebind('cache', $cache);
    }

    public function data($poeDB, $chatroom) {
        $dataMsg = [];

        // TODO move query to
        // app('App\Http\Controllers\QueryController')->getMonthPoemCount();
        $logToQuery = [
            ['subject' => ActivityLog::SUBJECT['poem'], 'msg' => '上传诗歌数'],
            ['subject' => ActivityLog::SUBJECT['score'], 'msg' => '新增评分数'],
            ['subject' => ActivityLog::SUBJECT['review'], 'msg' => '新增评论数'],
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfMonthShort = $startOfMonth->format('n.j');
        $endOfMonth = Carbon::now()->endOfMonth();
        $endOfMonthShort = $endOfMonth->format('n.j');
        $startPreviousMonth = Carbon::now()->startOfMonth()->subMonth();
        $startPreviousMonthShort = $startPreviousMonth->format('n.j');
        $endPreviousMonth = Carbon::now()->subMonth()->endOfMonth();
        $endPreviousMonthShort = $endPreviousMonth->format('n.j');

        foreach ($logToQuery as $log) {

            $monthCount = ActivityLog::where('subject_type', '=', $log['subject'])
                ->where('created_at', '>=', $startOfMonth)
                ->where('created_at', '<=', $endOfMonth)
                ->where('description', '=', 'created')
                ->count();

            array_push($dataMsg, "$startOfMonthShort ~ $endOfMonthShort\t{$log['msg']} $monthCount");

            // app('App\Http\Controllers\QueryController')->getMonthPoemCount();
            $previousMonthCount = ActivityLog::where('subject_type', '=', $log['subject'])
                ->where('created_at', '>=', $startPreviousMonth)
                ->where('created_at', '<=', $endPreviousMonth)
                ->where('description', '=', 'created')
                ->count();
            array_push($dataMsg, "$startPreviousMonthShort ~ $endPreviousMonthShort\t{$log['msg']} $previousMonthCount");
        }


        // 新增用户
        $monthCount = User::where('created_at', '>=', $startOfMonth)
            ->where('created_at', '<=', $endOfMonth)
            ->count();
        array_push($dataMsg, "$startOfMonthShort ~ $endOfMonthShort\t新增用户数 $monthCount");
        $previousMonthCount = User::where('created_at', '>=', $startPreviousMonth)
            ->where('created_at', '<=', $endPreviousMonth)
            ->count();
        array_push($dataMsg, "$startPreviousMonthShort ~ $endPreviousMonthShort\t新增用户数 $previousMonthCount");

        // 机器人回复次数

        $this->_log($poeDB, $chatroom, 'data', null);

        $msg = [
            'code' => 0,
            'poem' => implode("\n", $dataMsg),
            'data' => []
        ];
        if(isset($_GET['poemwiki'])) {
            return implode("<br>", $dataMsg);
        }
        return Response::json($msg);
    }

    private function _log($poeDB, $chatroom, $subject_type, $subject_id) {
        $stmt = $poeDB->prepare('INSERT INTO `bot_reply_log` SET `created_at`=:created_at,
                `subject_id`=:subject_id, `subject_type`=:subject_type, `chatroom_id`=:chatroom_id');
        $stmt->bindValue(':subject_id', null);
        $stmt->bindValue(':chatroom_id', $chatroom);
        $stmt->bindValue(':created_at', now());
        $stmt->bindValue(':subject_type', 'data');
        $stmt->execute();

        if($stmt->errorCode() !== '00000') {
            Log::error('error while insert to bot reply');
            Log::error($stmt->errorInfo());
        }
    }

    /**
     * Display a listing of the Poem.
     *
     * @param Request $request
     *
     */
    public function index(Request $request) {
        $chatroom = $request->input('chatroom', '');
        $maxLength = $request->input('maxLength', 800);
        $msg = $request->input('keyword', '云朵');

        $dataMode = in_array($msg, ['数据', '搜数据', 'data']) && in_array($chatroom, ['R:10696051632015143', 'R:10696051758570234']);
        $keyword = $this->getKeywords($msg);

        if (empty($keyword) && !$dataMode) {
            return Response::json([
                'code' => -2,
                'poem' => '抱歉，没有匹配到关键词。',
                'data' => []
            ]);
        }

        $poeDB = new PDO('mysql:dbname=poe;host=' . config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'), [
                PDO::ATTR_EMULATE_PREPARES => TRUE
            ]);
        if ($dataMode) {
            return $this->data($poeDB, $chatroom);
        }

        $originWords = '';
        if (is_array($keyword)) {
            $originWords = implode(' ', $keyword);
            $sql = 'SELECT (select 1 from wx_post WHERE poem_id=p.id limit 1) as `wx`, `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
`from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`, `dynasty`, `preface`, `subtitle`, `location`, `short_url`
        FROM `poem` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE ';
            foreach ($keyword as $idx => $word) {
                $sql .= "(`poem` like :keyword1_$idx OR `title` like :keyword2_$idx
        OR `poet` like :keyword3_$idx OR `poet_cn` like :keyword4_$idx OR `translator` like :keyword5_$idx) AND";
            }
            $sql = trim($sql, 'AND') . ' AND `length` < :maxLength AND (`need_confirm` IS NULL OR`need_confirm`<>1)
        ORDER BY wx desc, `selected_count`,`last_selected_time`,length(`poem`) limit 0,2';
            $poeDB->prepare($sql);

            $q = $poeDB->prepare($sql);
            foreach ($keyword as $idx => $word) {
                $word = '%' . $word . '%';
                $q->bindValue(":keyword1_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword2_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword3_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword4_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword5_$idx", "%$word%", PDO::PARAM_STR);
            }

        } else {
            $originWords = $keyword;
            $q = $poeDB->prepare(<<<'SQL'
        SELECT (select 1 from wx_post WHERE poem_id=p.id limit 1) as `wx`, `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
`from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`, `dynasty`, `preface`, `subtitle`, `location`, `short_url`
        FROM `poem` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE (`poem` like :keyword1 OR `title` like :keyword2
        OR `poet` like :keyword3 OR `poet_cn` like :keyword4 OR `translator` like :keyword5) AND `length` < :maxLength AND (`need_confirm` IS NULL OR `need_confirm`<>1)
        ORDER BY wx desc, `selected_count`,`last_selected_time`,length(`poem`) limit 0,2
SQL
            );
            $word = '%' . $keyword . '%';
            $q->bindValue(':keyword1', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword2', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword3', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword4', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword5', $word, PDO::PARAM_STR);
        }

        $q->bindValue(':chatroomId', $chatroom, PDO::PARAM_STR);
        $q->bindValue(':maxLength', $maxLength, PDO::PARAM_INT);

        //$q->debugDumpParams();
        $code = -1;
        $poem = '';
        $data = [];
        if ($q->execute()) {
            $code = 0;
            $res = $q->fetchAll(PDO::FETCH_ASSOC);
            $count = count($res);

            // TODO put this into blade
            if ($count == 0) {
                $emoji = Arr::random(['😓', '😅', '😢', '😂', '😭呜呜 ', '', '🙁️', '😫', '😶', '😬', '😔', '😒', '😠', '😊', '😹','🙁','🙃']);
                $sorry = Arr::random(['Sorry', '对不起', '抱歉', '不好意思', '不好意思哈', 'Soooorry']);
                $notFound = Arr::random(['没查到', '没搜着', '没找到', '没找着']);
                $ne = Arr::random(['相关内容', '', '呢']);
                $welcome = Arr::random([
                    "欢迎你来上传关于“${originWords}”的诗，",
                    "你来上传一些关于“${originWords}”的诗如何，"
                ]);
                $click = Arr::random(['点这里：','点击：','👉','➡️']);
                $link = 'poemwiki.org/new';
                $poem = "$emoji ${sorry}，${notFound}${ne}。${welcome}\n${click}$link";
            } else {
                $data = $res[0];
                $post = (object)$res[0];

                $nation = $post->dynasty
                    ? "[$post->dynasty] "
                    : (($post->nation && $post->nation !== '中国') ? "[$post->nation] " : '');

                $od = opencc_open("t2s.json");
                $content = opencc_convert(preg_replace('@[\r\n]{3,}@', "\n\n", $post->poem), $od);

                $writer = '作者 / ' .($post->poet_cn
                    ?  $nation . ($post->poet_cn ?? $post->poet)
                    : ($post->poet ? $post->poet : ''));


                // poem content
                $parts = ['▍ ' . opencc_convert($post->title, $od)];
                opencc_close($od);
                if($post->preface) array_push($parts, '        '. $post->preface);
                if($post->subtitle) array_push($parts, "\n    ".$post->subtitle);
                array_push($parts, "\n".$content."\n");


                // poem's other properties

                $timeStr = '';
                if ($post->year) $timeStr .= $post->year;
                if ($post->year && $post->month) $timeStr .= '.';
                if ($post->month) $timeStr .= $post->month;
                if ($post->month && $post->date) $timeStr .= '.';
                if ($post->date) $timeStr .= $post->date;

                if ($post->location) {
                    if ($timeStr) {
                        array_push($parts, "$timeStr, $post->location\n");
                    } else {
                        array_push($parts, $post->location."\n");
                    }
                } else if ($timeStr) {
                    array_push($parts, $timeStr."\n");
                }

                array_push($parts, $writer);


                if ($post->translator) array_push($parts, '翻译 / ' . trim($post->translator));

                // links & score
                if(!$post->short_url) {
                    // TODO use poems/show url instead of exposing poem id
                    $url = $this->shorten(route('poem', $post->id), function ($link) use ($post){
                        $p = Poem::find($post->id);
                        if(empty($p)) return;

                        $p->short_url = $link;
                        $p->save();
                    });
                } else {
                    $url = $post->short_url;
                }
                $wikiLink = "\n\n诗歌维基：$url";

                $scoreRepo = new ScoreRepository(app());
                $score = $scoreRepo->calcScoreByPoemId($post->id);
                if ($score['score']) {
                    $wikiScore = '评分：' . "${score['score']} " . str_repeat("🌟", floor($score['score']));
                    array_push($parts, $wikiLink);
                    array_push($parts, $wikiScore);
                } else {
                    $wikiScore = "点这里：$url 做第一个给这首诗打分的人";
                    array_push($parts, "\n\n$wikiScore");
                }

                if($count >= 2 || is_array($keyword)) {
                    $word = is_array($keyword) ? $keyword[0] : $keyword;
                    $more = "\n更多\"$word\"的诗：" . $this->shorten(route('search', $word));
                    array_push($parts, $more);
                }

                $poem = implode("\n", $parts);

                if ($post->last_selected_time) {
                    $stmt = $poeDB->prepare('UPDATE `chatroom_poem_selected` SET `selected_count`=1+`selected_count`
                WHERE `poem_id`=:poem_id AND `chatroom_id`=:chatroom_id');
                } else {
                    $stmt = $poeDB->prepare('INSERT INTO `chatroom_poem_selected` SET `selected_count`=1,
                `poem_id`=:poem_id, `chatroom_id`=:chatroom_id');
                }
                $stmt->bindValue(':poem_id', $post->id);
                $stmt->bindValue(':chatroom_id', $chatroom);
                $stmt->execute();
                //        print_r($stmt->errorCode());
                //        print_r($stmt->errorInfo());

                $this->_log($poeDB, $chatroom, \App\Models\Poem::class, $post->id);
            }
        } else {
            Log::error('Bot search failed.');
            Log::error($q->errorInfo());
        }


        $msg = [
            'code' => $code,
            'poem' => $poem,
            'data' => $data
        ];

        return Response::json($msg);
    }

    /**
     * @param PDO $db
     * @param $poemID
     * @return array|object
     */
    private function findWxPost(PDO $db, $poemID) {
        $sql = <<<'SQL'
SELECT wx.*,p.bedtime_post_id FROM wx_post wx RIGHT JOIN poem p
ON p.bedtime_post_id=wx.bedtime_post_id WHERE p.id=:poemID;
SQL;
        $q = $db->prepare($sql);
        $q->bindValue(':poemID', $poemID, PDO::PARAM_INT);
        if (!$q->execute()) {
            return [];
        }

        return $q->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    /**
     * @param string $str
     * @param boolean $divide
     * @return string[]|string
     */
    private function getKeywords($str, $divide = false) {
        $str = trim(preg_replace('@[[:punct:]\n\r～｜　\s]+@u', ' ', $str));
        $keyword = '';
        $matches = [];
        preg_match('@^(搜索??|search)(一下|一搜|一首|一个)??\s*?(?<keyword>.*)(的?((古|现代)?诗歌?|词))?$@Uu', $str, $matches);
        if (isset($matches['keyword'])) {
            $keyword = trim($matches['keyword']);
        } else {
            $matches = [];
            preg_match('@^(有没有??|告诉我|帮我找|我想要|(给我来|给我|来)|搜索?)(一首|(一|那|哪)?个|一下)??((和|跟|带|包?含)有??)??\s*?(?<keyword>.*)((有关|相关)?的?((十四行|十六行|古|现代)?诗歌?|词))$@Uu', $str, $matches);
            $keyword = isset($matches['keyword']) ? trim($matches['keyword']) : '';
        }

        if ($divide) {
            return Jieba::cut($keyword);
        }

        return strstr($keyword, ' ')
            ? explode(' ', $keyword)
            : $keyword;
    }

    private function shorten($link, $cb = null) {
        // TODO enable shorten until it accessible under China Mobile 4G network
        return $link;
        $shortUrl = $this->wechatApp->url->shorten($link);
        if($shortUrl->errcode === 0) {
            if(is_callable($cb)) call_user_func($cb, $shortUrl->short_url);
            return $shortUrl->short_url;
        }
        return $link;
    }
}
