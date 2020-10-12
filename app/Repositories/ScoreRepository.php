<?php

namespace App\Repositories;

use App\Models\Score;
use App\Models\Poem;
use App\Repositories\BaseRepository;

/**
 * Class PoemRepository
 * @package App\Repositories
 * @version July 17, 2020, 12:24 pm UTC
*/

class ScoreRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
//        'content_id',
        'factor',
//        'score',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model() {
        return Score::class;
    }


    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listAll($perPage, $order = 'updated_at', $direction= 'desc', $columns = ['*']) {
        $query = $this->allQuery()->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }

    public function listByPoem(Poem $poem, $perPage = 10, $order = 'created_at', $direction = 'desc', $columns = ['*']) {
        $query = $this->allQuery()->where(['poem_id' => $poem->id])->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }

    public function calcScoreByPoem(Poem $poem) {
        $res = $this->allQuery()->where(['poem_id' => $poem->id])->get();
        $scores = $res;//->toArray();
//        return ['sum' => 0, 'weight' => 0, 'groupCount' => [], 'score' => 2, 'count' => 3];
        $groupCount = $res->groupBy('score')->map(function ($item) {
            return collect($item)->count();
        });

//        $scoreTotal = array_reduce($scores, function($carry, $item) {
//            $carry['sum'] += $item['score'] * $item['weight'];
//            $carry['weight'] += $item['weight'];
//            return $carry;
//        }, ['sum' => 0, 'weight' => 0, 'groupCount' => $groupCount]);
        $scoreTotal = ['sum' => 0, 'weight' => 0, 'groupCount' => $groupCount, 'score' => null, 'count' => null];
        foreach($scores as $item) {
            $scoreTotal['sum'] += $item['score'] * $item['weight'];
            $scoreTotal['weight'] += $item['weight'];
        }

        $scoreTotal['score'] = $scoreTotal['weight'] ? $scoreTotal['sum'] / $scoreTotal['weight'] : null;
        $scoreTotal['count'] = count($scores);
        return $scoreTotal;
    }

}
