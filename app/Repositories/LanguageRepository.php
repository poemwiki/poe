<?php

namespace App\Repositories;

use App\Models\Language;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class LanguageRepository extends BaseRepository {
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

    public static function listAll() {
        $langs = Language::select(['id', 'name_cn'])->get()->toArray();
        $langList = [];
        foreach ($langs as $value) {
            $langList[$value['id']] = $value['name_cn'];
        };
        return $langList;
    }

    public static function findByName($name) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        return Language::select(['id', 'name_lang'])
            ->whereRaw('LOWER(your_table.your_column) LIKE ' . $value)->first()->toArray();
    }

    /**
     * Configure the Model
     **/
    public function model() {
        return Language::class;
    }
}
