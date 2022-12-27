<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\Content;
use App\Models\NFT;
use App\Models\Poem;
use App\Models\Relatable;
use App\Models\Score;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PoemRepository.
 * @version July 17, 2020, 12:24 pm UTC
 */
class PoemRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'title',
        'language',
        'poet',
        'poet_cn',
        'bedtime_post_id',
        'bedtime_post_title',
        'poem',
        'length',
        'translator',
        'from',
        'year',
        'month',
        'date',
        'dynasty',
        'nation',
        'need_confirm',
        'is_lock'
    ];

    /**
     * Return searchable fields.
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model.
     **/
    public static function model() {
        return Poem::class;
    }

    /**
     * Paginate records for scaffold.
     *
     * @param int   $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listAll($perPage, $order, $direction, $columns = ['*']) {
        $query = $this->allQuery()->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }

    /**
     * @param array         $with
     * @param \Closure|null $callback
     * @param string[]      $select
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function random(array $with = [], \Closure $callback = null, array $select = ['*']): \Illuminate\Database\Eloquent\Builder {
        $builder = Poem::query()
            ->select($select)
            ->join(DB::raw('(SELECT CEIL( RAND() * ( SELECT MAX( id ) FROM `poem` )) AS id) AS rand'), 'poem.id', '>=', 'rand.id')
            ->orderBy('poem.id', 'ASC')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('relatable')
                    ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
            });

        if (is_callable($callback)) {
            call_user_func($callback, $builder);
        }
        if (!empty($with)) {
            $builder->with($with);
        }

        return $builder->take(1);
    }

    public function suggest($num = 1, $with = [], \Closure $callback = null, $select = ['*']) {
        // TODO 选取策略： 1. 优先选取 poem.bedtime_post_id 不为空的 poem
        // 2. 评分和评论数
        // 3. poem.length
        // 4. 最近未推送给当前用户的
        // $builder = Poem::query()->whereNotExists(function ($query) {
        //     $query->select(DB::raw(1))
        //         ->from('relatable')
        //         ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
        // })->where('language_id', '=', '1');
        // if (!empty($with)) {
        //     $builder->with($with);
        // }

        $builder = self::random($with, $callback, $select);
        for ($i = 1; $i < $num; ++$i) {
            $builder->union(self::random($with, $callback, $select));
        }

        return $builder; // TODO 1. 如果显示声明原创的诗歌，是否需要跟普通诗歌区分开？ 2. 对声明原创的诗歌，gate 中定义只允许上传用户编辑
    }

    /**
     * @param $name
     * @return array
     */
    public static function searchByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');

        return Poem::query()->select(['title', 'poet_id', 'translator_id'])
            ->whereRaw("lower(`title`) LIKE $value ")
            ->orWhereHas('poetAuthor', function ($q) use ($value) {
                $q->where(function ($q) use ($value) {
                    $q->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");
                });
            })
            ->orWhereHas('translatorAuthor', function ($q) use ($value) {
                $q->where(function ($q) use ($value) {
                    $q->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");
                });
            })->with('poetAuthor')->get()->toArray();
    }

    /**
     * @return mixed
     */
    public static function randomOne() {
        return self::random()->first();
    }

    public function getPoemFromFakeId($fakeId, $select = null) {
        $id = Poem::getIdFromFakeId($fakeId);

        if ($select) {
            return $this->newQuery()->select($select)->findOrFail($id);
        }

        return $this->newQuery()->findOrFail($id);
    }

    private function _processTagPoems($poems, $orderBy, $poemScores, $startTime, $endTime) {
        // if ($poems->get()->count()) {
        //     dump($poems->get());
        // }

        return $poems->with(['reviews', 'reviews.user', 'uploader'])->orderByDesc($orderBy)->get()->map(function (Poem $poem) use ($poemScores) {
            // dd($poem->created_at);
            $item = $poem->only(self::$listColumns);
            $item['date_ago'] = date_ago($poem->created_at);
            $item['poet'] = $poem->poetLabel;
            if (!(config('app.env') === 'production')) {
                $item['poet'] = $item['poet'] . '-' . $poem->id;
            }
            $item['poet_is_v'] = $poem->is_owner_uploaded === Poem::$OWNER['uploader'] && $poem->uploader && $poem->uploader->is_v;
            $item['reviews_count'] = $poem->reviews->count();
            $item['reviews'] = $poem->reviews->take(2)->map->only(self::$relatedReviewColumns);

            $score = isset($poemScores[$poem->id]) ? $poemScores[$poem->id] : ['score' => null, 'count' => 0, 'weight' => 0];
            $item['score'] = $score['score'];
            $item['score_weight'] = $score['weight'];
            $item['score_count'] = $score['count'];

            return $item;
        });
    }

    public function getByTagId($tagId, $orderBy, $startTime = null, $endTime = null) {
        // TODO Poem::where() tag_id=$tagId, with(['uploader', 'scores'])
        $poems = \App\Models\Tag::where('id', '=', $tagId)->with('poems')->first()->poems();
        if ($startTime) {
            $poems->where('poem.created_at', '>=', $startTime);
        }
        if ($endTime) {
            $poems->where('poem.created_at', '<=', $endTime);
        }

        $poemScores = $endTime
            ? ScoreRepository::batchCalc($poems->pluck('poem.id')->values()->all(), $startTime, $endTime)
            : ScoreRepository::batchCalc($poems->pluck('poem.id')->values()->all());

        return $this->_processTagPoems($poems, $orderBy, $poemScores, $startTime, $endTime);
    }

    /**
     * 选取最赞列表的诗歌.
     * @param $tagId
     * @param null $startTime
     * @param null $endTime
     * @param int  $scoreMin
     * @return mixed
     */
    public function getTopByTagId($tagId, $startTime = null, $endTime = null, $scoreMin = 6) {
        $poemIds = Poem::select('id')->whereHas('tags', function ($q) use ($tagId) {
            $q->where('tag.id', '=', $tagId);
        })->pluck('id');

        $poemIdsScored = Score::select(['user_id', 'poem_id'])
            ->whereIn('poem_id', $poemIds)
            // ->whereHas('user', function ($q) {
            //     $q->where('is_v', '=', 1);
            // })
            ->pluck('poem_id');

        $poems = Poem::whereIn('id', $poemIdsScored)->where('score', '>', $scoreMin);
        if ($startTime) {
            $poems->where('poem.created_at', '>=', $startTime);
        }
        if ($endTime) {
            $poems->where('poem.created_at', '<=', $endTime);
        }

        $poemScores = $endTime
            ? ScoreRepository::batchCalc($poems->pluck('poem.id')->values()->all(), $startTime, $endTime)
            : ScoreRepository::batchCalc($poems->pluck('poem.id')->values()->all());

        return $this->_processTagPoems($poems, 'score', $poemScores, $startTime, $endTime);
    }

    // TODO withReview in one method
    private function _withReviews($q) {
    }

    public function getTobeMint($userID) {
        return self::newQuery()->whereNull('upload_user_id')
            ->whereIn('is_owner_uploaded', [Poem::$OWNER['none']])
            ->whereIn('genre_id', [1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12])
            ->with('reviews')->orderByDesc('created_at')
            ->get()->map(function (Poem $item) use ($userID) {
                $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
                $item['poet'] = $item->poetLabel;
                $item['score_count'] = ScoreRepository::calcCount($item->id);
                $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
                $item['listable'] = 1;
                $item['unlistable'] = $item->nft && $item->nft->isUnlistableByUser($userID);
                $item['nft_id'] = $item->nft ? $item->nft->id : null;

                return $item->only(self::$listColumns);
            });
    }

    public function getByOwner($userID) {
        return self::newQuery()->where('upload_user_id', $userID)
            ->whereIn('is_owner_uploaded', [Poem::$OWNER['uploader'], Poem::$OWNER['translatorUploader']])
            ->with('reviews')->orderByDesc('created_at')
            ->get()->map(function (Poem $item) use ($userID) {
                $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
                $item['poet'] = $item->poetLabel;
                $item['score_count'] = ScoreRepository::calcCount($item->id);
                $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
                $item['listable'] = (!$item->nft && NFT::isMintable($item, $userID))
                    || ($item->nft && $item->nft->isListableByUser($userID));
                $item['unlistable'] = $item->nft && $item->nft->isUnlistableByUser($userID);
                $item['nft_id'] = $item->nft ? $item->nft->id : null;

                return $item->only(self::$listColumns);
            });
    }

    public static $listColumns = [
        'id', 'created_at', 'date_ago', 'title', //'subtitle', 'preface', 'location',
        'poem', 'poet', 'poet_id', 'poet_avatar', 'poet_cn',
        'score', 'score_count', 'score_weight', 'rank',
        'reviews', 'reviews_count', 'poet_is_v', 'listable', 'unlistable', 'nft_id'
    ];
    public static $relatedReviewColumns = ['id', 'avatar', 'content', 'pure_content', 'created_at', 'name', 'user_id'];

    /**
     * @param $userId
     * @param bool $isCampaignPoem
     * @param bool $excludeSelf    exclude poem that upload_user_id=userId. ONLY for $isCampaignPoem == true
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getRelated($userId, $limit = 100, $isCampaignPoem = false, $excludeSelf = true) {
        $q = self::newQuery();

        if ($isCampaignPoem) {
            $tagIds = Campaign::select('tag_id')->pluck('tag_id');

            $q->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tag.id', $tagIds);
            });

            if ($excludeSelf) {
                $q->where([
                    // ['is_owner_uploaded', '=', '1'],
                    ['upload_user_id', '<>', $userId],
                ]);
            }
        }

        $q->where(function ($q) use ($userId) {
            $q->whereHas('reviews', function ($q) use ($userId) {
                $q->where(['user_id' => $userId]);
            })->orWhereHas('scores', function ($q) use ($userId) {
                $q->where(['user_id' => $userId]);
            });
        });

        // TOOD pagination
        return $q->limit($limit)->with('reviews')->orderByDesc('created_at')->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poet_label;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);

            return $item->only(self::$listColumns);
        });
    }

    /**
     * @param string $poem
     * @return Poem|Poem[]|false
     */
    public static function isDuplicated(string $poem) {
        // TODO poem soft deleted, but content not deleted???
        // TODO use simhash
        if (mb_strlen($poem) <= 10) {
            return false;
        }
        $contentHash = Str::contentHash($poem);
        $existed     = Content::where([
            'hash_crc32' => Str::crc32($contentHash),
            'hash'       => $contentHash
        ])->whereHas('poem')->orderBy('created_at')->first();

        if ($existed) {
            return Poem::find($existed->entry_id);
        }

        return false;
    }

    public function getCampaignPoemsByTagId($tagId) {
        if (!is_numeric($tagId)) {
            return $this->responseFail();
        }

        $campaign = Tag::find($tagId)->campaign;
        if (!$campaign) {
            // campaign deleted
            return [];
        }

        $dateInterval  = Carbon::parse($campaign->end)->diff(now());
        $campaignEnded = $dateInterval->invert === 0;

        // poem before endDate, scores before endDate
        $byScore = $this->getTopByTagId($tagId, $campaign->start, $campaign->end);

        $limit = $campaign->settings['rank_min_weight'] ?? 3;

        $cacheKey     = 'api-topPoemsByScore-' . $campaign->id;
        $cachedResult = Cache::get($cacheKey);
        if ($campaignEnded && $cachedResult) {
            $byScoreData = $cachedResult;
        } else {
            $byScoreData = $byScore->filter(function ($value) use ($limit) {
                // 票数不足的不参与排名
                // dump($value['score_count']);
                return $value['score_count'] >= $limit;
            })->map(function ($item) {
                // TODO 独立以下后处理的过程，使得仅缓存 id, score 成为可能
                $item['reviews'] = $item['reviews']->map(function ($review) {
                    $review['content'] = $review['pure_content'];

                    return $review;
                });

                return $item;
            })->sort(function ($a, $b) {
                $scoreOrder = $b['score'] <=> $a['score'];
                $countOrder = $b['score_count'] <=> $a['score_count'];

                return $scoreOrder === 0
                    ? ($countOrder === 0 ? $b['score_weight'] <=> $a['score_weight'] : $countOrder)
                    : $scoreOrder;
            })->values()->map(function ($item, $index) {
                $item['rank'] = $index + 1;

                return $item;
            });

            if ($campaignEnded && !$cachedResult) {
                // TODO cache id, score, score_count, poet, title, content_id only
                // TODO 如果活动结束后，最赞列表中的诗歌被删除，则结果中也不应显示此诗
                Cache::forever($cacheKey, $byScoreData);
            }
        }

        return [
            // TODO paginate $byScoreData
            'byScore' => $byScoreData,
            // TODO pagination instead of ugly splice
            'byCreatedAt' => $this->getByTagId($tagId, 'created_at')->splice(0, 150)
        ];
    }

    public static function updateAllTranslatedPoemPoetId(Poem $originalPoem, int $poetId) {
        foreach ($originalPoem->translatedPoems as $p) {
            if ($p->is_translated) {
                $p->poet_id = $poetId;
                $p->save();

                self::updateAllTranslatedPoemPoetId($p, $poetId);
            }
        }
    }
}
