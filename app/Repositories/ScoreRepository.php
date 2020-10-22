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
class ScoreRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
//        'poem_id',
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
    public function listAll($perPage, $order = 'updated_at', $direction = 'desc', $columns = ['*']) {
        $query = $this->allQuery()->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }

    public function listByPoem(Poem $poem, $perPage = 10, $order = 'created_at', $direction = 'desc', $columns = ['*']) {
        $query = $this->allQuery()->where(['poem_id' => $poem->id])->orderBy($order, $direction);

        return $query->paginate($perPage, $columns);
    }


    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return \Illuminate\Database\Eloquent\Model|int
     */
    public function updateOrCreate(array $attributes, array $values = []) {
        $find = $this->model->withTrashed()->where($attributes)->first();
        if($find) {
            $find->restore();
            return $find->update($values);
        } else {
            return $this->newQuery()->updateOrCreate($attributes, $values);
        }
    }



    /**
     * @param Poem $poem
     * @return array
     */
    public function calcScoreByPoem(Poem $poem) {
        return $this->calcScoreByPoemId($poem->id);
    }

    /**
     * @param Int $poemId
     * @return array
     */
    public function calcScoreByPoemId($poemId): array {
        $res = $this->allQuery()->where(['poem_id' => $poemId])->get();
        $scores = $res;//->toArray();

        $groupCount = $res->groupBy('score')->map(function ($item) {
            return collect($item)->count();
        });

        $scoreTotal = ['sum' => 0, 'weight' => 0, 'groupCount' => $groupCount, 'score' => null, 'count' => null];
        foreach ($scores as $item) {
            $scoreTotal['sum'] += $item['score'] * $item['weight'];
            $scoreTotal['weight'] += $item['weight'];
        }

        $scoreTotal['score'] = $scoreTotal['weight'] ? number_format($scoreTotal['sum'] / $scoreTotal['weight'], 1) : null;
        $scoreTotal['count'] = count($scores);
        return $scoreTotal;
    }

}
