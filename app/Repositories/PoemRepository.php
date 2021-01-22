<?php

namespace App\Repositories;

use App\Models\Poem;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

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
        return Poem::query()->with('wx', 'lang')
            ->where('is_owner_uploaded', '<>', 1) // TODO 1. 如果显示声明原创的诗歌，是否需要跟普通诗歌区分开？ 2. 对声明原创的诗歌，只允许上传用户编辑
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
        // return $this->newQuery()->with('wx', 'lang')->findOrFail($id);
        if($select)
            return $this->newQuery()->select($select)->findOrFail($id);
        return $this->newQuery()->findOrFail($id);
    }

    public function getByTagId($tagId) {
        return \App\Models\Tag::where('id', '=', $tagId)->with('poems')->first()->poems->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->updated_at ?? $item->created_at)->diffForHumans(now());
            $item['poet_image'] = $item->uploader->avatarUrl;
            return $item;
        });
    }

    public function getByOwner($userId) {
        return self::newQuery()->where([
            ['is_owner_uploaded', '=', '1'],
            ['upload_user_id', '=', $userId],
        ])->get()->map(function ($item) {
            $item['date_ago'] = \Illuminate\Support\Carbon::parse($item->updated_at ?? $item->created_at)->diffForHumans(now());
            $item['poet_image'] = $item->uploader->avatarUrl;
            return $item;
        });
    }

}
