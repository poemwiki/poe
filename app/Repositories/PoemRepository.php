<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\Content;
use App\Models\Poem;
use App\Models\Review;
use App\Models\Score;
use App\Repositories\BaseRepository;
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
     * @return mixed
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
    public static function random($num = 1) {
        // TODO 选取策略： 1. 优先选取 poem.bedtime_post_id 不为空的 poem
        // 2. 评分和评论数
        // 3. poem.length
        // 4. 最近未推送给当前用户的
        return Poem::query()->with('wx', 'lang') // TODO 1. 如果显示声明原创的诗歌，是否需要跟普通诗歌区分开？ 2. 对声明原创的诗歌，gate 中定义只允许上传用户编辑
            ->inRandomOrder()
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

    public function getByTagId($tagId, $orderBy) {
        // TODO Poem::where() tag_id=$tagId, with(['uploader', 'scores'])
        $poems = \App\Models\Tag::where('id', '=', $tagId)->with('poems')->first()->poems();

        return $poems->orderByDesc($orderBy)->get()->map(function ($item, $index) use ($orderBy) {

            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet_image'] = $item->uploader->avatarUrl;
            $item['poet'] = $item->poetLabel;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            return $item;
        });
    }

    public function getByOwner($userId) {
        return self::newQuery()->where([
            ['is_owner_uploaded', '=', '1'],
            ['upload_user_id', '=', $userId],
        ])->orderByDesc('created_at')->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet_image'] = $item->uploader->avatarUrl;
            $item['poet'] = $item->poetLabel;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            return $item;
        });
    }

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
        return $q->orderByDesc('created_at')->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->created_at)->diffForHumans(now());
            $item['poet_image'] = $item->uploader->avatarUrl;
            $item['poet'] = $item->poetLabel;
            $item['score_count'] = ScoreRepository::calcCount($item->id);
            return $item;
        });
    }


    public static function isDuplicated(string $poem) {
        // TODO poem soft deleted, but content not deleted???
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

}
