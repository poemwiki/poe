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
    public static function random(array $with = [], ?\Closure $callback = null, array $select = ['*']): \Illuminate\Database\Eloquent\Builder {
        $builder = Poem::query()
            ->select($select)
            ->join(DB::raw('(SELECT CEIL( RAND() * ( SELECT MAX( id ) FROM `poem` WHERE poem.deleted_at is NOT NULL)) AS rand_id) AS rand'), 'poem.id', '>=', 'rand.rand_id')
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

    public function suggest($num = 1, $with = [], ?\Closure $callback = null, $select = ['*']) {
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

    public function getByOwnerPaginate($userID, $page = 1, $pageSize = 20, $extraFieldsMap = null): array {
        $query = $this->_getByOwner($userID);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        $data = $paginator->map(function (Poem $item) use ($extraFieldsMap) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poetLabel;

            if ($extraFieldsMap) {
                foreach ($extraFieldsMap as $field => $func) {
                    $item[$field] = $func($item);
                }
            }

            return $item->only(array_merge(
                self::$listColumns,
                array_keys($extraFieldsMap ?? []
            )));
        });

        return [
            'data'           => $data,
            'total'          => $paginator->total(),
            'per_page'       => $paginator->perPage(),
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages()
        ];
    }

    private function _getByOwner($userID) {
        return self::newQuery()->where('upload_user_id', $userID)
            // TODO handle other $poem->is_owner_uploaded values, and fix api.poem.delete gate
            ->whereIn('is_owner_uploaded', [Poem::$OWNER['uploader'], Poem::$OWNER['translatorUploader']])
            ->with([
                'uploader:id,name,is_v,avatar',
                'poetAuthor.user:id,name,is_v,avatar'
            ])
            ->orderByDesc('created_at');
    }

    public function getByOwner($userID, $page = null, $pageSize = 20, $reviewsLimit = 2) {
        $query = $this->_getByOwner($userID);

        // Apply pagination if requested (still returning collection for backward compatibility)
        if (is_numeric($page)) {
            $query->paginate($pageSize, ['*'], 'page', $page); // side-effect to set internal page; will still call get()
        }

        // Constrained eager loads
        $query->with([
            'reviews' => function ($q) use ($reviewsLimit) {
                if ($reviewsLimit > 0) {
                    $q->orderByDesc('created_at');
                    if ($reviewsLimit !== -1) { // -1 means no limit (load all)
                        $q->limit($reviewsLimit);
                    }
                }
                $q->select(['id','poem_id','user_id','content','created_at'])
                  ->with('user:id,name,avatar');
            },
            'nft:id,poem_id' // minimal nft columns
        ]);

        $poems = $query->get();

        // Batch score counts
        $scoreCounts = Score::query()
            ->whereIn('poem_id', $poems->pluck('id'))
            ->selectRaw('poem_id, COUNT(user_id) as c')
            ->groupBy('poem_id')
            ->pluck('c', 'poem_id');

        return $poems->map(function (Poem $item) use ($userID, $scoreCounts) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poetLabel;
            $item['score_count'] = (int) ($scoreCounts[$item->id] ?? 0);
            $item['reviews'] = $item->reviews->map->only(self::$relatedReviewColumns);
            $item['listable'] = (!$item->nft && NFT::isMintable($item, $userID))
                || ($item->nft && $item->nft->isListableByUser($userID));
            $item['unlistable'] = $item->nft && $item->nft->isUnlistableByUser($userID);
            $item['nft_id'] = $item->nft ? $item->nft->id : null;

            return $item->only(self::$listColumns);
        });
    }

    public static $listColumns = [
        'fake_id', 'id', 'created_at', 'date_ago', 'title', //'subtitle', 'preface', 'location',
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
    public function getRelated($userId, $limit = 60, $isCampaignPoem = false, $excludeSelf = true) {
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

        // TODO pagination
        $poems = $q->limit($limit)
            ->with([
                'uploader:id,name,is_v,avatar',
                'reviews' => function ($q) {
                $q->orderByDesc('created_at')
                    ->select(['id', 'poem_id', 'user_id', 'content', 'created_at'])
                    ->with('user:id,name,avatar');
                }
            ])
            ->orderByDesc('created_at')
            ->get();
        // Batch score counts to avoid N+1 queries
        $scoreCounts = Score::query()
            ->whereIn('poem_id', $poems->pluck('id'))
            ->selectRaw('poem_id, COUNT(user_id) as c')
            ->groupBy('poem_id')
            ->pluck('c', 'poem_id');

        return $poems->map(function ($item) use ($scoreCounts) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poet_label;
            $item['score_count'] = (int)($scoreCounts[$item->id] ?? 0);
            $item['reviews'] = $item->reviews->map->only(self::$relatedReviewColumns);
            // poet_is_v derivation possibly involves uploader relation; ensure consistency
            if (!isset($item['poet_is_v'])) {
                $item['poet_is_v'] = ($item->is_owner_uploaded === Poem::$OWNER['uploader']) && $item->uploader && $item->uploader->is_v;
            }

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
            throw new \InvalidArgumentException('Invalid tag ID');
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

    /**
     * Preload translator data for poems to avoid N+1 queries
     */
    public static function preloadTranslatorsForPoems($poems) {
        // Filter out poems already having cached translators to avoid redundant queries
        $poems = $poems->filter(function ($p) {
            return !$p->relationLoaded('cached_translators');
        });
        if ($poems->isEmpty()) {
            return; // nothing new to preload
        }

        $poemIds = $poems->pluck('id');

        // Get translator data from translator_id field (direct relationship)
        $translatorIds = $poems->whereNotNull('translator_id')->pluck('translator_id')->unique();
        $directTranslatorsData = collect();

        if ($translatorIds->isNotEmpty()) {
            $directTranslatorsData = DB::table('author')
                ->whereIn('id', $translatorIds)
                ->select('id', 'name_lang')
                ->get()
                ->keyBy('id');
        }

        // Get translator data from relatable table (many-to-many relationship)
        $relatableTranslatorsData = DB::table('relatable')
            ->leftJoin('author', function($join) {
                $join->on('relatable.end_id', '=', 'author.id')
                     ->where('relatable.end_type', '=', \App\Models\Author::class);
            })
            ->leftJoin('entry', function($join) {
                $join->on('relatable.end_id', '=', 'entry.id')
                     ->where('relatable.end_type', '=', \App\Models\Entry::class);
            })
            ->whereIn('relatable.start_id', $poemIds)
            ->where('relatable.relation', '=', Relatable::RELATION['translator_is'])
            ->where('relatable.start_type', '=', Poem::class)
            ->select('relatable.start_id as poem_id', 'relatable.end_type', 'relatable.end_id',
                    'author.name_lang as author_name', 'entry.name as entry_name')
            ->get()
            ->groupBy('poem_id');

        // Cache translator data for each poem
        foreach ($poems as $poem) {
            $translators = collect();

            // First try translator_id (direct relationship)
            if ($poem->translator_id && isset($directTranslatorsData[$poem->translator_id])) {
                $authorData = $directTranslatorsData[$poem->translator_id];
                $authorName = json_decode($authorData->name_lang, true);
                $name = is_array($authorName)
                    ? pick_translation_value($authorName, 'zh-CN')
                    : $authorData->name_lang;

                $translators->push([
                    'id' => $poem->translator_id,
                    'name' => $name
                ]);
            }

            // Then add relatable translators (many-to-many relationship)
            if (isset($relatableTranslatorsData[$poem->id])) {
                $relatableTranslators = $relatableTranslatorsData[$poem->id]->map(function($item) {
                    if ($item->end_type === \App\Models\Author::class) {
                        $authorName = json_decode($item->author_name, true);
                        $name = is_array($authorName)
                            ? pick_translation_value($authorName, 'zh-CN')
                            : $item->author_name;
                    } else {
                        $name = $item->entry_name;
                    }
                    return [
                        'id' => $item->end_id,
                        'name' => $name
                    ];
                });

                $translators = $translators->concat($relatableTranslators)->unique('id')->unique('name');
            }

            // Always set cached_translators relation to indicate caching was attempted
            $poem->setRelation('cached_translators', $translators);

            if ($translators->isNotEmpty()) {
                // Cache the translators string for direct access
                $translatorsStr = $translators->pluck('name')->implode(', ');
                $poem->setRelation('cached_translators_str', $translatorsStr);
            } else {
                // If no translators found in relations but poem has translator field, cache it
                if (!empty($poem->translator)) {
                    $poem->setRelation('cached_translators_str', $poem->translator);
                } else {
                    // Set empty string to indicate no translators
                    $poem->setRelation('cached_translators_str', '');
                }
            }
        }
    }

    /**
     * Get poet labels for poems to avoid N+1 queries
     */
    public static function getPoetLabelsForPoems($poems) {
        if ($poems->isEmpty()) {
            return;
        }

        $poetLabelMap = collect();
        $poemIds = $poems->pluck('id');

        // Get poet data from poet_id field
        $poetIds = $poems->whereNotNull('poet_id')->pluck('poet_id')->unique();
        $poetsData = collect();

        if ($poetIds->isNotEmpty()) {
            $poetsData = DB::table('author')
                ->whereIn('id', $poetIds)
                ->select('id', 'name_lang')
                ->get()
                ->keyBy('id');
        }

        // Get poet data from relatable table
        $relatablePoetsData = DB::table('relatable')
            ->leftJoin('author', function($join) {
                $join->on('relatable.end_id', '=', 'author.id')
                     ->where('relatable.end_type', '=', \App\Models\Author::class);
            })
            ->leftJoin('entry', function($join) {
                $join->on('relatable.end_id', '=', 'entry.id')
                     ->where('relatable.end_type', '=', \App\Models\Entry::class);
            })
            ->whereIn('relatable.start_id', $poemIds)
            ->where('relatable.relation', '=', Relatable::RELATION['poet_is'])
            ->where('relatable.start_type', '=', Poem::class)
            ->select('relatable.start_id as poem_id', 'relatable.end_type', 'relatable.end_id',
                    'author.name_lang as author_name', 'entry.name as entry_name')
            ->get()
            ->groupBy('poem_id');

        // Cache poet data for each poem
        foreach ($poems as $poem) {
            $cachedPoet = null;

            // First try poet_id
            if ($poem->poet_id && isset($poetsData[$poem->poet_id])) {
                $authorData = $poetsData[$poem->poet_id];
                $authorName = json_decode($authorData->name_lang, true);
                $name = is_array($authorName)
                    ? pick_translation_value($authorName, 'zh-CN')
                    : $authorData->name_lang;

                $cachedPoet = [
                    'author_id' => $poem->poet_id,
                    'name' => $name
                ];
            }
            // Then try relatable poets
            elseif (isset($relatablePoetsData[$poem->id])) {
                $poetItem = $relatablePoetsData[$poem->id]->first();
                if ($poetItem->end_type === \App\Models\Author::class) {
                    $authorName = json_decode($poetItem->author_name, true);
                    $name = is_array($authorName)
                        ? pick_translation_value($authorName, 'zh-CN')
                        : $poetItem->author_name;
                } else {
                    $name = $poetItem->entry_name;
                }

                $cachedPoet = [
                    'author_id' => $poetItem->end_id,
                    'name' => $name
                ];
            } else {
                $cachedPoet = [
                    'author_id' => null,
                    'name' => $poem->poet
                ];
            }

            if ($cachedPoet) {
                $poetLabelMap[$poem->id] = $cachedPoet;
            }
        }

        return $poetLabelMap;
    }

    /**
     * Sort author poems with unified logic (only sorting, no content modification)
     *
     * @param \Illuminate\Support\Collection $poems Collection of poems
     * @param string $sortType Either 'hottest' (default) or 'newest'
     * @return \Illuminate\Support\Collection
     */
    public static function sortAuthorPoems(\Illuminate\Support\Collection $poems, string $sortType = 'hottest'): \Illuminate\Support\Collection {
        if ($sortType === 'newest') {
            // use poem writing time year / month / date, if 缺失部分按当年/当月的最后时间补齐，以便排序时更靠后（更新）
            // 若缺少 year，则回退使用 created_at
            return $poems->sortByDesc(function ($p) {
                // 如果有 year，构造一个尽量“晚”的日期以体现“最新”排序
                if (!empty($p->year)) {
                    $year  = (int)$p->year;
                    // 若 month 为空，用 12 代表当年末
                    $month = !empty($p->month) ? (int)$p->month : 12;
                    // 若 date 为空，用该月最后一天
                    $day   = !empty($p->date) ? (int)$p->date : 1; // 先用 1 再 endOfMonth() 统一
                    try {
                        $dt = \Illuminate\Support\Carbon::create($year, $month, $day, 23, 59, 59);
                        // 如果原本未给出具体日，调整到月底；若未给出月，前面已设为 12
                        if (empty($p->date)) {
                            $dt = $dt->endOfMonth();
                        }
                        return $dt->timestamp;
                    } catch (\Exception $e) {
                        // 回退 created_at
                        return $p->created_at ? $p->created_at->timestamp : 0;
                    }
                }
                return $p->created_at ? $p->created_at->timestamp : 0;
            })->values();
        } else {
            // Default hottest sorting - need scores for comparison
            $poemScores = ScoreRepository::batchCalc($poems->pluck('id')->values()->all());

            return $poems->sort(function ($a, $b) use ($poemScores) {
                $scoreA = isset($poemScores[$a->id]) ? $poemScores[$a->id] : Score::$DEFAULT_SCORE_ARR;
                $scoreB = isset($poemScores[$b->id]) ? $poemScores[$b->id] : Score::$DEFAULT_SCORE_ARR;

                // If both have null scores, sort by created_at desc (newer first)
                if ($scoreA['score'] === null && $scoreB['score'] === null) {
                    return $b->created_at->timestamp <=> $a->created_at->timestamp;
                }

                $scoreOrder = $scoreB['score'] <=> $scoreA['score'];
                $countOrder = $scoreB['count'] <=> $scoreA['count'];

                return $scoreOrder === 0
                    ? ($countOrder === 0 ? $scoreB['weight'] <=> $scoreA['weight'] : $countOrder)
                    : $scoreOrder;
            })->values();
        }
    }

    /**
     * Prepare author poems for API output with sorting
     *
     * @param \Illuminate\Support\Collection $poems Collection of poems
     * @param string $sortType Either 'hottest' (default) or 'newest'
     * @param array $options Options for processing ['noAvatar' => false, 'noPoet' => false]
     * @return \Illuminate\Support\Collection
     */
    public static function prepareAuthorPoemsForAPI(\Illuminate\Support\Collection $poems, string $sortType = 'hottest', array $options = []): \Illuminate\Support\Collection {
        $defaultOptions = ['noAvatar' => false, 'noPoet' => false];
        $opt = array_merge($defaultOptions, $options);
        list('noAvatar' => $noAvatar, 'noPoet' => $noPoet) = $opt;

        $columns = [
            'id', 'created_at', 'date_ago', 'title',
            'poem', 'poet', 'poet_id',
            'score', 'score_count', 'score_weight'
        ];

        if (!$noAvatar) {
            $columns[] = 'poet_avatar';
        }

        // First sort the poems
        $sortedPoems = self::sortAuthorPoems($poems, $sortType);

        // Then prepare the content for API
        $poemScores = ScoreRepository::batchCalc($sortedPoems->pluck('id')->values()->all());

        return $sortedPoems->map(function (Poem $item) use ($noPoet, $poemScores) {
            $score = isset($poemScores[$item->id]) ? $poemScores[$item->id] : Score::$DEFAULT_SCORE_ARR;
            $item['score'] = $score['score'];
            $item['score_count'] = $score['count'];
            $item['score_weight'] = $score['weight'];
            $item['poem'] = $item->firstLine;

            if (!$noPoet) {
                $item['poet'] = $item->poetLabel;
            }

            return $item;
        })->map->only($columns)->values();
    }

    /**
     * Get hierarchical translated poems tree starting from top original poem
     * Returns nested structure with each poem containing its direct translations
     */
    public function getTranslatedPoemsTree($poem) {
        $cacheKey = "translated_poems_tree_{$poem->topOriginalPoem->id}";

        return Cache::remember($cacheKey, 360000, function() use ($poem) {
            $topOriginal = $poem->topOriginalPoem;

            // Get ALL poems in the translation tree recursively
            $allPoemsInTree = $this->collectAllPoemsInTranslationTree($topOriginal);

            // Preload translator data for all poems
            static::preloadTranslatorsForPoems($allPoemsInTree);
            $poetLabelMap = self::getPoetLabelsForPoems($allPoemsInTree);

            // Build hierarchical structure
            return $this->buildTranslationTree($topOriginal, $allPoemsInTree, $poetLabelMap);
        });
    }

    /**
     * Clear translated poems tree cache for a poem and all poems in its translation tree
     */
    public static function clearTranslatedPoemsTreeCache($poem) {
        $topOriginal = $poem->topOriginalPoem ?? $poem;
        $cacheKey = "translated_poems_tree_{$topOriginal->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Optimized method to collect all poems in a translation tree with minimal queries
     */
    private function collectAllPoemsInTranslationTree($topOriginal, $maxDepth = 4) {
        // Use recursive CTE or find all poems that belong to this translation tree
        // We'll use a breadth-first approach with batched queries

        $allPoems = collect([$topOriginal]);
        $currentLevelIds = collect([$topOriginal->id]);
        $processedIds = collect([$topOriginal->id]);
        $currentDepth = 1;

        while ($currentLevelIds->isNotEmpty() && $currentDepth <= $maxDepth) {
            // Get all direct translations for all poems in current level in one query
            $nextLevelPoems = Poem::whereIn('original_id', $currentLevelIds->toArray())
                ->whereRaw('original_id <> id')
                ->whereNotIn('id', $processedIds->toArray())
                ->get();

            if ($nextLevelPoems->isEmpty()) {
                break;
            }

            // Add to collections
            $allPoems = $allPoems->concat($nextLevelPoems);
            $currentLevelIds = $nextLevelPoems->pluck('id');
            $processedIds = $processedIds->concat($currentLevelIds);
            $currentDepth++;
        }

        return $allPoems;
    }

    /**
     * Recursively build translation tree structure
     */
    private function buildTranslationTree($poem, $allPoems, $poetLabelMap) {
        // Get direct translations of this poem
        $directTranslations = $allPoems->filter(function($p) use ($poem) {
            return $p->original_id == $poem->id && $p->id !== $poem->id;
        })->sortBy('language_id');

        // Build the poem structure
        $poemData = [
            'id' => $poem->id,
            'fakeId' => $poem->fakeId,
            'originalId' => $poem->original_id,
            'languageId' => $poem->language_id,
            'language' => $poem->lang->name_lang ?? '',
            'translatorStr' => $poem->translatorsStr ?? '',
            'title' => $poem->title,
            'url' => $poem->url,
            'isOriginal' => $poem->is_original,
            'poetLabel' => $poem->is_original ? $poetLabelMap[$poem->id]['name'] : '',
            'translatedPoems' => []
        ];

        // Recursively add translated poems
        foreach ($directTranslations as $translation) {
            $poemData['translatedPoems'][] = $this->buildTranslationTree($translation, $allPoems, $poetLabelMap);
        }

        return $poemData;
    }
}
