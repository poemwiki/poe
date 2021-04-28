<?php

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class GenreRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'name_cn'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    public static function findByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        return Genre::select(['id', 'name_lang'])
            ->whereRaw('LOWER(your_table.your_column) LIKE ' . $value)->first()->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Genre::class;
    }

    public static function allInUse() {
        return static::model()::select('name_lang', 'id')->whereNull('deleted_at')->orderBy('id', 'desc')->get();
    }
}
