<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Repositories\ScoreRepository;
use App\User;
use EasyWeChat\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Response;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use \PDO as PDO;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class BotController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;

    protected $factor = [
        'dushui' => 1.5,
        'ugc' => 1
    ];

    public function __construct() {
        ini_set('memory_limit', '300M');
        Jieba::init(array('mode' => 'default', 'dict' => 'small'));
        Finalseg::init();
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
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthDuration = $this->_pad($startOfMonth->format('n.j'), 4) . '~' .$this->_pad($endOfMonth->format('n.j'));
        $startPreviousMonth = Carbon::now()->startOfMonth()->subMonth();
        $endPreviousMonth = Carbon::now()->subMonth()->endOfMonth();
        $monthPreviousDuration = $this->_pad($startPreviousMonth->format('n.j'), 4) . '~' .$this->_pad($endPreviousMonth->format('n.j'));

        foreach ($logToQuery as $log) {

            $monthCount = ActivityLog::where('subject_type', '=', $log['subject'])
                ->where('created_at', '>=', $startOfMonth)
                ->where('created_at', '<=', $endOfMonth)
                ->where('description', '=', 'created')
                ->count();

            array_push($dataMsg, "$monthDuration {$log['msg']} $monthCount");

            // app('App\Http\Controllers\QueryController')->getMonthPoemCount();
            $previousMonthCount = ActivityLog::where('subject_type', '=', $log['subject'])
                ->where('created_at', '>=', $startPreviousMonth)
                ->where('created_at', '<=', $endPreviousMonth)
                ->where('description', '=', 'created')
                ->count();
            array_push($dataMsg, "$monthPreviousDuration {$log['msg']} $previousMonthCount");
        }


        // 新增用户
        $monthCount = User::where('created_at', '>=', $startOfMonth)
            ->where('created_at', '<=', $endOfMonth)
            ->count();
        array_push($dataMsg, "$monthDuration 新增用户数 $monthCount");
        $previousMonthCount = User::where('created_at', '>=', $startPreviousMonth)
            ->where('created_at', '<=', $endPreviousMonth)
            ->count();
        array_push($dataMsg, "$monthPreviousDuration 新增用户数 $previousMonthCount");

        // 机器人回复次数

        $this->_log($poeDB, $chatroom, 'data', null);

        $msg = [
            'code' => 0,
            'poem' => implode("\n", $dataMsg),
            'data' => []
        ];
        if(isset($_GET['poemwiki'])) {
            return implode("\n", $dataMsg);
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

    private function _pad($str, $length=5) {
        return Str::padLeft($str, $length);
    }

    /**
     * @param Poem $poem
     * @return mixed|string
     */
    public function getUrl(Poem $poem) {
        if (!$poem->short_url) {
            $longUrl = 'https://poemwiki.org/p/' . Poem::getFakeId($poem->id);
            $url = short_url($longUrl, function ($link) use ($poem, $longUrl) {
                Log::info('shorted url:' . $link);
                if ($link === $longUrl) return;

                $p = Poem::find($poem->id);
                if (empty($p)) return;

                $p->short_url = $link;
                $p->save();
            });
            if ($url === $longUrl) {
                $url = 'https://poemwiki.org/' . $poem->id;
            }
        } else {
            $url = $poem->short_url;
        }
        return $url;
    }

    private function _boldNum($str) {
        return str_replace([0,1,2,3,4,5,6,7,8,9], ['𝟎','𝟏','𝟐','𝟑','𝟒','𝟓','𝟔','𝟕','𝟖','𝟗'], $str);
    }

    // get poem uploader wechat id?
    private function getUploader($poemId) {

    }

    public function top($poeDB, $chatroom) {
        $dataMsg = [];

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthDuration = $this->_pad($startOfMonth->format('n.j'), 4) . '~' .$this->_pad($endOfMonth->format('n.j'));
        $startPreviousMonth = Carbon::now()->startOfMonth()->subMonth();
        $endPreviousMonth = Carbon::now()->subMonth()->endOfMonth();
        $monthPreviousDuration = $this->_pad($startPreviousMonth->format('n.j'), 4) . '~' .$this->_pad($endPreviousMonth->format('n.j'));

        $monthCount = Poem::where('created_at', '>=', $startOfMonth)
            ->where('created_at', '<=', $endOfMonth)
            ->count('id');
        array_push($dataMsg, "$monthDuration 新增诗歌数 $monthCount");

        $previousMonthCount = Poem::where('created_at', '>=', $startPreviousMonth)
            ->where('created_at', '<=', $endPreviousMonth)
            ->count('id');
        array_push($dataMsg, "$monthPreviousDuration 新增诗歌数 $previousMonthCount");

        $total = Poem::count('id');
        array_push($dataMsg, "累计诗歌数 $total");

        $sql = <<<SQL
SELECT causer_id as userID, users.name as `name`, count(*) as `pcount` FROM activity_log as a
LEFT JOIN users on causer_id=users.id
WHERE subject_type=? and description="created"
  AND causer_type=?
and a.created_at>=? AND a.created_at<=?
GROUP BY (causer_id)
ORDER BY `pcount` desc
LIMIT 10
SQL;
        // DB::enableQueryLog();
        $res = DB::select($sql, [
            Poem::class,
            User::class,
            $startOfMonth->format('Y-m-d H:i:s'),
            $endOfMonth->format('Y-m-d H:i:s')
        ]);
        // print_r($res);
        // print_r( DB::getQueryLog());
        array_push($dataMsg, "\n本月上传诗歌 Top 10");
        array_push($dataMsg, "上传数量 用户名");
        foreach ($res as $line) {
            array_push($dataMsg, $this->_pad($line->pcount, 4) . ' ' . str_replace('[from-wechat]', '', $line->name));
        }


        $resPrev = DB::select($sql, [
            Poem::class,
            User::class,
            $startPreviousMonth->format('Y-m-d H:i:s'),
            $endPreviousMonth->format('Y-m-d H:i:s')
        ]);
        // print_r($res);
        // print_r( DB::getQueryLog());
        array_push($dataMsg, "\n{$startPreviousMonth->month}月上传诗歌 Top 10");
        array_push($dataMsg, "上传数量 用户名");
        foreach ($resPrev as $line) {
            array_push($dataMsg, $this->_pad($line->pcount, 4) . ' ' . str_replace('[from-wechat]', '', $line->name));
        }

        $this->_log($poeDB, $chatroom, 'data', null);

        $msg = [
            'code' => 0,
            'poem' => implode("\n", $dataMsg),
            'data' => []
        ];
        if(isset($_GET['poemwiki'])) {
            return implode("\n", $dataMsg);
        }
        return Response::json($msg);
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

        $topMode = preg_match("@top($|\s)@i", $msg);
        $dataMode = in_array($msg, ['数据', 'data']);
        $keyword = $this->getKeywords($msg);

        if (empty($keyword) && !$dataMode && !$topMode) {
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
        if ($topMode) {
            return $this->top($poeDB, $chatroom);
        }

        // TODO add search for translator author name
        $subSql = <<<SQL
SELECT
    (select {$this->factor['dushui']} from wx_post WHERE poem_id=p.id limit 1) as `wx`,
    (select {$this->factor['ugc']}) as `base`,
    IF(`selected_count`, `selected_count`, 0) as times,
    p.score,
    p.`id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
    `from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`,
    `dynasty`, `preface`, `subtitle`, `location`, p.`short_url`,
    `poet_id`, `translator_id`
    FROM `poem` p
    LEFT JOIN `chatroom_poem_selected` selected
    ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
    LEFT JOIN `author` poet_author
    ON (poet_author.id = p.poet_id)
    WHERE
SQL;

        $originWords = '';
        if (is_array($keyword)) {
            $originWords = implode(' ', $keyword);

            foreach ($keyword as $idx => $word) {
                $subSql .= "(
                    `poem` like :keyword1_$idx OR `title` like :keyword2_$idx
                    OR `poet` like :keyword3_$idx OR `poet_cn` like :keyword4_$idx
                    OR `translator` like :keyword5_$idx
                    OR JSON_SEARCH(lower(poet_author.`name_lang`), 'all', :keyword6_$idx )
                ) AND";
            }
            $subSql = trim($subSql, 'AND') . ' AND `length` < :maxLength AND (`need_confirm` IS NULL OR`need_confirm`<>1) AND p.`deleted_at` is NULL';

            $sql = <<<SQL
SELECT  (IF(ISNULL(`wx`), 0, `wx`)+`base`) * IF(ISNULL(score), 1, 1+score/5) / (1+times) as `rank`, t.* FROM (
    $subSql
) as t
ORDER BY `rank` desc, t.times, t.length limit 0,2
SQL;

            $poeDB->prepare($sql);

            $q = $poeDB->prepare($sql);
            foreach ($keyword as $idx => $word) {
                $word = '%' . $word . '%';
                $q->bindValue(":keyword1_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword2_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword3_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword4_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword5_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword6_$idx", "%$word%", PDO::PARAM_STR);
            }

        } else {
            $originWords = $keyword;

            $subSql .= '(
                `poem` like :keyword1 OR `title` like :keyword2
                OR `poet` like :keyword3 OR `poet_cn` like :keyword4
                OR `translator` like :keyword5
                OR JSON_SEARCH(lower(poet_author.`name_lang`), \'all\', :keyword6)
            )';
            $subSql .= ' AND `length` < :maxLength AND (`need_confirm` IS NULL OR `need_confirm`<>1) AND p.`deleted_at` is NULL';
            $sql = <<<SQL
SELECT  (IF(ISNULL(`wx`), 0, `wx`)+`base`) * IF(ISNULL(score), 1, 1+(score-3)/10) / (1+times) as `rank`, t.* FROM (
    $subSql
) as t
ORDER BY `rank` desc, t.times, t.length limit 0,2
SQL;
            $q = $poeDB->prepare($sql);

            $word = '%' . $keyword . '%';
            $q->bindValue(':keyword1', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword2', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword3', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword4', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword5', $word, PDO::PARAM_STR);
            $q->bindValue(":keyword6", $word, PDO::PARAM_STR);
        }

        $q->bindValue(':chatroomId', $chatroom, PDO::PARAM_STR);
        $q->bindValue(':maxLength', $maxLength, PDO::PARAM_INT);

        //$q->debugDumpParams();
        $code = -1;
        $poem = '';
        $data = [];
        $msg = '';
        if ($q->execute()) {
            $code = 0;
            $res = $q->fetchAll(PDO::FETCH_ASSOC);
            $count = count($res);

            // TODO put this into blade
            if ($count == 0) {
                $emoji = Arr::random(['😓', '😅', '😢', '😂', '😭呜呜 ', '', '🙁️', '😫', '😬', '😔', '😊', '😹','🙁','🙃','[裂开]','[苦涩]','[叹气]']);
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

                if($post->poet_id) {
                    $poetAuthor = Author::find($post->poet_id);
                }
                if($post->translator_id) {
                    $translatorAuthor = Author::find($post->translator_id);
                }

                // TODO nation should be $poetAuthor->nation->name_lang
                $nation = isset($poetAuthor) && $poetAuthor->nation
                    ? ($poetAuthor->nation->id !== 32
                        ? "[{$poetAuthor->nation->name_lang}] "
                        : ($poetAuthor->dynasty && $poetAuthor->dynasty->id !== 75
                            ? "[{$poetAuthor->dynasty->name_lang}] "
                            : ''
                        )
                    )
                    : ($post->dynasty
                        ? "[$post->dynasty] "
                        : (($post->nation && $post->nation !== '中国') ? "[$post->nation] " : '')
                    );

                $content = preg_replace('@[\r\n]{3,}@', "\n\n", $post->poem);


                $p = Poem::find($post->id);
                $writer = '作者 / ' . $nation . $p->poetLabel;


                // poem content
                $parts = ['▍ ' . $post->title];
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


                if(isset($translatorAuthor)) {
                    $translator = '翻译 / ' . $translatorAuthor->name_lang;
                    array_push($parts, $translator);
                } else if($translatorName = trim($post->translator)){
                    $translator = '翻译 / ' . $translatorName;
                    array_push($parts, $translator);
                }

                // links & score
                $url = $this->getUrl($p);
                $wikiLink = "\n\n诗歌维基：$url";

                $scoreRepo = new ScoreRepository(app());
                $score = $scoreRepo->calcScoreByPoemId($post->id);
                if ($score['score']) {
                    $wikiScore = '评分：' . "${score['score']} " . str_repeat("🌟", round($score['score'] / 2));
                    array_push($parts, $wikiLink);
                    array_push($parts, $wikiScore);
                } else {
                    $wikiScore = "点这里：$url 做第一个给这首诗打分的人";
                    array_push($parts, "\n\n$wikiScore");
                }

                if($count >= 2 || is_array($keyword)) {
                    $word = is_array($keyword) ? $keyword[0] : $keyword;
                    $more = "\n更多\"$word\"的诗：" . route('search', $word);
                    array_push($parts, $more);
                }

                // convert to simplified chinese
                $od = opencc_open("t2s.json");
                $poem = opencc_convert(implode("\n", $parts), $od);
                opencc_close($od);


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
            $msg = $q->errorInfo();
        }


        $msg = [
            'code' => $code,
            'poem' => $poem,
            'data' => $data,
            'msg' => $msg
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
}
