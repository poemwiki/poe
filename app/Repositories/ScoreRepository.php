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
    public static function model() {
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

    public function listByPoemsUsers($poemIds, $userIds, $columns = ['*']) {
        $query = $this->allQuery()->select($columns)->whereIn('poem_id', $poemIds, 'and')
            ->whereIn('user_id', $userIds);
        return $query;
    }

    public function listByPoemsUser($poemIds, $userId, $columns = ['poem_id', 'score', 'weight']) {
        $query = $this->allQuery()->select($columns)->whereIn('poem_id', $poemIds, 'and')
            ->where('user_id', '=', $userId);
        return $query;
    }

    public function listByUserId($userId, $columns = ['poem_id', 'score']) {
        $query = $this->allQuery()->select($columns)
            ->where('user_id', '=', $userId);
        return $query;
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
        return self::calc($poemId);
    }

    /**
     * @param $poemId
     * @param null $start
     * @param null $end
     * @return array
     */
    public static function calc($poemId, $start = null, $end = null) {
        $query = Score::query()->where(['poem_id' => $poemId]);
        if($start) {
            $query->where('updated_at', '>=', $start);
        }
        if($end) {
            $query->where('updated_at', '<=', $end);
        }

        $scores = $query->get();
        return self::calcScores($scores);
    }

    /**
     * @param \App\Models\Score[] $scores
     * @param bool $withGroupCount
     * @return array
     */
    public static function calcScores($scores, $withGroupCount = true) {
        $scoreTotal = ['sum' => 0, 'weight' => 0, 'score' => null, 'count' => null];

        if($withGroupCount) {
            $scoreTotal['groupCount'] = $scores->groupBy('score')->map(function ($item) {
                return collect($item)->count();
            });
        }

        foreach ($scores as $item) {
            $scoreTotal['sum'] += $item['score'] * $item['weight'];
            $scoreTotal['weight'] += $item['weight'];
        }

        $scoreTotal['score'] = $scoreTotal['weight'] ? number_format($scoreTotal['sum'] / $scoreTotal['weight'], 1) : null;
        $scoreTotal['count'] = count($scores);
        return $scoreTotal;
    }

    /**
     * @param $poemIds
     * @param bool $withGroupCount
     * @param null $start
     * @param null $end
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public static function batchCalc($poemIds, $start = null, $end = null, $withGroupCount=false) {
        $query = Score::query()->whereIn('poem_id', $poemIds);

        if($start) {
            $query->where('updated_at', '>=', $start);
        }
        if($end) {
            $query->where('updated_at', '<=', $end);
        }

        $scores = $query->get();
        $poemScores = $scores->groupBy('poem_id')->map(function ($item) use ($withGroupCount) {
            return self::calcScores($item, $withGroupCount);
        });

        return $poemScores->toArray();
    }

    public static function calcWeight($poemId) {
        return Score::query()->select('weight')->where(['poem_id' => $poemId])->sum('weight');
    }

    // TODO save count to poem.score_count and poem.campaign_score_count
    public static function calcCount($poemId, $startTime = null, $endTime = null) {
        $builder = Score::query()->where(['poem_id' => $poemId]);
        if($startTime) {
            $builder->where('updated_at', '>=', $startTime);
        }
        if($endTime) {
            $builder->where('updated_at', '<=', $endTime);
        }
        return $builder->count('user_id');
    }

}
