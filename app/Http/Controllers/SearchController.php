<?php

namespace App\Http\Controllers;

use App\Repositories\PoemRepository;
use Illuminate\Http\Request;
use Flash;
use Response;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;

class SearchController extends Controller
{
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo)
    {
        $this->poemRepository = $poemRepo;
    }

    /**
     * Display a listing of the Poem.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request) {


    }

    public function run() {

        require_once dirname(__FILE__).'/functions.php';


        $keyword = isset($_GET['keyword']) ? getKeywords($_GET['keyword']) : '云朵';

        $poeDB = new PDO('mysql:dbname=poe;host=39.106.216.79', 'ray', 'testset', [
            PDO::ATTR_EMULATE_PREPARES   => TRUE
        ]);

        if (is_array($keyword)) {
            $sql = 'SELECT `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`,
`from`, `year`, `bedtime_post_id`, `selected_count`,`last_selected_time`
        FROM `poem_wiki` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE ';
            foreach ($keyword as $idx => $word) {
                $sql .= "(`poem` like :keyword1_$idx OR `title` like :keyword2_$idx
        OR `poet` like :keyword3_$idx OR `translator` like :keyword4_$idx) AND";
            }
            $sql = trim($sql, 'AND') . ' AND `length` < :maxLength AND (`need_confirm` IS NULL OR`need_confirm`<>1)
        ORDER BY `selected_count`,`last_selected_time`,length(`poem`) limit 0,1';
            $poeDB->prepare($sql);

            $q = $poeDB->prepare($sql);
            foreach ($keyword as $idx => $word) {
                $q->bindValue(":keyword1_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword2_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword3_$idx", "%$word%", PDO::PARAM_STR);
                $q->bindValue(":keyword4_$idx", "%$word%", PDO::PARAM_STR);
            }

        } else {
            $q = $poeDB->prepare(<<<'SQL'
        SELECT `id`, `title`, `nation`, `poet`, `poet_cn`, `poem`, `translator`, `length`
`from`, `year`, `bedtime_post_id`, `selected_count`,`last_selected_time`
        FROM `poem_wiki` p
        LEFT JOIN `chatroom_poem_selected` selected
        ON (selected.chatroom_id = :chatroomId and p.id=selected.poem_id)
        WHERE (`poem` like :keyword1 OR `title` like :keyword2
        OR `poet` like :keyword3 OR `translator` like :keyword4) AND `length` < :maxLength AND (`need_confirm` IS NULL OR `need_confirm`<>1)
        ORDER BY `selected_count`,`last_selected_time`,length(`poem`) limit 0,1
SQL
            );
            $keyword = '%' . $keyword . '%';
            $q->bindValue(':keyword1', $keyword, PDO::PARAM_STR);
            $q->bindValue(':keyword2', $keyword, PDO::PARAM_STR);
            $q->bindValue(':keyword3', $keyword, PDO::PARAM_STR);
            $q->bindValue(':keyword4', $keyword, PDO::PARAM_STR);
        }

        $q->bindValue(':chatroomId', $chatroom, PDO::PARAM_STR);
        $q->bindValue(':maxLength', $maxLength, PDO::PARAM_INT);

        //$q->debugDumpParams();
        $code = -1;
        $poem = '';
        $data = [];
        if($q->execute()) {
            $code = 0;
            $res = $q->fetchAll(PDO::FETCH_ASSOC);
            if(count($res) == 0) {
                $poem = '抱歉，没有查到相关内容。';
            } else {
                $data = $res[0];
                $post = (object)$res[0];
                $wxPost = $this->findWxPost($poeDB, $post->id);
                $data['wxPost'] = $wxPost;

                $nation = $post->dynasty
                    ? "[$post->dynasty] "
                    : ($post->nation ? "[$post->nation] " : '');

                $content = preg_replace('@[\r\n]{3,}@', "\n\n", $post->poem);

                $writer = $post->poet_cn
                    ? '作者 / '. $nation . $post->poet_cn
                    : ($post->poet ? $post->poet : '');

                $parts = [
                    '▍ '.$post->title."\n",
                    $content."\n",
                    $writer
                ];
                if($post->year) array_push($parts, $post->year);
                if($post->translator) array_push($parts, '翻译 / '.trim($post->translator));
                if(!empty($wxPost) && $wxPost['recommender']) array_push($pars,'评论 / '.$wxPost['recommender']);

                $poem = implode("\n", $parts);

                if($post->last_selected_time) {
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

        echo json_encode($msg);


    }

    /**
     * @param PDO $db
     * @param $poemID
     * @return array|object
     */
    private function findWxPost(PDO $db, $poemID) {
        $sql = <<<'SQL'
SELECT wx.*,p.bedtime_post_id FROM wx_post wx RIGHT JOIN poem_wiki p
ON p.bedtime_post_id=wx.bedtime_post_id WHERE p.id=:poemID;
SQL;
        $q = $db->prepare($sql);
        $q->bindValue(':poemID', $poemID, PDO::PARAM_INT);
        if (!$q->execute()) {
            return [];
        }

        return $q->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    private function getKeywords() {
        ini_set('memory_limit', '300M');

        Jieba::init(array('mode'=>'default','dict'=>'small'));
        Finalseg::init();

        /**
         * @param string $str
         * @param boolean $divide
         * @return string[]|string
         */
        function getKeyword($str, $divide = false) {
            $str = trim(preg_replace('@[[:punct:]\n\r～｜　\s]+@u', ' ', $str));
            $keyword = '';
            $matches = [];
            preg_match('@^(搜索??|search)(一下|一搜|一首|一个)??\s*?(?<keyword>.*)(的?((古|现代)?诗歌?|词))?$@Uu', $str, $matches);
            if(isset($matches['keyword'])) {
                $keyword = trim($matches['keyword']);
            } else {
                $matches = [];
                preg_match('@^(有没有??|告诉我|帮我找|我想要|(给我来|给我|来)|搜索?)(一首|(一|那|哪)?个|一下)??((和|跟|带|包?含)有??)??\s*?(?<keyword>.*)((有关|相关)?的?((十四行|十六行|古|现代)?诗歌?|词))$@Uu', $str, $matches);
                $keyword = isset($matches['keyword']) ? trim($matches['keyword']) : '';
            }

            if($divide) {
                return Jieba::cut($keyword);
            }

            return strstr($keyword, ' ')
                ? explode(' ', $keyword)
                : $keyword;
        }

        /**
         * @param $str
         * @return string|string[]
         */
        function noTag($str) {
            if (is_array($str)) {
                return array_map('noTag', $str);
            }
            return trim(strip_tags($str));
        }
    }
}
