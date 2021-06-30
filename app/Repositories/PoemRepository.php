<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\Content;
use App\Models\Poem;
use App\Models\Review;
use App\Models\Score;
use App\Models\Tag;
use App\Repositories\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PoemRepository
 * @package App\Repositories
 * @version July 17, 2020, 12:24 pm UTC
*/

class PoemRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'title',
        'language',
        'is_original',
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
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Poem::class;
    }


    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listAll($perPage, $order, $direction, $columns = ['*']) {
        $query = $this->allQuery()->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }

    /**
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     * @TODO optimize sql by :
     * SELECT r1.id
    FROM poem AS r1
    JOIN
    (SELECT CEIL(RAND() *
    (SELECT MAX(id)
    FROM poem)) AS id)
    AS r2
    WHERE r1.id >= r2.id AND r1.deleted_at is NULL
    ORDER BY r1.id ASC
    LIMIT 1
     */
    public static function random($num = 1, $with=[]) {
        $builder = Poem::query();
        if(!empty($with)) $builder->with($with);

        return $builder
            ->inRandomOrder()
            ->take($num);
    }

    public function suggest($num = 1, $with=[]) {
        // TODO 选取策略： 1. 优先选取 poem.bedtime_post_id 不为空的 poem
        // 2. 评分和评论数
        // 3. poem.length
        // 4. 最近未推送给当前用户的
        $builder = Poem::query()->where('language_id', '=', '1');
        if(!empty($with)) $builder->with($with);

        return $builder // TODO 1. 如果显示声明原创的诗歌，是否需要跟普通诗歌区分开？ 2. 对声明原创的诗歌，gate 中定义只允许上传用户编辑
        ->inRandomOrder(rand(0, 1000000))
            ->take($num);
    }

    /**
     *
     * @param $name
     * @return array
     */
    public static function searchByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        return Poem::query()->select(['title', 'poet_id', 'translator_id'])
            ->whereRaw("lower(`title`) LIKE $value ")
            ->orWhereHas('poetAuthor', function($q) use ($value) {
                $q->where(function($q) use ($value) {
                    $q->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");
                });
            })
            ->orWhereHas('translatorAuthor', function($q) use ($value) {
                $q->where(function($q) use ($value) {
                    $q->whereRaw("JSON_SEARCH(lower(`name_lang`), 'all', $value)");
                });
            })->with('poetAuthor')->get()->toArray();
    }

    /**
     * @return mixed
     */
    public static function randomOne() {
        return self::random(1)->first();
    }

    public function getPoemFromFakeId($fakeId, $select = null){
        $id = Poem::getIdFromFakeId($fakeId);

        if($select)
            return $this->newQuery()->select($select)->findOrFail($id);

        return $this->newQuery()->findOrFail($id);
    }

    public function getByTagId($tagId, $orderBy, $startTime = null, $endTime = null) {
        // TODO Poem::where() tag_id=$tagId, with(['uploader', 'scores'])
        $poems = \App\Models\Tag::where('id', '=', $tagId)->with('poems')->first()->poems();
        if($startTime) {
            $poems->where('poem.created_at', '>=', $startTime);
        }
        if($endTime) {
            $poems->where('poem.created_at', '<=', $endTime);
        }

        return $poems->with('reviews')->orderByDesc($orderBy)->get()->map(function (Poem $item) use ($startTime, $endTime) {
            $item['date_ago'] = date_ago($item->created_at);
            $item['poet'] = $item->poetLabel;
            if(!(config('app.env') === 'production')) {
                $item['poet'] = $item['poet'] . '-' .$item->id;
            }
            $item['poet_is_v'] = $item->is_owner_uploaded===Poem::$OWNER['uploader'] && $item->uploader && $item->uploader->is_v;
            $item['reviews_count'] = $item->reviews->count();
            $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
            $item['score_count'] = $endTime ? ScoreRepository::calcCount($item->id, $startTime, $endTime) : ScoreRepository::calcCount($item->id);
            return $item;
        });
    }

    /**
     * 选取最赞列表的诗歌
     * @param $tagId
     * @param null $startTime
     * @param null $endTime
     * @param int $scoreMin
     * @return mixed
     */
    public function getTopByTagId($tagId, $startTime = null, $endTime = null, $scoreMin = 6) {
        $poemIds = Poem::select('id')->whereHas('tags', function($q) use ($tagId) {
            $q->where('tag.id', '=', $tagId);
        })->pluck('id');

        $poemIdsScoredByV = Score::select(['user_id', 'poem_id'])
            ->whereIn('poem_id', $poemIds)
            // ->whereHas('user', function ($q) {
            //     $q->where('is_v', '=', 1);
            // })
            ->pluck('poem_id');

        $poems = Poem::whereIn('id', $poemIdsScoredByV)->where('score', '>', $scoreMin);
        if($startTime) {
            $poems->where('poem.created_at', '>=', $startTime);
        }
        if($endTime) {
            $poems->where('poem.created_at', '<=', $endTime);
        }

        return $poems->with('reviews')->orderByDesc('score')->get()->map(function (Poem $item) use ($startTime, $endTime) {
            $item['date_ago'] = date_ago($item->created_at);
            $item['poet'] = $item->poetLabel;
            if(!(config('app.env') === 'production')) {
                $item['poet'] = $item['poet'] . '-' .$item->id;
            }
            $item['reviews_count'] = $item->reviews->count();
            $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
            $item['score_count'] = $endTime ? ScoreRepository::calcCount($item->id, $startTime, $endTime) : ScoreRepository::calcCount($item->id);
            return $item;
        });
    }

    // TODO withReview in one method
    private function _withReviews($q) {

    }

    public function getByOwner($userId) {
        return self::newQuery()->where([
            ['is_owner_uploaded', '=', '1'],
            ['upload_user_id', '=', $userId],
        ])->with('reviews')->orderByDesc('created_at')->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poetLabel;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
            return $item->only(self::$listColumns);
        });
    }

    public static $listColumns = [
        'id', 'created_at', 'date_ago', 'title', //'subtitle', 'preface', 'location',
        'poem', 'poet', 'poet_id', 'poet_avatar', 'poet_cn',
        'score', 'score_count', 'score_weight', 'rank',
        'reviews', 'reviews_count', 'poet_is_v'
    ];
    public static $relatedReviewColumns = ['id', 'avatar', 'content', 'pure_content', 'created_at', 'name', 'user_id'];

    /**
     * @param $userId
     * @param bool $isCampaignPoem
     * @param bool $excludeSelf exclude poem that upload_user_id=userId. ONLY for $isCampaignPoem == true
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getRelated($userId, $isCampaignPoem=false, $excludeSelf=true) {
        $q = self::newQuery();

        if($isCampaignPoem) {
            $tagIds = Campaign::select('tag_id')->pluck('tag_id');

            $q->whereHas('tags', function ($q) use($tagIds) {
                $q->whereIn('tag.id', $tagIds);
            });

            if($excludeSelf) {
                $q->where([
                    // ['is_owner_uploaded', '=', '1'],
                    ['upload_user_id', '<>', $userId],
                ]);
            }
        }

        $q->where(function ($q) use ($userId) {
            $q->whereHas('reviews', function($q) use ($userId) {
                $q->where(['user_id' => $userId]);
            })->orWhereHas('scores', function($q) use ($userId) {
                $q->where(['user_id' => $userId]);
            });
        });

        return $q->with('reviews')->orderByDesc('created_at')->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet'] = $item->poet_label;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            $item['reviews'] = $item->reviews->take(2)->map->only(self::$relatedReviewColumns);
            return $item->only(self::$listColumns);
        });
    }


    public static function isDuplicated(string $poem) {
        // TODO poem soft deleted, but content not deleted???
        // TODO use simhash
        if(mb_strlen($poem) <= 10) {
            return false;
        }
        $contentHash = Str::contentHash($poem);
        $existed = Content::where([
            'hash_crc32' => Str::crc32($contentHash),
            'hash' => $contentHash
        ])->whereHas('poem')->orderBy('created_at')->first();

        if($existed) {
            return Poem::find($existed->entry_id);
        }
        return false;
    }

    public function getCampaignPoemsByTagId($tagId) {

        if(!is_numeric($tagId)) {
            return $this->responseFail();
        }

        $campaign = Tag::find($tagId)->campaign;
        if(!$campaign) {
            // campaign deleted
            return [];
        }

        $dateInterval = Carbon::parse($campaign->end)->diff(now());
        $campaignEnded = $dateInterval->invert === 0;

        if(isset($campaign->settings['result'])) {

            $byScoreData = $campaign->settings['result'];
            foreach ($byScoreData as $key=>&$poem) {
                $poem['date_ago'] = date_ago($poem['created_at']);
                // TODO unset this lower than 7 limit if list performance allowed
                if($poem['score'] <= 7) {
                    unset($byScoreData[$key]);
                    continue;
                }
                $poem = collect($poem)->only(self::$listColumns);
            }

        } else {
            // poem before endDate, scores before endDate
            if($campaign->id >= 6 || (!(config('app.env') === 'production'))) {
                $byScore = $this->getTopByTagId($tagId, $campaign->start, $campaign->end);
            } else {
                $byScore = $this->getByTagId($tagId, 'score', $campaign->start, $campaign->end);
            }

            $limit = $campaign->settings['rank_min_weight'] ?? 3;
            $byScoreData = $byScore->filter(function ($value) use ($limit) {
                // 票数不足的不参与排名
                // dump($value['score_count']);
                // TODO should use $item->getCampaignScore returned score_count
                return $value['score_count'] >= $limit;
            })->map(function (Poem $item) use ($campaign) {
                $item['poet_is_v'] = $item->is_owner_uploaded===Poem::$OWNER['uploader'] && $item->uploader && $item->uploader->is_v;
                $score = $item->getCampaignScore($campaign);
                $item['score'] = $score['score'];
                $item['score_weight'] = $score['weight'];
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
            })->map->only(self::$listColumns)->values()->map(function ($item, $index) {
                // $item = $item->toArray();
                $item['rank'] = $index + 1;
                return $item;
            });

            // TODO this should be done in a command when campaign ends
            // and in case of command failed to execute, do it again here at controller
            if($campaignEnded) {
                $newSetting = $campaign->settings;
                // TODO save poem.id poem.score poem->score_count only
                $newSetting['result'] = $byScoreData;
                $campaign->settings = $newSetting;
                $campaign->save();
            }
        }


        return [
            'byScore' => $byScoreData,
            // TODO if weapp use virtual list, remove splice
            'byCreatedAt' => $this->getByTagId($tagId, 'created_at')->splice(0,150)
                ->map->only(self::$listColumns)
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
