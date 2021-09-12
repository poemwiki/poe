<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreOwnerUploaderPoem;
use App\Models\Author;
use App\Models\Entry;
use App\Models\Poem;
use App\Models\Tag;
use App\Query\AuthorAliasSearchAspect;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use App\Rules\ValidPoemContent;
use EasyWeChat\Factory;
use Exception;
use File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;
use Spatie\Searchable\SearchResult;

/**
 * Class LanguageController.
 */
class PoemAPIController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;
    /** @var ReviewRepository */
    private $reviewRepository;
    /** @var ScoreRepository */
    private $scoreRepository;

    public function __construct(PoemRepository $poemRepository, ReviewRepository $reviewRepository, ScoreRepository $scoreRepository) {
        $this->poemRepository   = $poemRepository;
        $this->reviewRepository = $reviewRepository;
        $this->scoreRepository  = $scoreRepository;
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
        $poems      = $this->poemRepository->suggest($num - $noScoreNum, ['reviews'])
            ->whereNull('campaign_id')
            ->where('score', '>=', 7)
            ->get();
        $noScorePoems = $this->poemRepository->suggest($noScoreNum, ['reviews'])
            ->whereNull('campaign_id')
            ->whereNull('score')
            ->get();
        $poems = $poems->concat($noScorePoems);

        if ($id) {
            $poemById = $this->poemRepository->findMany([$id]);
            if ($poemById) {
                $poems = $poemById->concat($poems);
            }
        }

        $res = [];
        foreach ($poems as $poem) {
            $item = $poem->only($columns);

            $score                          = $poem->scoreArray;
            $item['score']                  = $score['score'];
            $item['score_count']            = $score['count'];
            $item['date_ago']               = date_ago($poem->created_at);
            $item['poet']                   = $poem->poet_label;
            $item['poet_cn']                = $poem->poet_label_cn;
            $item['poet_avatar_true']       = $poem->poet_avatar !== config('app.avatar.default');
            $item['translator_avatar_true'] = $poem->translator_avatar !== config('app.avatar.default');
            $item['poet_is_v']              = $poem->poet_is_v;

            $item['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);
            $translatorLabels        = [];
            $item['translators']     = $poem->translators->map(function ($translator) use (&$translatorLabels) {
                if ($translator instanceof Author) {
                    $translatorLabels[] = $translator->label;

                    return [
                        'name'        => $translator->label,
                        'id'          => $translator->id,
                        'avatar'      => $translator->avatar_url,
                        'avatar_true' => $translator->avatar_url !== config('app.avatar.default')
                    ];
                } elseif ($translator instanceof Entry) {
                    $translatorLabels[] = $translator->name;

                    return ['name' => $translator->name];
                }
            });

            $item['translator_label'] = count($translatorLabels) ? join(', ', $translatorLabels) : $poem->translator_label;

            $item['reviews_count'] = $poem->reviews->count();
            $item['reviews']       = $poem->reviews->take(1)->map(function ($review) use ($reviewColumn) {
                $review->content = $review->pureContent;

                return $review->makeHidden('user')->only($reviewColumn);
            });

            $res[] = $item;
        }

        return $this->responseSuccess($res);
    }

    public function mine(Request $request) {
        return $this->responseSuccess($this->poemRepository->getByOwner($request->user()->id));
    }

    public function relatedAll(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id, 100));
    }

    /**
     * get related poem which has related campaign.
     * @param Request $request
     * @return array
     */
    public function related(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id, 100, true));
    }

    public function detail($id) {
        $columns = [
            'id', 'created_at', 'title', 'subtitle', 'preface', 'poem',
            'poet', 'poet_cn', 'poet_id', 'poet_avatar', 'translator_avatar',
            'subtitle', 'preface', 'date', 'month', 'year', 'location',
            'dynasty_id', 'nation_id', 'language_id', 'is_original', 'original_id', 'created_at',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded', 'share_pics',
            'campaign_id'
        ];
        /** @var Poem $poem */
        $poem = Poem::where('id', '=', $id)->first();
        if (!$poem) {
            abort(404);

            return $this->responseFail([], 'not found', Controller::$CODE['no_entry']);
        }
        $res = $poem->only($columns);

        $user = request()->user();

        if ($user) {
            $myScore = $this->scoreRepository->listByUserId($user->id)
                ->where('poem_id', '=', $poem->id)
                ->first();
            $res['my_score'] = $myScore ? $myScore->score : null;
        }

        $res['poet']                   = $poem->poet_label;
        $res['poet_avatar_true']       = $poem->poet_avatar !== config('app.avatar.default');
        $res['translator_avatar_true'] = $poem->translator_avatar !== config('app.avatar.default');
        $res['poet_is_v']              = $poem->poet_is_v;

        $res['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);

        $translatorLabels   = [];
        $res['translators'] = $poem->translators->map(function ($translator) use (&$translatorLabels) {
            if ($translator instanceof Author) {
                $translatorLabels[] = $translator->label;

                return [
                    'name'        => $translator->label,
                    'id'          => $translator->id,
                    'avatar'      => $translator->avatar_url,
                    'avatar_true' => $translator->avatar_url !== config('app.avatar.default')
                ];
            } elseif ($translator instanceof Entry) {
                $translatorLabels[] = $translator->name;

                return ['name' => $translator->name];
            }
        });

        $res['translator_label'] = count($translatorLabels) ? join(', ', $translatorLabels) : $poem->translator_label;

        $res['date_ago'] = date_ago($poem->created_at);

        // TODO poet_id should be set after poem created (if is_owner_uploaded===Poem::$OWNER['uploader'])
        if (!$res['poet_id'] && $poem->is_owner_uploaded === Poem::$OWNER['uploader'] && $poem->uploader) {
            if ($poem->uploader->author) {
                $res['poet_id'] = $poem->uploader->author->id;
            }
        }

        if ($res['poet_id'] === 1033 or ($poem->is_owner_uploaded === 1 && $poem->upload_user_id === 4806)) {
            $res['sell'] = [
                'path'   => 'pages/proxy/union/union?spreadUrl=https://u.jd.com/6D7JluJ',
                'appId'  => 'wx1edf489cb248852c',
                'picUrl' => 'https://poemwiki-1254719278.cos.ap-guangzhou.myqcloud.com/campaign/9/sell.jpg'
            ];
        }

        $liked_review_ids = [];

        $res['score'] = $poem->totalScore;
        // TODO save score_count to poem.score_count column
        $res['score_count'] = ScoreRepository::calcCount($id);

        $q = $this->reviewRepository->listByOriginalPoem($poem);

        $reviewColumns = ['review.id', 'review.content', 'review.created_at', 'review.user_id', 'review.like', 'review.reply_id'];
        if ($user) {
            $q->leftJoin('likes', function ($join) use ($user) {
                $join->on('review.id', '=', 'likes.likeable_id')
                    ->where('likes.user_id', '=', $user->id)
                    ->where('likes.likeable_type', '=', \App\Models\Review::class);
            });
            $reviewColumns[] = 'likes.likeable_id';
        }

        $res['reviews'] = $q->get($reviewColumns)
            ->map(function ($review) use (&$liked_review_ids) {
                $review->makeHidden('user');

                if ($review->likeable_id) {
                    array_push($liked_review_ids, $review->likeable_id);
                }

                $review['date_ago'] = date_ago($review->created_at);
                $review['content'] = $review->pureContent;

                return $review->only(['id', 'content', 'created_at', 'user_id', 'like', 'reply_id', 'name', 'avatar', 'reply_to_user', 'date_ago']);
            });
        $res['liked_review_ids'] = $liked_review_ids;

        $relatedQuery = $this->poemRepository->suggest(2)
            ->where('id', '<>', $id);

        $res['is_campaign'] = $poem->is_campaign;
        if ($poem->is_campaign) {
            $res['campaign_reward'] = $poem->campaign->settings ? ($poem->campaign->settings['reward'] ?? 0) : null;
        }

        if ($poem->tags->count()) {
            $res['tags'] = $poem->tags->map->only(['id', 'name', 'category_id']);

            if ($poem->is_campaign) {
                $relatedQuery->whereHas('tags', function ($query) use ($poem) {
                    // TODO use campaign tag here
                    $query->where('tag_id', '=', $poem->tags[0]->id);
                });
            } else {
                $relatedQuery->where('score', '>=', 7);
            }
        }
        if ($poem->share_pics && isset($poem->share_pics['pure'])) {
            if (File::exists(storage_path($poem->share_pics['pure']))) {
                $res['share_image'] = route('poem-card', [
                    'id'            => $poem->id,
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

        $tag  = Tag::find($sanitized['tag_id']);
        if ($tag) {
            if ($tag->campaign && $tag->campaign->settings['gameType']) {
                $validator = Validator::make($sanitized, [
                    'poem' => [new ValidPoemContent(3)],
                ]);
                if ($validator->fails()) {
                    $error = join(' ', $validator->getMessageBag()->get('poem'));

                    return $this->responseFail([], $error, Controller::$CODE['poem_content_invalid']);
                }
            }
        }

        $wechatApp = Factory::miniProgram([
            'app_id'        => config('wechat.mini_program.default.app_id'),
            'secret'        => config('wechat.mini_program.default.secret'),
            'response_type' => 'object',
        ]);
        $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['poem']);
        if ($result->errcode) {
            return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
        }

        $poem = Poem::create($sanitized);
        if ($tag) {
            $poem->tags()->save($tag);
            if ($tag->is_campaign) {
                $poem->campaign_id = $tag->campaign->id;
                $poem->save();
            }
        }

        return $this->responseSuccess(['id' => $poem->id, 'fid' => $poem->fakeId]);
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
    public function poster(int $poemId, $force = null) {
    }

    // TODO poster image generation process should be a command, and invoke after poem created
    // TODO regenerate poster when related poem fields updated
    public function share(int $poemId) {
        $poem  = Poem::find($poemId);
        $force = isset($_GET['force']); // || config('app.env') === 'local';

        $compositionId = 'pure';
        $posterUrl     = route('poem-card', [
            'id'            => $poemId,
            'compositionId' => $compositionId
        ]);

        $postData = ['compositionId' => $compositionId, 'id' => $poem->id, 'poem' => $poem->poem, 'poet' => $poem->poetLabel, 'title' => $poem->title];
        $hash     = crc32(json_encode($postData));
        if (!$force && $poem->share_pics && isset($poem->share_pics[$compositionId])
            && file_exists(storage_path($poem->share_pics[$compositionId]))) {
            if (str_contains($poem->share_pics[$compositionId], $hash)) {
                return $this->responseSuccess(['url' => $posterUrl . '?t=' . time()]);
            }
            unlink(storage_path($poem->share_pics[$compositionId]));
            // TODO if posterUrl not contain $hash, remove old poemImg file
        }

        $relativeStoreDir = 'app/public/poem-card/' . $poem->id;
        $dir              = storage_path($relativeStoreDir);
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        // img file name will change if postData change
        $posterStorePath = "{$relativeStoreDir}/poster_{$compositionId}_{$hash}.png";
        $posterPath      = storage_path($posterStorePath); // posterImg = poemImg + appCodeImg
        $poemImgFileName = "poem_{$compositionId}_{$hash}.png"; // main part of poster

        $postData['force'] = $force;

        try {
            $poemImgPath = $this->fetchPoemImg($postData, $dir, $poemImgFileName, $force);

            $scene          = $poem->is_campaign ? ($poem->campaign_id . '-' . $poem->id) : $poem->id;
            $page           = $poem->is_campaign ? 'pages/campaign/campaign' : 'pages/poems/index';
            $appCodeImgPath = $this->fetchAppCodeImg($scene, $dir, $page, $force);

            if (!$this->composite($poemImgPath, $appCodeImgPath, $posterPath)) {
                return $this->responseFail();
            }

            $sharePics                 = $poem->share_pics ?? [];
            $sharePics[$compositionId] = $posterStorePath;
            Poem::withoutEvents(function () use ($poem, $sharePics) {
                $poem->timestamps = false;
                $poem->share_pics = $sharePics;
                $poem->save();
            });

            return $this->responseSuccess(['url' => $posterUrl . '?t=' . time()]);
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
    private function fetchPoemImg($postData, string $dir, string $poemImgFileName, bool $force = false) {
        $poemImgPath = $dir . '/' . $poemImgFileName;
        if (!$force && file_exists($poemImgPath)) {
            return $poemImgPath;
        }

        $poemImg = file_get_contents_post(config('app.render_server'), $postData, 'application/x-www-form-urlencoded', 30);
        if (file_put_contents($poemImgPath, $poemImg)) {
            if (File::mimeType($poemImgPath) == 'text/plain') {
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
     * @param bool   $force
     * @param string $appCodeFileName
     * @return string|false
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function fetchAppCodeImg(string $scene, string $appCodeImgDir, string $page = 'pages/detail/detail', bool $force = false, string $appCodeFileName = 'app-code.jpg') {
        $app = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret')
        ]);
        // 注意微信对此接口调用频率有限制
        $response = $app->app_code->getUnlimit($scene, [
            'page'       => $page,
            'width'      => 280,
            'is_hyaline' => false
        ]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $response->saveAs($appCodeImgDir, $appCodeFileName);

            return $appCodeImgDir . '/' . $appCodeFileName;
        }

        return false;
    }

    /**
     * composite poem image and appCode image.
     * @param string $poemImgPath
     * @param string $appCodeImgPath
     * @param string $posterPath
     * @param int    $quality
     * @return bool
     * @throws Exception
     */
    private function composite(string $poemImgPath, string $appCodeImgPath, string $posterPath, int $quality = 100): bool {
        // 绘制小程序码
        // 覆盖海报右下角小程序码区域
        $posterImg = img_overlay($poemImgPath, $appCodeImgPath, 0, 0, 120, 120);

        $imgType = exif_imagetype($poemImgPath);
        if ($imgType === IMAGETYPE_JPEG) {
            $res = imagejpeg($posterImg, $posterPath, $quality);
        } elseif ($imgType === IMAGETYPE_PNG) {
            //see: http://stackoverflow.com/a/7878801/1596547
            $res = imagepng($posterImg, $posterPath, $quality * 9 / 100);
        } elseif ($imgType === IMAGETYPE_GIF) {
            $res = imagegif($posterImg, $posterPath);
        } else {
            throw new Exception('image type not supported');
        }

        imagedestroy($posterImg);

        return $res;
    }

    public function query(Request $request) {
        $keyword = Str::trimSpaces($request->json('keyword', ''));

        if ($keyword === '' || is_null($keyword)) {
            return $this->responseSuccess([
                'authors' => [],
                'poems'   => [],
                'keyword' => $keyword
            ]);
        }

        $keyword4Query = Str::of($keyword)
            // ->replace('·', ' ')
            // ->replaceMatches('@[[:punct:]]+@u', ' ')
            ->replaceMatches('@\b[a-zA-Z]{1,2}\b@u', ' ')
            ->replaceMatches('@\s+@u', ' ')
            ->trim();
        // dd($keyword4Query);
        if ($keyword4Query->length < 1) {
            return $this->responseSuccess([
                'authors' => [],
                'poems'   => [],
                'keyword' => $keyword
            ]);
        }

        // DB::enableQueryLog();
        $searchResults = (new Search())
            ->registerAspect(AuthorAliasSearchAspect::class)
            ->registerModel(Poem::class, function (ModelSearchAspect $modelSearchAspect) {
                $modelSearchAspect
                    ->addSearchableAttribute('title') // return results for partial matches
                    ->addSearchableAttribute('poem')
                    ->addSearchableAttribute('poet')
                    ->addSearchableAttribute('poet_cn')
                    ->addSearchableAttribute('translator')
                    ->addSearchableAttribute('preface')
                    ->addSearchableAttribute('subtitle')
                    ->addSearchableAttribute('location')
                    ->with('poetAuthor')->limit(50);
                // ->addExactSearchableAttribute('upload_user_name') // only return results that exactly match the e-mail address
            })
            // ->registerModel(Poem::class, 'title', 'poem', 'poet', 'poet_cn', 'translator')//, 'poet')
            ->search($keyword4Query);

        // dd(DB::getQueryLog());
        $results   = $searchResults->groupByType();
        $authorRes = $results->get('authorAlias') ?: collect();

        $shiftPoems = collect();

        $authors = $authorRes->map(function (SearchResult $authorSearchRes, $index) use ($shiftPoems) {
            // TODO replace this ugly filter
            if ($index >= 5) {
                return null;
            }

            if ($authorSearchRes->searchable instanceof \App\Models\Author) {
                $author = $authorSearchRes->searchable;
                $author['#label'] = $author->label === $authorSearchRes->title ? $author->label : $author->label . ' ( ' . $authorSearchRes->title . ' )';
                foreach ($author->poems as $poem) {
                    $item = $poem;
                    $item['poet_contains_keyword'] = true;
                    // $item['#from_author'] = true;
                    $item['#poet_label'] = $poem->poet_label === $authorSearchRes->title
                        ? $authorSearchRes->title
                        : ($poem->poet_label . ' ( ' . $authorSearchRes->title . ' )');
                    $shiftPoems->push($item);
                }
            }

            // TODO show wikidata poet on search result page: $author->searchable instanceof \App\Models\Wikidata
            return $authorSearchRes->searchable instanceof \App\Models\Author ? $authorSearchRes->searchable : null;
        })->filter(function ($author) {
            return $author;
        });

        $poems = $results->get('poem') ?: [];

        foreach ($poems as $p) {
            $shiftPoems->push($p->searchable);
        }

        $keywordArr = $keyword4Query->split('#\s+#');

        // TODO append translated poems
        $mergedPoems = $shiftPoems->unique('id')->map(function ($poem) use ($keywordArr) {
            $columns = ['poet_label', '#poet_label', 'poet_is_v', 'translator', 'id', 'title', 'poet_contains_keyword', 'poem'];

            $item = $poem->only($columns);

            // TODO str_pos_one_of should support case insensitive mode
            $posOnPoem = str_pos_one_of($poem->poem, $keywordArr, 1);
            if ($posOnPoem) {
                $pos = $posOnPoem['pos'];
                // TODO don't do noSpace for english poem
                $item['poem'] = Str::of($poem->poem)->substr($pos - min(20, $pos), 40)->trimPunct()->replace("\n", ' ')->__toString();
                $item['poem_contains_keyword'] = true;
            } else {
                $item['poem'] = $poem->firstLine;
                $item['poem_contains_keyword'] = false;
            }

            $item['poet_is_v'] = $poem->poet_is_v;
            $item['poet_label'] = $poem->poet_label;
            $item['translator_label'] = $poem->translator_label;

            if ($poem->poet_label && str_pos_one_of($poem->poet_label, $keywordArr)) {
                $item['poet_contains_keyword'] = true;
            }
            if ($poem->translator_label && str_pos_one_of($poem->translator_label, $keywordArr)) {
                $item['translator_contains_keyword'] = true;
            }

            return $item;
        })->values();

        return $this->responseSuccess([
            'authors' => $authors->map->only(['id', 'avatar_url', 'label', '#label', 'describe_lang', 'user'])->map(function ($author) {
                if ($author['user']) {
                    $author['user'] = $author['user']->only(['id', 'avatar_url', 'name']);
                }

                $author['avatar_true'] = $author['avatar_url'] !== config('app.avatar.default');

                return $author;
            }),
            'poems'   => $mergedPoems,
            'keyword' => $keyword
        ]);
    }
}
