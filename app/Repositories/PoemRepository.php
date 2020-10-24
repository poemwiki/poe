<?php

namespace App\Repositories;

use App\Models\Poem;
use App\Repositories\BaseRepository;

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
    public function model() {
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
        return Poem::select()->with('wx')
            ->inRandomOrder()
            ->take($num)->get();
    }

    /**
     * @return mixed
     */
    public static function randomOne() {
        return self::random(1)->first();
    }

    public function getPoemFromFakeId($fakeId, $select='*'){
        $id = Poem::getIdFromFakeId($fakeId);
        return Poem::findOrFail($id, $select);
    }
}
