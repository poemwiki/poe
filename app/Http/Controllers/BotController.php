<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePoemRequest;
use App\Http\Requests\UpdatePoemRequest;
use App\Models\Language;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Repositories\ScoreRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Response;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use \PDO as PDO;

class BotController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct() {
        ini_set('memory_limit', '300M');
        Jieba::init(array('mode' => 'default', 'dict' => 'small'));
        Finalseg::init();
    }

    /**
     * Display a listing of the Poem.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request) {
        $chatroom = $request->input('chatroom', '');
        $maxLength = $request->input('maxLength', 800);
        $msg = $request->input('keyword', '云朵');

        $keyword = $this->getKeywords($msg);

        if (empty($keyword)) {
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

        $originWords = '';
        if (is_array($keyword)) {
            $originWords = implode(' ', $keyword);
            $sql = 'SELECT `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
`from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`, `dynasty`, `preface`, `subtitle`
        FROM `poem` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE ';
            foreach ($keyword as $idx => $word) {
                $sql .= "(`poem` like :keyword1_$idx OR `title` like :keyword2_$idx
        OR `poet` like :keyword3_$idx OR `poet_cn` like :keyword4_$idx OR `translator` like :keyword5_$idx) AND";
            }
            $sql = trim($sql, 'AND') . ' AND `length` < :maxLength AND (`need_confirm` IS NULL OR`need_confirm`<>1)
        ORDER BY `selected_count`,`last_selected_time`,length(`poem`) limit 0,1';
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
        SELECT `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
`from`, `year`, `month` , `date`, `bedtime_post_id`, `selected_count`,`last_selected_time`, `dynasty`, `preface`, `subtitle`
        FROM `poem` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE (`poem` like :keyword1 OR `title` like :keyword2
        OR `poet` like :keyword3 OR `poet_cn` like :keyword4 OR `translator` like :keyword5) AND `length` < :maxLength AND (`need_confirm` IS NULL OR `need_confirm`<>1)
        ORDER BY `selected_count`,`last_selected_time`,length(`poem`) limit 0,1
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

            // TODO put this into blade
            if (count($res) == 0) {
                $emoji = Arr::random(['😓', '😅', '😢', '😂', '😭呜呜 ', '', '🙁️', '😫', '😶', '😬', '😔', '😒', '😠', '😊', '😹','🙁','🙃']);
                $sorry = Arr::random(['Sorry', '对不起', '抱歉', '不好意思', '不好意思哈', 'Soooorry']);
                $notFound = Arr::random(['没查到', '没搜着', '没找到', '没找着']);
                $ne = Arr::random(['相关内容', '', '呢']);
                $welcome = Arr::random([
                    "欢迎你来上传关于“${originWords}”的诗，",
                    "你来这里上传一些关于“${originWords}”的诗如何，",
                    "你来这里上传一些关于“${originWords}”的诗如何，",
                ]);
                $click = Arr::random(['点这里：','点击：','👉','➡️']);
                $link = 'poemwiki.org/new';
                $poem = "$emoji ${sorry}，${notFound}${ne}。${welcome}\n${click}$link";
            } else {
                $data = $res[0];
                $post = (object)$res[0];

                $wxPost = $this->findWxPost($poeDB, $post->id);
                $data['wxPost'] = $wxPost;

                $nation = $post->dynasty
                    ? "[$post->dynasty] "
                    : (($post->nation && $post->nation !== '中国') ? "[$post->nation] " : '');

                $content = preg_replace('@[\r\n]{3,}@', "\n\n", $post->poem);

                $writer = '作者 / ' .($post->poet_cn
                    ?  $nation . ($post->poet_cn ?? $post->poet)
                    : ($post->poet ? $post->poet : ''));


                // poem content
                $parts = ['▍ ' . $post->title];
                if($post->preface) array_push($parts, '        '. $post->preface);
                if($post->subtitle) array_push($parts, "\n    ".$post->subtitle);
                array_push($parts, "\n".$content."\n");

                // poem's other properties
                array_push($parts, $writer);

                $timeStr = '';
                if ($post->year) $timeStr .= $post->year . '年';
                if ($post->month) $timeStr .= $post->month . '月';
                if ($post->date) $timeStr .= $post->date . '日';
                if ($timeStr <> '') array_push($parts, $timeStr);

                if ($post->translator) array_push($parts, '翻译 / ' . trim($post->translator));
                if (!empty($wxPost) && isset($wxPost['recommender'])) array_push($pars, '评论 / ' . $wxPost['recommender']);

                // links & score
                $url = (isset($post->length) && $post->length > 500)
                    ? "https://poemwiki.org/" . $post->id
                    : "poemwiki.org/" . $post->id;
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
            }
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
}
