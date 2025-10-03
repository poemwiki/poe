<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StoreOwnerUploaderPoem;
use App\Http\Requests\API\StorePoem;
use App\Models\Author;
use App\Models\Balance;
use App\Models\Language;
use App\Models\NFT;
use App\Models\Poem;
use App\Models\Relatable;
use App\Models\Tag;
use App\Models\Transaction;
use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use App\Rules\NoDuplicatedPoem;
use App\Rules\ValidPoemContent;
use App\Services\AliTranslate;
use App\Services\PosterGenerator;
use App\Services\Weapp;
use EasyWeChat\Factory;
use Error;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PoemAPIController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;
    /** @var ReviewRepository */
    private $reviewRepository;
    /** @var ScoreRepository */
    private $scoreRepository;
    /** @var AuthorRepository */
    private $authorRepository;

    public function __construct(PoemRepository $poemRepository, ReviewRepository $reviewRepository, ScoreRepository $scoreRepository, AuthorRepository $authorRepository) {
        $this->poemRepository   = $poemRepository;
        $this->reviewRepository = $reviewRepository;
        $this->scoreRepository  = $scoreRepository;
        $this->authorRepository = $authorRepository;
    }

    /**
     * Get user identifier for personalized recommendations
     * Handles logged-in users, web anonymous users, and WeChat mini-program users
     */
    private function getUserIdentifier() {
        // For logged-in users: use user ID
        if (request()->user()) {
            return 'user:' . request()->user()->id;
        }

        // For WeChat mini-program users: use client ID from header
        if (\App\User::isWeApp() || \App\User::isWeAppWebview()) {
            $clientId = request()->header('POEMWIKI-Client-ID') ?: request()->header('Client-ID');
            if ($clientId) {
                return 'weapp:' . $clientId;
            }

            // Fallback: use Cloudflare's real IP for mini-program users without client ID
            $cfConnectingIp = request()->header('CF-Connecting-IP');
            if ($cfConnectingIp) {
                return 'weapp:cf_' . md5($cfConnectingIp);
            }

            // Final fallback: don't cache to avoid excessive cache usage
            return null;
        }

        // For web anonymous users: prefer CF-Connecting-IP, fallback to session ID
        $cfConnectingIp = request()->header('CF-Connecting-IP');
        if ($cfConnectingIp) {
            return 'web:cf_' . md5($cfConnectingIp);
        }

        // Fallback to session ID for non-Cloudflare environments
        return 'session:' . request()->session()->getId();
    }

    public function random($num = 8, $id = null) {
        $num = max(5, min(10, $num));

        // Get current user identifier for personalized recommendations
        $userIdentifier = $this->getUserIdentifier();
        $columns        = [
            'id', 'title', 'subtitle', 'preface', 'poem', 'poet', 'poet_cn', 'poet_id',
            'dynasty_id', 'nation_id', 'language_id', 'is_original', 'original_id', 'created_at',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded',
            'reviews', 'reviews_count', 'date_ago', 'poet_avatar', 'translator_avatar',
            'score', 'score_count', 'score_weight'
        ];

        $poems = collect([]);
        if ($id) {
            $poemById = Poem::find($id);

            if ($poemById) {
                $poems = $poems->concat([$poemById->mergedToPoem ? $poemById->mergedToPoem : $poemById]);
            }
        }

        $select = [
            'poem.id', 'title', 'poem', 'poet', 'poet_cn', 'poet_id', 'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded'
        ];

        $noScoreNum   = 2;
        $earlyPoemNum = rand(0, 1) <= 0.2 ? 1 : 0; // chance to include one early poem
        $scorePoems   = $this->poemRepository->suggest($num - $noScoreNum - $earlyPoemNum, ['reviews'], function ($query) {
                $query->whereNull('campaign_id')
                    ->where('score', '>=', 7)
                    ->where('language_id', Language::LANGUAGE_ID['zh-CN']);
            }, $select, null, $userIdentifier)
            ->get();

        $noScorePoems = $this->poemRepository->suggest($noScoreNum, ['reviews'], function ($query) {
                $query->whereNull('campaign_id')
                    ->whereNull('score')
                    ->where('language_id', Language::LANGUAGE_ID['zh-CN']);
            }, $select, null, $userIdentifier)
            ->get();

        $earlyPoems = $earlyPoemNum ?
            $this->poemRepository->suggest($earlyPoemNum, ['reviews'], function ($query) {
                    $query->where('language_id', Language::LANGUAGE_ID['zh-CN']);
                }, $select, 3100, $userIdentifier)
                ->get()
            : collect();

        // Use merge() to maintain Eloquent Collection type and avoid memory copy
        $poems = $scorePoems->merge($noScorePoems)->merge($earlyPoems)->unique('id');

        // Cache suggested poem IDs to user's history for future exclusion
        if ($userIdentifier && $poems->isNotEmpty()) {
            PoemRepository::addUserSuggestedIds($userIdentifier, $poems->pluck('id')->toArray());
        }

        // Optimize relationships and translators to prevent N+1 queries
        if ($poems->isNotEmpty()) {
            $poems = $this->loadPoemRelationships($poems);
        }

        // Batch calculate scores to prevent N+1 queries
        $poemIds = $poems->pluck('id');
        $scores  = $poemIds->isNotEmpty() ? \App\Repositories\ScoreRepository::batchCalc($poemIds->toArray()) : [];

        $res = [];
        foreach ($poems as $poem) {
            $item = $poem->only($columns);

            $score                          = $scores[$poem->id] ?? ['score' => null, 'count' => 0];
            $item['score']                  = $score['score'];
            $item['score_count']            = $score['count'];
            $item['date_ago']               = date_ago($poem->created_at);
            $item['poet']                   = $poem->poet_label;
            $item['poet_cn']                = $poem->poet_label_cn;
            $item['poet_avatar_true']       = $poem->poet_avatar       !== config('app.avatar.default');
            $item['translator_avatar_true'] = $poem->translator_avatar !== config('app.avatar.default');
            $item['poet_is_v']              = $poem->poet_is_v;

            $item['translator_is_v']  = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);
            $item['translators']      = $poem->translatorsApiArr;
            $item['translator_label'] = $poem->translatorsStr;

            $res[] = $item;
        }

        return $this->responseSuccess($res);
    }

    public function randomNft($num = 5, $id = null) {
        $num = min(10, $num);

        $columns = [
            'id', 'created_at', 'title', 'poem', 'poet', 'language_id', 'is_original',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded'
        ];

        $ids = NFT::query()->with(['poem:id,title', 'content:id,content'])->get(['nft.id', 'nft.poem_id', 'nft.content_id'])->map(function ($item) {
            return $item->poem->id;
        })->toArray();

        $poems = Poem::whereIn('id', $ids)->get();

        $res = [];
        foreach ($poems as $poem) {
            $item = $poem->only($columns);

            $score                    = $poem->scoreArray;
            $item['score']            = $score['score'];
            $item['score_count']      = $score['count'];
            $item['date_ago']         = date_ago($poem->created_at);
            $item['poet']             = $poem->poet_label;
            $item['poet_cn']          = $poem->poet_label_cn;
            $item['poet_avatar_true'] = $poem->poet_avatar       !== config('app.avatar.default');
            $item['poet_is_v']        = $poem->poet_is_v;
            $item['nft_id']           = $poem->nft->id;
            $item['price']            = number_format($poem->nft->listing->price, 2, '.', '');

            $item['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);
            $item['translators']     = $poem->translatorsApiArr;

            $item['translator_label'] = $poem->translatorsStr;

            $res[] = $item;
        }

        return $this->responseSuccess($res);
    }

    public function userPoems($userID, $page = 1, $pageSize = 20) {
        return $this->responseSuccess($this->poemRepository->getByOwnerPaginate($userID, $page, $pageSize, [
            'firstLine' => function (Poem $poem) {
                return $poem->firstLine;
            }
        ]));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function mine(Request $request) {
        $userId = $request->user()->id;

        // TODO delete nft query
        // $nft = Balance::query()->with('nft:id,poem_id,content_id')->where('user_id', 1)->get();
        $nfts = NFT::query()->with(['poem:id,title', 'content:id,content'])->join('balance', function ($join) use ($userId) {
            $join->on('nft.id', '=', 'balance.nft_id')
                ->where('balance.user_id', '=', $userId);
            // ->where('balance.user_id', '=', 1);
        })->get(['nft.id', 'nft.poem_id', 'nft.content_id'])->map(function ($item) {
            $res          = $item->only('id');
            $res['title'] = $item->poem->title;
            $res['poem']  = $item->content->content;

            return $res;
        });

        if ($request->input('nft')) {
            return $this->responseSuccess([
                'nfts'    => $nfts,
                'author'  => $userId === 2 ? $this->poemRepository->getTobeMint($userId) : $this->poemRepository->getByOwner($userId)
            ]);
        }

        return $this->responseSuccess($this->poemRepository->getByOwner($userId));
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

    // TODO nft poem detail should pull from nft.poemwiki instead of poem table.
    public function nftDetail($id): array {
        $nft     = NFT::findOrFail($id);
        $balance = Balance::query()->where('amount', '>=', 1)->where('nft_id', $id)->first();

        $columns = [
            'id', 'created_at', 'title', 'subtitle', 'preface', 'poem',
            'poet', 'poet_cn', 'poet_id', 'poet_avatar', 'translator_avatar',
            'subtitle', 'preface', 'date', 'month', 'year', 'location',
            'dynasty_id', 'nation_id', 'language_id', 'is_original', 'original_id', 'created_at',
            'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded', 'share_pics',
            'campaign_id', 'first_line'
        ];
        /** @var Poem $poem */
        $poem = Poem::where('id', '=', $nft['poem_id'])->first();
        if (!$poem) {
            return $this->responseFail([], 'not found', Controller::$CODE['no_entry']);
        }
        $res = $poem->only($columns);

        $res['poet']                   = $poem->poet_label;
        $res['poet_avatar_true']       = $poem->poet_avatar       !== config('app.avatar.default');
        $res['translator_avatar_true'] = $poem->translator_avatar !== config('app.avatar.default');
        $res['poet_is_v']              = $poem->poet_is_v;

        $res['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);


            $res['translators'] = $poem->translatorsApiArr;


        $res['translator_label'] = $poem->translatorsStr;

        $res['date_ago'] = date_ago($poem->created_at);

        // TODO poet_id should be set after poem created (if is_owner_uploaded===Poem::$OWNER['uploader'])
        if (!$res['poet_id'] && $poem->is_owner_uploaded === Poem::$OWNER['uploader'] && $poem->uploader) {
            if ($poem->uploader->author) {
                $res['poet_id'] = $poem->uploader->author->id;
            }
        }

        // TODO use NFT pic
        if ($poem->share_pics && isset($poem->share_pics['pure'])) {
            if (File::exists(storage_path($poem->share_pics['pure']))) {
                $res['share_image'] = route('poem-card', [
                    'id'            => $poem->id,
                    'compositionId' => 'pure'
                ]);
            }
        }

        if ($nft->listing) {
            $res['price'] = number_format($nft->listing['price'], 2);
        }
        $res['nft_id']         = $nft->id;
        $res['mint_time']      = $nft->created_at->format('Y-m-d H:i:s');
        $res['hash']           = $nft->shortedHash;
        $res['nft_owner']      = $nft->owner->only(['id', 'name', 'avatar_url']);
        $res['listing_status'] = $nft->listing ? $nft->listing->status : null;

        $res['txs'] = $nft->txs->where('f_id', '=', 0)->map(function ($tx) {
            $res                   = $tx->only(['id', 'tx_hash', 'amount', 'from_user_id', 'to_user_id', 'action']);
            $res['date_ago']       = date_ago($tx->created_at);
            $res['from_user_name'] = $tx->fromUser ? $tx->fromUser->name : "[$tx->from_user_id]";
            $res['to_user_name']   = $tx->toUser ? $tx->toUser->name : "[$tx->to_user_id]";

            if ($tx->nft_id) {
                $res['amount'] = $tx->children ? $tx->children->where('nft_id', '=', 0)->sum('amount') : '';
            }

            if ($tx->action === Transaction::ACTION['listing']) {
                $res['price'] = number_format($tx->memo, 2, '.', '');
            } elseif ($tx->action === Transaction::ACTION['sell'] && $tx->nft_id) {
                $res['price'] = number_format($tx->childGoldPrice()->sum('amount'), 2, '.', '');
            }

            return $res;
        })->reverse()->values();

        return $this->responseSuccess($res);
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
        $res['poet_avatar_true']       = $poem->poet_avatar       !== config('app.avatar.default');
        $res['translator_avatar_true'] = $poem->translator_avatar !== config('app.avatar.default');
        $res['poet_is_v']              = $poem->poet_is_v;

        $res['translator_is_v'] = ($poem->translatorAuthor && $poem->translatorAuthor->user && $poem->translatorAuthor->user->is_v);

        $res['translators'] = $poem->translatorsApiArr;

        $res['translator_label'] = $poem->translatorsStr;

        $res['date_ago'] = date_ago($poem->created_at);

        // TODO poet_id should be set after poem created (if is_owner_uploaded===Poem::$OWNER['uploader'])
        if (!$res['poet_id'] && $poem->is_owner_uploaded === Poem::$OWNER['uploader'] && $poem->uploader) {
            if ($poem->uploader->author) {
                $res['poet_id'] = $poem->uploader->author->id;
            }
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
                $review['content']  = $review->pureContent;

                return $review->only(['id', 'content', 'created_at', 'user_id', 'like', 'reply_id', 'name', 'avatar', 'reply_to_user', 'date_ago']);
            });
        $res['liked_review_ids'] = $liked_review_ids;

        $res['is_campaign'] = $poem->is_campaign;
        if ($poem->campaign) {
            $res['campaign_reward'] = $poem->campaign->settings ? ($poem->campaign->settings['reward'] ?? 0) : null;
        }

        if ($poem->tags->count()) {
            $res['tags'] = $poem->tags->map->only(['id', 'name', 'category_id']);
        }
        if ($poem->share_pics && isset($poem->share_pics['pure'])) {
            if (File::exists(storage_path($poem->share_pics['pure']))) {
                $res['share_image'] = route('poem-card', [
                    'id'            => $poem->id,
                    'compositionId' => 'pure'
                ]);
            }
        }

        $res['related'] = $this->_relatedPoems($poem);

        $res['nft_unlistable'] = $poem->nft && $poem->nft->listing && $poem->nft->listing->isUnlistable;
        $res['nft_id']         = $poem->nft ? $poem->nft->id : null;
        $res['nft_price']      = ($poem->nft && $poem->nft->listing) ? $poem->nft->listing->price : null;

        return $this->responseSuccess($res);
    }

    /**
     * Get poem info by fakeId. Fields align with query() output shape
     * where applicable.
     *
     * @param string $fakeId
     * @return array
     */
    public function infoByFakeId($fakeId) {
        try {
            /** @var Poem $poem */
            $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        } catch (\Throwable $e) {
            return $this->responseFail([], 'not found', Controller::$CODE['no_entry']);
        }

        // Keep fields consistent with items returned by query(); include original poem if exists
        // Load author relations to provide their profile URLs when available
        $poem->loadMissing(['poetAuthor', 'translatorAuthor']);
        $poetAuthorUrl       = $poem->poetAuthor ? $poem->poetAuthor->url : null;
        $translatorAuthorUrl = $poem->translatorAuthor ? $poem->translatorAuthor->url : null;

        $translatorsArr = $poem->translatorsApiArr;
        $item           = [
            'fake_id'                => $poem->fake_id,
            'title'                  => $poem->title,
            'subtitle'               => $poem->subtitle,
            'preface'                => $poem->preface,
            'date_str'               => $poem->dateStr,
            'location'               => $poem->location,
            'url'                    => $poem->url,
            'poem'                   => $poem->poem,
            'poet_is_v'              => $poem->poet_is_v,
            'poet_label'             => $poem->poet_label,
            'poet_label_native'      => $poem->poet_label_native,
            'poet_avatar'            => $poem->poet_avatar,
            'poet_avatar_true'       => $poem->poet_avatar !== config('app.avatar.default'),
            'translators_str'        => $poem->translatorsStr,
            'translators'            => $translatorsArr,
            'poet_author_url'        => $poetAuthorUrl,
            'translator_author_url'  => $translatorAuthorUrl,
        ];

        // Attach original poem info when this is a translation with a valid original_id
        $original = null;
        if (!empty($poem->original_id) && $poem->is_translated) {
            $topOriginal = $poem->topOriginalPoem; // climb to root original if chained
            if ($topOriginal && $topOriginal->id !== $poem->id) {
                $topOriginal->loadMissing(['poetAuthor', 'translatorAuthor']);

                $original = [
                    'fake_id'                    => $topOriginal->fake_id,
                    'title'                      => $topOriginal->title,
                    'subtitle'                   => $poem->subtitle,
                    'preface'                    => $poem->preface,
                    'date_str'                   => $poem->dateStr,
                    'location'                   => $poem->location,
                    'url'                        => $topOriginal->url,
                    'poem'                       => $topOriginal->poem,
                    'poet_is_v'                  => $topOriginal->poet_is_v,
                    'poet_label'                 => $topOriginal->poet_label,
                    'poet_label_native'          => $topOriginal->poet_label_native,
                    'translator_str'             => $topOriginal->translatorsStr,
                    'poet_author_url'            => $topOriginal->poetAuthor ? $topOriginal->poetAuthor->url : null,
                    'translator_author_url'      => $topOriginal->translatorAuthor ? $topOriginal->translatorAuthor->url : null,
                ];
            }
        }
        $item['original_poem'] = $original;

        return $this->responseSuccess($item);
    }

    private function _relatedPoems($poem, $num = 2) {
        $select = [
            'poem.id', 'poem', 'poet', 'poet_cn', 'poet_id', 'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded'
        ];

        if ($poem->is_campaign) {
            $relatedQuery = Poem::query()->select($select)
                ->whereHas('tags', function ($query) use ($poem) {
                    // TODO use campaign tag here
                    $query->where('tag_id', '=', $poem->tags[0]->id);
                })->inRandomOrder()->take($num);
        } else {
            $relatedQuery = $this->poemRepository->suggest($num, [], function ($query) use ($poem) {
                $query->where('poem.id', '!=', $poem->id)->where('score', '>=', 7);
            }, $select);
        }

        return $relatedQuery->get()
            ->map(function ($item) {
                $arr         = $item->toArray();
                $arr['poet'] = $item->poet_label;

                return $arr;
            })
            ->toArray();
    }

    /**
     * Store poem. **Only for weapp user**.
     * @param StorePoem $request
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(StorePoem $request) {
        $sanitized = $request->getSanitized();
        /* @var User $user */
        $user = $request->user();

        $wechatApp = new Weapp();
        $content   = $sanitized['title'] . $sanitized['poem'];

        if (!$wechatApp->checkText($content)) {
            return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
        }

        $poem = Poem::create($sanitized);

        if ($sanitized['translator_ids']) {
            $poem->relateToTranslators($sanitized['translator_ids']);
        }

        return $this->responseSuccess(['id' => $poem->id, 'fid' => $poem->fakeId]);
    }

    /**
     * store campaign poem.
     * @param StoreOwnerUploaderPoem $request
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function store(StoreOwnerUploaderPoem $request) {
        $sanitized = $request->getSanitized();

        $tag = Tag::find($sanitized['tag_id']);
        if ($tag && $tag->campaign && isset($tag->campaign->settings['gameType'])) {
            $gameType      = $tag->campaign->settings['gameType'];
            $strictLineNum = $gameType === 1 ? ($tag->campaign->settings['strictLineNum'] ?? 3) : 0;
            $maxLineNum    = $tag->campaign->settings['maxLineNum'] ?? 3;
            $validator     = Validator::make($sanitized, [
                'poem' => [new ValidPoemContent($strictLineNum, true, $maxLineNum)],
            ]);
            if ($validator->fails()) {
                $error = join(' ', $validator->getMessageBag()->get('poem'));

                return $this->responseFail([], $error, Controller::$CODE['poem_content_invalid']);
            }
        }

        if (config('app.env') !== 'local') {
            $wechatApp = Factory::miniProgram([
                'app_id'        => config('wechat.mini_program.default.app_id'),
                'secret'        => config('wechat.mini_program.default.secret'),
                'response_type' => 'object',
            ]);
            $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['poem']);
            if ($result->errcode !== 0 && $result->errcode !== -1) {
                return $this->responseFail([], '请检查是否含有敏感词' . print_r($result, 1), Controller::$CODE['content_security_failed']);
            }
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
        $poem = Poem::find($poemId);
        if (empty($poem)) {
            return $this->responseFail([], 'Poem not found', Controller::$CODE['not_found']);
        }

        try {
            $this->authorize('api.poem.delete', $poem);
        } catch (AuthorizationException $e) {
            return $this->responseFail([], 'Not Allowed');
        }

        $poem->delete();

        return $this->responseSuccess();
    }

    // TODO for mini program, generate and get poster image file should be in ONE request
    public function poster(int $poemId, $force = null) {
    }

    // TODO poster image generation process should be a command, and invoke after poem created
    // TODO regenerate poster when related poem fields updated

    /**
     * @param int    $poemID
     * @param string $compositionID the template composition id
     * @return array
     */
    public function share(int $poemID, string $compositionID = 'pure'): array {
        $poem  = Poem::find($poemID);
        $force = isset($_GET['force']) || config('app.env') === 'local';

        $posterUrl = route('poem-card', [
            'id'            => $poemID,
            'compositionId' => $compositionID
        ]);

        $notZhLang = !in_array($poem->language_id, [Language::LANGUAGE_ID['zh-CN'], Language::LANGUAGE_ID['zh-hant']]);
        $poetName  = $poem->poet_label_native;

        $postData = [
            'compositionId' => $compositionID,
            'config'        => [
                'wrap'          => true,
                'noAuthorLabel' => $notZhLang,
            ],
            'id'            => $poem->id,
            'poem'          => $poem->poem,
            'poet'          => $poetName,
            'translators'   => $poem->translatorsStr,
            'title'         => $poem->title,
            'subtitle'      => $poem->subtitle,
            'preface'       => $poem->preface,
            'date'          => $poem->dateStr,
            'location'      => $poem->location
        ];
        if ($compositionID === 'nft') {
            $postData['hash']      = $poem->nft->shortedHash;
            $postData['time']      = $poem->nft->created_at->format('Y-m-d H:i:s');
            $postData['collector'] = $poem->nft->owner->name;
            $postData['pfp']       = $poem->nft->owner->avatarUrl;
        }

        $hash = crc32(json_encode($postData));
        if (!$force && $poem->share_pics && isset($poem->share_pics[$compositionID])
                    && file_exists(storage_path($poem->share_pics[$compositionID]))) {
            if (str_contains($poem->share_pics[$compositionID], $hash)) {
                return $this->responseSuccess(['url' => $posterUrl . '?t=' . time()]);
            }
            unlink(storage_path($poem->share_pics[$compositionID]));
            // TODO if posterUrl not contain $hash, remove old poemImg file
        }

        $relativeStoreDir = 'app/public/poem-card/' . $poem->id;
        $dir              = storage_path($relativeStoreDir);
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        // img file name will change if postData change
        // poster is stored as JPEG for better metadata support
        $posterStorePath = "{$relativeStoreDir}/poster_{$compositionID}_{$hash}.jpg";
        $posterPath      = storage_path($posterStorePath); // posterImg = poemImg + appCodeImg
        $poemImgFileName = "poem_{$compositionID}_{$hash}.png"; // main part of poster (render server returns PNG)

        $postData['force'] = $force;

        try {
            $posterGen = new PosterGenerator();
            $ok        = $posterGen->generatePosterFromData($postData, $dir, $poemImgFileName, $posterPath, $compositionID, $force, $poem);
            if (!$ok) {
                return $this->responseFail();
            }

            $sharePics                 = $poem->share_pics ?? [];
            $sharePics[$compositionID] = $posterStorePath;
            Poem::withoutEvents(function () use ($poem, $sharePics) {
                $poem->timestamps = false;
                $poem->share_pics = $sharePics;
                $poem->save();
            });

            return $this->responseSuccess(['url' => $posterUrl . '?t=' . time()]);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            // Clean up the created directory and files on error
            if (is_dir($dir)) {
                $this->removeDirectory($dir);
            }

            return $this->responseFail();
        }
    }

    public function query(Request $request) {
        $keyword = Str::trimSpaces($request->json('keyword', ''));
        $mode    = $request->json('mode', '');

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

        // Use Laravel Scout for search
        $shiftPoems = collect();

        // Authors
        $authors = collect();
        if ($mode !== 'poem-select') {
            $authorLimit = $mode !== 'author-select' ? 3 : 50;
            $authors     = \App\Models\Author::search($keyword4Query)
                ->query(function ($query) {
                    $query->with(['user', 'alias']);
                })
                ->take($authorLimit)
                ->get();

            // Sort authors: name/alias matches first, then description matches
            $keywordArr = $keyword4Query->split('#\s+#');
            $authors    = $authors->sortByDesc(function ($author) use ($keywordArr) {
                // Check if author's label (from name_lang) matches keyword
                if ($author->label && str_pos_one_of($author->label, $keywordArr)) {
                    return 100; // High priority for name match
                }

                // Check if any alias matches keyword
                if ($author->alias && $author->alias->isNotEmpty()) {
                    foreach ($author->alias as $alias) {
                        if ($alias->name && str_pos_one_of($alias->name, $keywordArr)) {
                            return 100; // High priority for alias match
                        }
                    }
                }

                // Description match gets lower priority
                return 1;
            })->values();
        }

        // Poems
        $poems = collect();
        if ($mode !== 'author-select' && $mode !== 'translator-select') {
            $poems = Poem::search($keyword4Query)
                ->query(function ($query) {
                    $query->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('relatable')
                            ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
                    });
                    $query->with(['poetAuthor.user', 'uploader']);
                })
                ->take(50)
                ->get();

            $shiftPoems = $poems->collect();
        }

        // Append poems from matched authors (limit to prevent timeout for prolific poets)
        if ($mode !== 'author-select' && $authors->isNotEmpty()) {
            $keywordArr     = $keyword4Query->split('#\s+#');
            $poemsPerAuthor = 30; // Limit poems per author to prevent timeout

            // Filter authors whose name_lang or alias actually contains the keyword
            // Note: alias is already loaded in the author search query above
            $matchedAuthorIds = $authors->filter(function ($author) use ($keywordArr) {
                // Check if author's label (from name_lang) matches keyword
                if ($author->label && str_pos_one_of($author->label, $keywordArr)) {
                    return true;
                }

                // Check if any alias matches keyword
                if ($author->alias && $author->alias->isNotEmpty()) {
                    foreach ($author->alias as $alias) {
                        if ($alias->name && str_pos_one_of($alias->name, $keywordArr)) {
                            return true;
                        }
                    }
                }

                return false;
            })->pluck('id')->toArray();

            // Only query poems if we have authors that actually match the keyword
            if (!empty($matchedAuthorIds)) {
                // Use efficient query instead of loading all poems for each author
                $authorPoems = Poem::query()
                    ->whereIn('poet_id', $matchedAuthorIds)
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('relatable')
                            ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
                    })
                    ->with(['poetAuthor.user', 'uploader'])
                    ->select(['id', 'title', 'poem', 'poet', 'poet_cn', 'poet_id', 'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded', 'language_id'])
                    ->get()
                    ->groupBy('poet_id')
                    ->map(function ($poems) use ($poemsPerAuthor) {
                        return $poems->take($poemsPerAuthor);
                    })
                    ->flatten();

                foreach ($authorPoems as $poem) {
                    $item                          = $poem;
                    $item['poet_contains_keyword'] = true;
                    $item['#poet_label']           = $poem->poet_label;
                    $shiftPoems->push($item);
                }
            }
        }

        $keywordArr = $keyword4Query->split('#\s+#');

        // TODO append translated poems
        $mergedPoems = $shiftPoems->unique('id')->map(function ($poem) use ($keywordArr) {
            $columns = ['poet_label', '#poet_label', 'poet_id', 'poet_is_v', 'translator', 'id', 'title', 'url', 'poet_contains_keyword', 'poem'];

            $item                          = $poem->only($columns);
            $item['poet_contains_keyword'] = $item['poet_contains_keyword'] ?? false;

            // TODO str_pos_one_of should support case insensitive mode
            $posOnPoem = str_pos_one_of($poem->poem, $keywordArr, 1);
            if ($posOnPoem) {
                $pos = $posOnPoem['pos'];
                // TODO don't do noSpace for english poem
                $item['poem']                  = Str::of($poem->poem)->substr($pos - min(20, $pos), 40)->trimPunct()->replace("\n", ' ')->__toString();
                $item['poem_contains_keyword'] = true;
            } else {
                $item['poem']                  = $poem->firstLine;
                $item['poem_contains_keyword'] = false;
            }

            $item['poet_is_v']        = $poem->poet_is_v;
            $item['poet_label']       = $poem->poet_label;
            $item['translator_label'] = $poem->translatorsStr;

            if ($poem->poet_label && str_pos_one_of($poem->poet_label, $keywordArr)) {
                $item['poet_contains_keyword'] = true;
            }
            if ($poem->translatorsStr && str_pos_one_of($poem->translatorsStr, $keywordArr)) {
                $item['translator_contains_keyword'] = true;
            }

            return $item;
        })->values();

        $data = [
            'authors' => $authors->map(function ($author) {
                $arr           = $author->only(['id', 'avatar_url', 'label', 'describe_lang', 'user']);
                $arr['#label'] = $author->label;
                if ($author->user) {
                    $arr['user'] = $author->user->only(['id', 'avatar_url', 'name']);
                }
                $arr['avatar_true'] = $author->avatar_url !== config('app.avatar.default');

                return $arr;
            }),
            'keyword' => $keyword
        ];

        $data['poems'] = $mergedPoems;

        return $this->responseSuccess($data);
    }

    public function import(Request $request): array {
        $poems = $request->input('poems');
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->responseFail([], 'Request content type must be application/json');
        }
        if (!is_array($poems)) {
            return $this->responseFail([], 'Poems must be an array.');
        } elseif (count($poems) > 200) {
            return $this->responseFail([], 'Limit 200 poems per request');
        }

        $result = [];
        foreach ($poems as $poem) {
            try {
                $poem['original_id']       = 0;
                $poem['is_owner_uploaded'] = Poem::$OWNER['none'];
                $poem['upload_user_id']    = $request->user()->id;
                $poem['flag']              = Poem::$FLAG['botContentNeedConfirm'];

                // Support poet_id in Q<wikidata_id> format: auto create / resolve author before validation
                if (isset($poem['poet_id']) && is_string($poem['poet_id']) && Str::startsWith($poem['poet_id'], 'Q')) {
                    $wikidataId = (int) ltrim($poem['poet_id'], 'Q');
                    if ($wikidataId > 0) {
                        try {
                            $author = $this->authorRepository->getExistedAuthor($wikidataId);
                            if ($author) {
                                $poem['poet_id'] = $author->id; // replace with internal id for validation & insertion
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to auto-create poet by wikidata in poem import: ' . $e->getMessage());
                        }
                    }
                }

                $validator = Validator::make($poem, [
                    'title'            => 'required|string|max:255',
                    'poet'             => 'required_without:poet_id|string|max:255',
                    'poem'             => ['required', new NoDuplicatedPoem(null), 'string', 'min:10', 'max:65500'],
                    'poet_id'          => ['nullable', 'integer', 'exists:' . \App\Models\Author::class . ',id'],
                    'from'             => 'nullable|string|max:255',
                    'language_id'      => ['required', Rule::in(LanguageRepository::idsInUse())],
                    'genre_id'         => ['nullable', 'integer', 'exists:' . \App\Models\Genre::class . ',id'],
                    'translator_ids'   => ['nullable', 'array'],
                    'translator_ids.*' => ['nullable'],
                ]);
                $validator->validate();

                $translatorIds = [];
                if (isset($poem['translator_ids']) && is_array($poem['translator_ids'])) {
                    foreach ($poem['translator_ids'] as $tid) {
                        // Accept formats:
                        // 1. numeric author id
                        // 2. Q<wikidata_id> (already existing author only)
                        // 3. any non-empty string -> raw translator name
                        if (is_string($tid) && Str::startsWith($tid, 'Q')) {
                            $wikidataId       = (int) ltrim($tid, 'Q');
                            $translatorAuthor = $this->authorRepository->getExistedAuthor($wikidataId);
                            if ($translatorAuthor) {
                                $translatorIds[] = $translatorAuthor->id;
                            }
                        } elseif (is_numeric($tid)) {
                            $translatorIds[] = (int) $tid;
                        } elseif (is_string($tid) && trim($tid) !== '') {
                            $translatorIds[] = trim($tid);
                        }
                    }
                    if (!empty($translatorIds)) {
                        $poem['translator'] = json_encode($translatorIds, JSON_UNESCAPED_UNICODE);
                    }
                }
                if (!empty($poem['translator'])) {
                    $poem['is_original'] = 0;
                }

                $inserted = Poem::create($poem);
                if (!empty($translatorIds)) {
                    $inserted->relateToTranslators($translatorIds);
                }
                $result[] = $inserted->url;
            } catch (ValidationException $e) {
                $errors   = $e->errors();
                $result[] = ['errors' => $errors];

                continue;
            } catch (Error $e) {
                logger()->error('Undefined error while import poem: ' . $e->getMessage() . $e->getTraceAsString());
                $result[] = ['errors' => 'Undefined error'];

                continue;
            }
        }

        return $this->responseSuccess($result, 'Thanks for your contribution!');
    }

    public function detectLanguage(Request $request) {
        $text = $request->input('text', '');

        try {
            $langId = AliTranslate::detectLanguage($text);

            return $this->responseSuccess(['language_id' => $langId]);
        } catch (Exception $e) {
            return $this->responseFail([], $e->getMessage());
        } catch (Error $e) {
            return $this->responseFail([], $e->getMessage());
        }
    }

    /**
     * Load poem relationships efficiently to prevent N+1 queries
     * No memory copy needed - works directly with Eloquent Collections
     *
     * @param \Illuminate\Database\Eloquent\Collection $poems
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function loadPoemRelationships($poems) {
        // Check if we have poems with valid (non-empty, non-null) IDs before loading relations
        $poetIds = $poems->filter(function($poem) {
            return !empty($poem->poet_id) && $poem->poet_id !== '';
        })->pluck('poet_id')->unique();

        $uploaderIds = $poems->filter(function($poem) {
            return !empty($poem->upload_user_id) && $poem->upload_user_id !== '';
        })->pluck('upload_user_id')->unique();

        $translatorIds = $poems->filter(function($poem) {
            return !empty($poem->translator_id) && $poem->translator_id !== '';
        })->pluck('translator_id')->unique();

        // Load relations conditionally - each relationship only if it has valid IDs
        if ($poetIds->isNotEmpty()) {
            $poems->loadMissing('poetAuthor.user');
        }
        if ($uploaderIds->isNotEmpty()) {
            $poems->loadMissing('uploader');
        }
        if ($translatorIds->isNotEmpty()) {
            $poems->loadMissing('translatorAuthor.user');
        }

        // Use existing repository methods to optimize translator N+1 queries
        \App\Repositories\PoemRepository::preloadTranslatorsForPoems($poems);

        return $poems;
    }

    /**
     * Recursively remove directory and all its contents
     *
     * @param string $dir Directory path to remove
     * @return bool
     */
    private function removeDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
