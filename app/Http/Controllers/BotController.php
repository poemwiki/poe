<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Repositories\ScoreRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO as PDO;
use Response;

class BotController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;

    protected $factor = [
        'dushui' => 1.0,
        'ugc'    => 1
    ];

    public function __construct() {
        // ini_set('memory_limit', '300M');
        // Jieba::init(['mode' => 'default', 'dict' => 'small']);
        // Finalseg::init();
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

        $startOfMonth          = Carbon::now()->startOfMonth();
        $endOfMonth            = Carbon::now()->endOfMonth();
        $monthDuration         = $this->_pad($startOfMonth->format('n.j'), 4) . '~' . $this->_pad($endOfMonth->format('n.j'));
        $startPreviousMonth    = Carbon::now()->startOfMonth()->subMonth();
        $endPreviousMonth      = Carbon::now()->subMonth()->endOfMonth();
        $monthPreviousDuration = $this->_pad($startPreviousMonth->format('n.j'), 4) . '~' . $this->_pad($endPreviousMonth->format('n.j'));

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
        if (isset($_GET['poemwiki'])) {
            return implode("\n", $dataMsg);
        }

        return Response::json($msg);
    }

    private function _log($poeDB, $chatroom, $subject_type, $subject_id) {
        $stmt = $poeDB->prepare('INSERT INTO `bot_reply_log` SET `created_at`=:created_at,
                `subject_id`=:subject_id, `subject_type`=:subject_type, `chatroom_id`=:chatroom_id');
        $stmt->bindValue(':subject_id', $subject_id);
        $stmt->bindValue(':chatroom_id', $chatroom);
        $stmt->bindValue(':created_at', now());
        $stmt->bindValue(':subject_type', $subject_type);
        $stmt->execute();

        if ($stmt->errorCode() !== '00000') {
            Log::error('error while insert to bot reply');
            Log::error($stmt->errorInfo());
        }
    }

    private function _pad($str, $length = 5) {
        return Str::padLeft($str, $length);
    }

    /**
     * @param Poem $poem
     * @return mixed|string
     */
    public function getUrl(Poem $poem) {
        if (!$poem->short_url) {
            $url = 'https://poemwiki.org/p/' . Poem::getFakeId($poem->id);
            Poem::withoutEvents(function () use ($poem, $url) {
                $poem->timestamps = false;
                $poem->short_url  = $url;
                $poem->save();
            });
        }

        return $poem->short_url;
    }

    /**
     * @param Poem $poem
     * @return mixed|string
     */
    public function getWeappUrl(Poem $poem) {
        $shouldRenew = $poem->weapp_url && isset($poem->weapp_url['expire']) && $poem->weapp_url['expire'] < now()->timestamp;
        if (!$poem->weapp_url or $shouldRenew) {
            try {
                $permanent          = $poem->id <= 4000;
                $expireIntervalDays = 30;
                $res                = $permanent ? getPermanentWxUrlLink('id=' . $poem->id)
                    : getTmpWxUrlLink($expireIntervalDays, 'id=' . $poem->id);
                if ($res->errcode) {
                    throw new \Exception('get wxUrlLink error' . $res->errmsg);
                }
                $url = $res->url_link;

                Poem::withoutEvents(function () use ($expireIntervalDays, $permanent, $poem, $url) {
                    $poem->timestamps = false;
                    if ($permanent) {
                        $poem->weapp_url = ['url' => $url];
                    } else {
                        $poem->weapp_url = ['url' => $url, 'expire' => now()->addDays($expireIntervalDays - 1)->timestamp];
                    }
                    $poem->save();
                });
            } catch (Exception $e) {
                return $poem->url;
            }
        }

        return $poem->weapp_url['url'];
    }

    private function _boldNum($str) {
        return str_replace([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], ['𝟎', '𝟏', '𝟐', '𝟑', '𝟒', '𝟓', '𝟔', '𝟕', '𝟖', '𝟗'], $str);
    }

    // get poem uploader wechat id?
    private function getUploader($poemId) {
    }

    public function top($poeDB, $chatroom) {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $poemMsg   = $this->getTopUploader(Poem::class, '诗歌', $startOfMonth, $endOfMonth);
        $authorMsg = $this->getTopUploader(Author::class, '作者', $startOfMonth, $endOfMonth);
        $dataMsg   = array_merge($poemMsg, ["\n"], $authorMsg);

        $this->_log($poeDB, $chatroom, 'top', null);

        $msg = [
            'code' => 0,
            'poem' => implode("\n", $dataMsg),
            'data' => []
        ];
        if (isset($_GET['poemwiki'])) {
            return implode("\n", $dataMsg);
        }

        return Response::json($msg);
    }

    private function getTopUploader($model, $modelName, $startOfMonth, $endOfMonth) {
        $dataMsg = [];

        $monthDuration         = $this->_pad($startOfMonth->format('n.j'), 4) . '~' . $this->_pad($endOfMonth->format('n.j'));
        $startPreviousMonth    = Carbon::now()->startOfMonth()->subMonth();
        $endPreviousMonth      = Carbon::now()->subMonth()->endOfMonth();
        $monthPreviousDuration = $this->_pad($startPreviousMonth->format('n.j'), 4) . '~' . $this->_pad($endPreviousMonth->format('n.j'));

        $monthCount = $model::where('created_at', '>=', $startOfMonth)
            ->where('created_at', '<=', $endOfMonth)
            ->count('id');
        array_push($dataMsg, "$monthDuration 新增{$modelName}数 $monthCount");

        $previousMonthCount = $model::where('created_at', '>=', $startPreviousMonth)
            ->where('created_at', '<=', $endPreviousMonth)
            ->count('id');
        array_push($dataMsg, "$monthPreviousDuration 新增{$modelName}数 $previousMonthCount");

        $total = $model::count('id');
        array_push($dataMsg, "累计{$modelName}数 $total");

        // TOOD including causer_type=AdminUser::class
        $sql = <<<SQL
SELECT causer_id as userID, users.name as `name`, count(distinct subject_id) as `pcount` FROM activity_log as a
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
            $model,
            User::class,
            $startOfMonth->format('Y-m-d H:i:s'),
            $endOfMonth->format('Y-m-d H:i:s')
        ]);
        // print_r($res);
        // print_r( DB::getQueryLog());
        array_push($dataMsg, "\n本月新增{$modelName} Top 10");
        array_push($dataMsg, '新增数量 用户名');
        foreach ($res as $line) {
            array_push($dataMsg, $this->_pad($line->pcount, 4) . ' ' . str_replace('[from-wechat]', '', $line->name));
        }

        $resPrev = DB::select($sql, [
            $model,
            User::class,
            $startPreviousMonth->format('Y-m-d H:i:s'),
            $endPreviousMonth->format('Y-m-d H:i:s')
        ]);
        // print_r($res);
        // print_r( DB::getQueryLog());
        array_push($dataMsg, "\n{$startPreviousMonth->month}月新增{$modelName} Top 10");
        array_push($dataMsg, '新增数量 用户名');
        foreach ($resPrev as $line) {
            array_push($dataMsg, $this->_pad($line->pcount, 4) . ' ' . str_replace('[from-wechat]', '', $line->name));
        }

        return $dataMsg;
    }

    /**
     * Display a listing of the Poem.
     *
     * @param Request $request
     */
    public function index(Request $request) {
        $chatroom  = $request->input('chatroom', '');
        $maxLength = $request->input('maxLength', 600);
        $msg       = $request->input('keyword', '云朵');

        $topMode  = preg_match("@^top($|\s\d+)$@i", $msg);
        $dataMode = in_array($msg, ['数据', 'data']);
        $keyword  = $this->getKeywords($msg);

        if ((empty($keyword) or grapheme_strlen($msg) > 24) && !$dataMode && !$topMode) {
            return Response::json([
                'code' => -2,
                'poem' => '抱歉，没有匹配到关键词。',
                'data' => []
            ]);
        }

        $poeDB = new PDO('mysql:dbname=poe;host=' . config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'), [
                PDO::ATTR_EMULATE_PREPARES => true
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
    (select IF(p.campaign_id, 0, {$this->factor['dushui']})) as `wx`,
    (select {$this->factor['ugc']}) as `base`,
    IF(`selected_count`, `selected_count`, 0) as times,
    p.score,
    p.`id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
    `from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`,
    `dynasty`, `preface`, `subtitle`, `location`, p.`short_url`,
    `poet_id`, `translator_id`, `language_id`
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
            // TODO 如果搜索不含CJK字符，应添加完整的$keyword 作为搜索词
            // if preg_match("@[a-zA-Z]@", $keyword)

            foreach ($keyword as $idx => $word) {
                // TODO remove replace if too slow
                $subSql .= "(
                    replace(replace(`poem`, ' ', ''), '\n', '') like :keyword1_$idx OR replace(`title`,' ', '') like :keyword2_$idx
                    OR `poet` like :keyword3_$idx OR `poet_cn` like :keyword4_$idx
                    OR `translator` like :keyword5_$idx
                    OR JSON_SEARCH(lower(poet_author.`name_lang`), 'all', :keyword6_$idx)
                    OR `subtitle` like :keyword7_$idx
                    OR `preface` like :keyword8_$idx
                ) AND";
            }
            $subSql = trim($subSql, 'AND') . ' AND (`need_confirm` IS NULL OR`need_confirm`<>1) AND p.`deleted_at` is NULL';

            $sql = <<<SQL
SELECT (IF(ISNULL(`wx`), 0, `wx`)+`base`) * IF(ISNULL(score), 1, 1+(score-6)/20) / (1+times) as `rank`, t.* FROM (
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
                $q->bindValue(":keyword7_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword8_$idx", "%$word%", PDO::PARAM_STR);
            }
        } else {
            $originWords = $keyword;

            // TODO remove replace if too slow
            $subSql .= <<<'SQL'
            (
                replace(replace(`poem`, " ", ""), "\n", "") like :keyword1 OR replace(`title`,' ', '')  like :keyword2
                OR `poet` like :keyword3 OR `poet_cn` like :keyword4
                OR `translator` like :keyword5
                OR JSON_SEARCH(lower(poet_author.`name_lang`), 'all', :keyword6)
                OR `subtitle` like :keyword7
                OR `preface` like :keyword8
            )
SQL;
            $subSql .= ' AND (`need_confirm` IS NULL OR `need_confirm`<>1) AND p.`deleted_at` is NULL';
            $sql = <<<SQL
SELECT (IF(ISNULL(`wx`), 0, `wx`)+`base`) * IF(ISNULL(score), 1, 1+(score-6)/20) / (1+times) as `rank`, t.* FROM (
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
            $q->bindValue(':keyword6', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword7', $word, PDO::PARAM_STR);
            $q->bindValue(':keyword8', $word, PDO::PARAM_STR);
        }

        $q->bindValue(':chatroomId', $chatroom, PDO::PARAM_STR);

        // $q->debugDumpParams();
        $code = -1;
        $poem = '';
        $data = [];
        $msg  = '';
        if ($q->execute()) {
            $code  = 0;
            $res   = $q->fetchAll(PDO::FETCH_ASSOC);
            $count = count($res);

            // TODO put this into blade
            if ($count == 0) {
                $emoji    = Arr::random(['😓', '😅', '😢', '😂', '😭呜呜 ', '', '🙁️', '😫', '😬', '😔', '😊', '😹', '🙁', '🙃', '[裂开]', '[苦涩]', '[叹气]']);
                $sorry    = Arr::random(['Sorry', '对不起', '抱歉', '不好意思', '不好意思哈', 'Soooorry']);
                $notFound = Arr::random(['没查到', '没搜着', '没找到', '没找着']);
                $ne       = Arr::random(['相关内容', '', '呢']);
                $welcome  = Arr::random([
                    "欢迎你来上传关于“${originWords}”的诗，",
                    "你来上传一些关于“${originWords}”的诗如何，"
                ]);
                $click = Arr::random(['点这里：', '点击：', '👉', '➡️']);
                $link  = 'poemwiki.org/new';
                $poem  = "$emoji ${sorry}，${notFound}${ne}。${welcome}\n${click}$link";
            } else {
                $data = $res[0];
                $post = Poem::find($res[0]['id']);

                $isSubstr = $post->length > ($post->language_id === 1 ? $maxLength : $maxLength * 6);

                if ($post->poet_id) {
                    $poetAuthor = Author::find($post->poet_id);
                }
                if ($post->translator_id) {
                    $translatorAuthor = Author::find($post->translator_id);
                }

                $writer = '作者 / ' . $post->poetLabelCn;

                // poem content
                $parts = [];
                if (!$isSubstr) {
                    array_push($parts, '▍ ' . $post->title);
                    if ($post->subtitle) {
                        array_push($parts, $post->subtitle);
                    }
                    if ($post->preface) {
                        array_push($parts, '        ' . $post->preface);
                    }
                }

                if ($isSubstr) {
                    $keywordArr   = is_array($keyword) ? $keyword : Str::of($keyword)->split('#\s+#')->toArray();
                    $posOnPoem    = str_pos_one_of($post->poem, $keywordArr, 1);
                    $subLength    = $post->language_id === 1 ? 160 : 300;
                    $subPreLength = $post->language_id === 1 ? 40 : 60;
                    if ($posOnPoem) {
                        $pos     = $posOnPoem['pos'];
                        $content = Str::of($post->poem)->substr($pos - min($subPreLength, $pos), $subLength)
                            ->replaceMatches("@^.{0,$subPreLength}\n+@", "……\n")
                            ->replaceMatches("@\n+.+$@", "\n……")->__toString();
                    } else {
                        $content = Str::of($post->poem)->substr(0, $subLength)
                            ->replaceMatches("@\n.+$@", "\n……")->__toString();
                    }
                } else {
                    $content = $post->poem;
                }

                array_push($parts, ($isSubstr ? '' : "\n") . $content . "\n");

                // poem's other properties

                $timeStr = '';
                if ($post->year) {
                    $timeStr .= $post->year;
                }
                if ($post->year && $post->month) {
                    $timeStr .= '.';
                }
                if ($post->month) {
                    $timeStr .= $post->month;
                }
                if ($post->month && $post->date) {
                    $timeStr .= '.';
                }
                if ($post->date) {
                    $timeStr .= $post->date;
                }

                if ($post->location) {
                    if ($timeStr) {
                        array_push($parts, "$timeStr, $post->location\n");
                    } else {
                        array_push($parts, $post->location . "\n");
                    }
                } elseif ($timeStr) {
                    array_push($parts, $timeStr . "\n");
                }

                array_push($parts, $writer);

                if ($post->translators->count()) {
                    $translator = '翻译 / ' . array_reduce($post->translatorsLabelArr, function ($carry, $t) {
                        return $carry . ($carry ? ', ' : '') . $t['name'];
                    }, '');
                    array_push($parts, $translator);
                } elseif (isset($translatorAuthor)) {
                    $translator = '翻译 / ' . $translatorAuthor->name_lang;
                    array_push($parts, $translator);
                } elseif ($translatorName = trim($post->translator)) {
                    $translator = '翻译 / ' . $translatorName;
                    array_push($parts, $translator);
                }

                array_push($parts, "\n");
                if ($isSubstr) {
                    array_push($parts, "节选自 《{$post->title}》");
                }

                // links & score
                $url      = $this->getUrl($post);
                $wikiLink = ($isSubstr ? '查看全文：' : '诗歌维基：') . $url;

                $scoreRepo = new ScoreRepository(app());
                $score     = $scoreRepo->calcScoreByPoemId($post->id);
                if ($score['score']) {
                    $wikiScore = '评分：' . "${score['score']} " . str_repeat('🌟', round($score['score'] / 2));
                    array_push($parts, $wikiLink);
                    array_push($parts, $wikiScore);
                } else {
                    $wikiScore = "点这里：$url " . ($isSubstr ? '查看全文' : '消灭零评分');
                    array_push($parts, "$wikiScore");
                }

                if ($count >= 2 || is_array($keyword)) {
                    $word = is_array($keyword) ? $keyword[0] : $keyword;
                    $more = "\n更多\"$word\"的诗：" . route('search', $word);
                    array_push($parts, $more);
                }

                // convert to simplified chinese
                // $od   = opencc_open('t2s.json');
                // $poem = opencc_convert(implode("\n", $parts), $od);
                // opencc_close($od);

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
            $msg = $q->errorInfo();
        }

        $msg = [
            'code' => $code,
            'poem' => $poem,
            'data' => $data,
            'msg'  => $msg
        ];

        return Response::json($msg);
    }

    /**
     * @param PDO $db
     * @param     $poemID
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
     * @param bool   $divide
     * @return string[]|string
     */
    private function getKeywords($str) {
        $str     = trim(preg_replace('@[[:punct:]\n\r～｜　\s]+@u', ' ', $str));
        $keyword = '';
        $matches = [];
        preg_match('@^([搜捜]索??|search)(一下|一首)??\s*?(?<keyword>.*)(的?((古|现代)?诗歌?|词))?$@Uu', $str, $matches);
        if (isset($matches['keyword'])) {
            $keyword = trim($matches['keyword']);
        } else {
            $matches = [];
            preg_match('@^(有没有??|告诉我|帮我找|我想要|(给我来|给我|来)|搜索?)(一首|一下)??((和|跟|带|包?含)有??)??\s*?(?<keyword>.*)((有关|相关)?的?((十四行|十六行|古|现代)?诗歌?|词))$@Uu', $str, $matches);
            $keyword = isset($matches['keyword']) ? trim($matches['keyword']) : '';
        }

        return strstr($keyword, ' ')
            ? explode(' ', $keyword)
            : $keyword;
    }
}
