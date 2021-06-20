<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreOwnerUploaderPoem;
use App\Models\Author;
use App\Models\Poem;
use App\Models\Tag;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use EasyWeChat\Factory;
use Exception;
use File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class PoemAPIController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;
    /** @var  ReviewRepository */
    private $reviewRepository;
    /** @var  ScoreRepository */
    private $scoreRepository;

    public function __construct(PoemRepository $poemRepository, ReviewRepository $reviewRepository, ScoreRepository $scoreRepository) {
        $this->poemRepository = $poemRepository;
        $this->reviewRepository = $reviewRepository;
        $this->scoreRepository = $scoreRepository;
    }

    // TODO return poem.id only if client don't need
    public function campaignIndex(Request $request) {
        $tagId = $request->input('tagId');
        return $this->responseSuccess($this->poemRepository->getCampaignPoemsByTagId($tagId));
    }

    // TODO this is a deprecated method
    public function index(Request $request) {
        // TODO pagination
        $tagId = $request->input('tagId');
        $orderBy = $request->input('orderBy', 'created_at');
        if(!is_numeric($tagId)) {
            return $this->responseFail();
        }

        $data = $this->poemRepository->getByTagId($tagId, $orderBy);
        if($orderBy === 'score') {
            $limit = Tag::find($tagId)->campaign->settings['rank_min_weight'] ?? 3;
            $data = $data->filter(function ($value) use ($limit) {
                // 票数不足的不参与排名
                return $value['score_count'] >= $limit;
            })->sort(function ($a, $b) {
                $score = $b['score'] <=> $a['score'];
                return $score === 0 ? $b['score_count'] <=> $a['score_count'] : $score;
            })->values()->map(function ($item, $index) {
                $item = $item->toArray();
                $item['rank'] = $index + 1;
                return $item;
            });
        }
        return $this->responseSuccess($data);
    }

    public function random($num = 5, $id = null) {
        $num = min(10, $num);

        $columns = [
            'id', 'created_at', 'title', 'subtitle', 'preface', 'poem', 'poet', 'poet_cn', 'poet_id',
            'dynasty_id', 'nation_id', 'language_id', 'is_original', 'original_id', 'created_at',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded',
            'reviews', 'reviews_count', 'date_ago', 'poet_avatar', 'translator_avatar',
            'score', 'score_count', 'score_weight'
        ];
        $reviewColumn = [
            'avatar', 'content', 'created_at', 'id', 'user_id', 'name', 'poem_id'//, 'title'
        ];

        $noScoreNum = 2;
        $poems = $this->poemRepository->suggest($num - $noScoreNum, ['reviews'])
            ->whereNull('campaign_id')
            ->where('score', '>=', 7)
            ->get();
        $noScorePoems = $this->poemRepository->suggest($noScoreNum, ['reviews'])
            ->whereNull('campaign_id')
            ->whereNull('score')
            ->get();
        $poems = $poems->concat($noScorePoems);

        if($id) {
            $poemById = $this->poemRepository->findMany([$id]);
            if($poemById) {
                $poems = $poemById->concat($poems);//;
            }
        }

        $res = [];
        foreach($poems as $poem) {
            $item = $poem->only($columns);

            $score = $poem->scoreArray;
            $item['score'] = $score['score'];
            $item['score_count'] = $score['count'];
            $item['date_ago'] = date_ago($poem->created_at);
            $item['poet'] = $poem->poet_label;
            $item['poet_cn'] = $poem->poet_label_cn;
            $item['poet_avatar_true'] = $poem->poet_avatar !== asset(Author::$defaultAvatarUrl);
            $item['translator_avatar_true'] = $poem->translator_avatar !== asset(Author::$defaultAvatarUrl);
            $item['poet_is_v'] = ($poem->poetAuthor && $poem->poetAuthor->user && $poem->poetAuthor->user->is_v);
            $item['translator_label'] = $poem->translator_label;
            $item['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);
            $item['reviews_count'] = $poem->reviews->count();
            $item['reviews'] = $poem->reviews->take(1)->map(function ($review) use ($reviewColumn) {
                $review->content = $review->pureContent;
                return $review->makeHidden('user')->only($reviewColumn);
            });
            $res[] = $item;
        };

        return $this->responseSuccess($res);
    }

    public function mine(Request $request) {
        return $this->responseSuccess($this->poemRepository->getByOwner($request->user()->id));
    }

    public function relatedAll(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id));
    }

    /**
     * get related poem which has related campaign
     * @param Request $request
     * @return array
     */
    public function related(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id, true));
    }

    public function detail($id) {
        $columns = [
            'id', 'created_at', 'title', 'subtitle', 'preface', 'poem',
            'poet', 'poet_cn', 'poet_id', 'poet_avatar', 'translator_avatar',
            'dynasty_id', 'nation_id', 'language_id', 'is_original', 'original_id', 'created_at',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded', 'share_pics',
            'campaign_id'
        ];
        /** @var Poem $poem */
        $poem = Poem::where('id', '=', $id)->first();
        if(!$poem) {
            abort(404);
            return $this->responseFail([], 'not found', Controller::$CODE['no_entry']);
        }
        $res = $poem->only($columns);

        $user = request()->user();

        if($user) {
            $myScore = $this->scoreRepository->listByUserId($user->id)
                ->where('poem_id', '=', $poem->id)
                ->first();
            $res['my_score'] = $myScore ? $myScore->score : null;
        }

        $res['poet'] = $poem->poet_label;
        $res['poet_cn'] = $poem->poet_label_cn;
        $res['poet_avatar_true'] = $poem->poet_avatar !== asset(Author::$defaultAvatarUrl);
        $res['translator_avatar_true'] = $poem->translator_avatar !== asset(Author::$defaultAvatarUrl);
        $res['poet_is_v'] = ($poem->poetAuthor && $poem->poetAuthor->user && $poem->poetAuthor->user->is_v);
        $res['translator_label'] = $poem->translator_label;
        $res['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);
        $res['date_ago'] = date_ago($poem->created_at);

        if (!$res['poet_id'] && $poem->is_owner_uploaded===Poem::$OWNER['uploader'] && $poem->uploader) {
            if($poem->uploader->author) {
                $res['poet_id'] = $poem->uploader->author->id;
                $res['poet_is_v'] = $poem->uploader->is_v;
            }
        }

        if($res['poet_id'] === 1033 or ($poem->is_owner_uploaded===1 && $poem->upload_user_id===4806)) {
            $res['sell'] = [
                'path' =>  "pages/item/detail/detail?cover=https%3A%2F%2Fm.360buyimg.com%2Fmobilecms%2Fs300x300_jfs%2Ft1%2F163742%2F18%2F11927%2F161606%2F604ae078E6e2ffbba%2Fb15e1a464fea9201.jpg!q70.jpg&price=33.3&name=%E5%A4%9C%E8%A1%8C%E5%88%97%E8%BD%A6&from=wxshare_3&sku=13144162&fb=0",
                "appId" => "wx91d27dbf599dff74",
                "picUrl" => "https://poemwiki.org/images/campaign/9/sell.jpg"
            ];
        }

        $liked_review_ids = [];

        $res['score'] = $poem->totalScore;
        // TODO save score_count to poem.score_count column
        $res['score_count'] = ScoreRepository::calcCount($id);

        DB::enableQueryLog();
        $q = $this->reviewRepository->listByOriginalPoem($poem);

        $reviewColumns = ['review.id', 'review.content', 'review.created_at', 'review.user_id', 'review.like', 'review.reply_id'];
        if($user) {
            $q->leftJoin('likes', function($join) use ($user) {
                $join->on('review.id', '=', 'likes.likeable_id')
                    ->where('likes.user_id', '=', $user->id)
                    ->where('likes.likeable_type', '=', \App\Models\Review::class);
            });
            $reviewColumns[] = 'likes.likeable_id';
        }

        $res['reviews'] = $q->get($reviewColumns)
            ->map(function ($review) use ($user, &$liked_review_ids) {

            $review->makeHidden('user');

            if($review->likeable_id) array_push($liked_review_ids, $review->likeable_id);

            $review['date_ago'] = date_ago($review->created_at);
            $review['content'] = $review->pureContent;
            return $review->only(['id', 'content', 'created_at', 'user_id', 'like', 'reply_id', 'name', 'avatar', 'reply_to_user', 'date_ago']);
        });
        $res['liked_review_ids'] = $liked_review_ids;

        $relatedQuery = $this->poemRepository->suggest(2)
            ->where('id', '<>', $id);

        $res['is_campaign'] = $poem->is_campaign;
        if($poem->tags->count()) {
            $res['tags'] = $poem->tags->map->only(['id', 'name', 'category_id']);

            if($poem->is_campaign) {
                $relatedQuery->whereHas('tags', function ($query) use ($poem) {
                    // TODO use campaign tag here
                    $query->where('tag_id', '=', $poem->tags[0]->id);
                });
            } else {
                $relatedQuery->where('score', '>=', 7);
            }
        }
        if($poem->share_pics && isset($poem->share_pics['pure'])) {
            if(File::exists(storage_path($poem->share_pics['pure']))) {
                $res['share_image'] = route('poem-card', [
                    'id' => $poem->id,
                    'compositionId' => 'pure'
                ]);
            }
        }

        $res['related'] = $relatedQuery->get([
            'id', 'poem', 'poet', 'poet_cn', 'poet_id', 'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded'
        ])
            ->map(function ($item) {
                $arr = $item->toArray();
                $arr['poet'] = $item->poet_label;
                return $arr;
            })
            ->toArray();

        return $this->responseSuccess($res);
    }

    public function store(StoreOwnerUploaderPoem $request) {
        $sanitized = $request->getSanitized();

        $wechatApp = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret'),
            'response_type' => 'object',
        ]);
        $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['poem']);
        if($result->errcode) {
            return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
        }

        $poem = Poem::create($sanitized);
        $tag = Tag::find($sanitized['tag_id']);
        if($tag) {
            $poem->tags()->save($tag);
            if($tag->is_campaign) {
                $poem->campaign_id = $tag->campaign->id;
                $poem->save();
            }
        }


        return $this->responseSuccess();
    }

    public function delete($poemId) {
        try {
            $this->authorize('api.poem.delete', Poem::find($poemId));
        } catch (AuthorizationException $e) {
            return $this->responseFail();
        }

        $poem = Poem::find($poemId);
        $poem->delete();

        return $this->responseSuccess();
    }

    // TODO for mini program, generate and get poster image file should be in ONE request
    public function poster(int $poemId, $force=null) {

    }

    // TODO poster image generation process should be a command, and invoke after poem created
    // TODO regenerate poster when related poem fields updated
    public function share(int $poemId) {
        $poem = Poem::find($poemId);
        $force = isset($_GET['force']);// || config('app.env') === 'local';

        $compositionId = 'pure';
        $posterUrl = route('poem-card', [
            'id' => $poemId,
            'compositionId' => $compositionId
        ]);

        $postData = ['compositionId' => $compositionId, 'id' => $poem->id, 'poem' => $poem->poem, 'poet' => $poem->poetLabel, 'title' => $poem->title];
        $hash = crc32(json_encode($postData));
        if(!$force && $poem->share_pics && isset($poem->share_pics[$compositionId])
            && file_exists(storage_path($poem->share_pics[$compositionId]))) {
            // TODO if posterUrl not contain $hash, remove old poem and poster file and generate new

            return $this->responseSuccess(['url' => $posterUrl]);
        }


        $relativeStoreDir = 'app/public/poem-card/' . $poem->id;
        $dir = storage_path($relativeStoreDir);
        if(!is_dir($dir)) {
            mkdir($dir);
        }

        // img file name will change if postData change
        $posterStorePath = "{$relativeStoreDir}/poster_{$compositionId}_{$hash}.png";
        $posterPath = storage_path($posterStorePath); // posterImg = poemImg + appCodeImg
        $poemImgFileName = "poem_{$compositionId}_{$hash}.png"; // main part of poster

        $postData['force'] = $force;

        try {
            $poemImgPath = $this->fetchPoemImg($postData, $dir, $poemImgFileName, $force);

            $scene = $poem->is_campaign ? ($poem->campaign_id . '-' . $poem->id) : $poem->id;
            $page = $poem->is_campaign ? 'pages/campaign/campaign' : 'pages/poems/index';
            $appCodeImgPath = $this->fetchAppCodeImg($scene, $dir, $page, $force);

            if(!$this->composite($poemImgPath, $appCodeImgPath, $posterPath)) {
                return $this->responseFail();
            }

            $sharePics = $poem->share_pics ?? [];
            $sharePics[$compositionId] = $posterStorePath;
            $poem->share_pics = $sharePics;
            $poem->save();
            return $this->responseSuccess(['url' => $posterUrl]);

        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return $this->responseFail();
        }
    }

    /**
     * @param $postData
     * @param $dir
     * @param $poemImgFileName
     * @param bool $force
     * @return string
     * @throws Exception
     */
    private function fetchPoemImg($postData, string $dir, string $poemImgFileName, bool $force=false) {
        $poemImgPath = $dir .'/'. $poemImgFileName;
        if(!$force && file_exists($poemImgPath)) {
            return $poemImgPath;
        }

        $poemImg = file_get_contents_post(config('app.render_server'), $postData, 'application/x-www-form-urlencoded', 30);
        if(file_put_contents($poemImgPath, $poemImg)) {
            if(File::mimeType($poemImgPath) == 'text/plain') {
                unlink($poemImgPath);
                throw new Exception('生成图片失败，请稍后再试。');
            }

            return $poemImgPath;
        }
        throw new Exception('图片写入失败，请稍后再试。');
    }

    /**
     * @param string $scene
     * @param string $page
     * @param string $appCodeImgDir
     * @param bool $force
     * @param string $appCodeFileName
     * @return string|false
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function fetchAppCodeImg(string $scene, string $appCodeImgDir, string $page = 'pages/detail/detail', bool $force=false, string $appCodeFileName='app-code.jpg') {
        $app = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret')
        ]);
        // 注意微信对此接口调用频率有限制
        $response = $app->app_code->getUnlimit($scene, [
            'page' => $page,
            'width' => 280,
            'is_hyaline' => false
        ]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $response->saveAs($appCodeImgDir, $appCodeFileName);
            return $appCodeImgDir.'/'.$appCodeFileName;
        }
        return false;
    }

    /**
     * composite poem image and appCode image
     * @param string $poemImgPath
     * @param string $appCodeImgPath
     * @param string $posterPath
     * @param int $quality
     * @return bool
     * @throws Exception
     */
    private function composite(string $poemImgPath, string $appCodeImgPath, string $posterPath, int $quality=100): bool {
        // 绘制小程序码
        // 覆盖海报右下角小程序码区域
        $posterImg = img_overlay($poemImgPath, $appCodeImgPath, 0, 0, 120, 120);

        $imgType = exif_imagetype($poemImgPath);
        if ($imgType === IMAGETYPE_JPEG) {
            $res = imagejpeg($posterImg, $posterPath, $quality);

        } else if($imgType === IMAGETYPE_PNG){
            //see: http://stackoverflow.com/a/7878801/1596547
            $res = imagepng($posterImg, $posterPath, $quality*9/100);

        } else if($imgType === IMAGETYPE_GIF){
            $res = imagegif($posterImg, $posterPath);

        } else {
            throw new Exception('image type not supported');
        }

        imagedestroy($posterImg);
        return $res;
    }
}
