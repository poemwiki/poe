<?php

namespace App\Repositories;

use App\Models\Poem;
use App\Models\Review;
use App\User;

class ReviewRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'content'
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
        return Review::class;
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

    public function listByOriginalPoem(Poem $poem, $perPage = 10, $order = 'updated_at', $direction = 'desc', $columns = ['*']) {
        $poemIds = [$poem->id];
        if ($poem->original_id) {
            $poemIds[] = $poem->original_id;
            $op = Poem::where('original_id', $poem->original_id)->get();
            $poemIds = $op->pluck('id')->concat($poemIds)->all();
        }
        if ($poem->translatedPoems) {
            $poemIds = $poem->translatedPoems->pluck('id')->concat($poemIds)->all();
        }
        $query = $this->allQuery()->whereIn('poem_id', $poemIds)->with('poem')->orderBy($order, $direction);
        return $query->paginate($perPage, $columns);
    }

    public function listByPoem(Poem $poem, $perPage = 10, $order = 'updated_at', $direction = 'desc', $columns = ['*']) {
        $query = $this->allQuery()->where(['poem_id' => $poem->id])->orderBy($order, $direction);
        return $query->paginate($perPage, $columns);
    }

    public function listByUser(User $user, $perPage = 10, $order = 'updated_at', $direction = 'desc', $columns = ['*']) {
        $query = $this->allQuery()->where(['user_id' => $user->id])->orderBy($order, $direction);
        return $query->paginate($perPage, $columns);
    }


}
