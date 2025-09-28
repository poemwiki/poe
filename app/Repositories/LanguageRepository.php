<?php

namespace App\Repositories;

use App\Models\Language;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository.
 * @version July 19, 2020, 11:24 am UTC
 */
class LanguageRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'name_cn',
        'name_lang',
        'id'
    ];

    /**
     * Return searchable fields.
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    public static function findByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');

        return Language::select(['id', 'name_lang'])
            ->whereRaw('LOWER(your_table.your_column) LIKE ' . $value)->first()->toArray();
    }

    /**
     * Configure the Model.
     **/
    public static function model() {
        return Language::class;
    }

    public static function allInUse($selectFields = ['*']) {
        return Language::select($selectFields)->where('name', '<>', '')->get();
    }

    public static function idsInUse() {
        return Language::select('id')->where('name', '<>', '')->get()->pluck('id');
    }
}
