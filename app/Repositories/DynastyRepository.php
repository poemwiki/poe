<?php

namespace App\Repositories;

use App\Models\Dynasty;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class DynastyRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'name_lang',
        'id'
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
        return Dynasty::select(['id', 'name_lang'])
            ->whereRaw('LOWER(your_table.your_column) LIKE ' . $value)->first()->toArray();
    }

    /**
     * Configure the Model
     **/
    public function model() {
        return Dynasty::class;
    }

    public static function allInUse() {
        return Dynasty::select('name_lang', 'id', 'f_id', 'describe_lang')->where('f_id', '=', '0')->with('children')->orderBy('id', 'desc')->get();
    }
}
