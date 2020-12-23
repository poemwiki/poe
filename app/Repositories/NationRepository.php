<?php

namespace App\Repositories;

use App\Models\Dynasty;
use App\Models\Nation;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class NationRepository extends BaseRepository {
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
        return Nation::select(['id', 'name_lang'])
            ->whereRaw('LOWER(nation.name_lang) LIKE ' . $value)->first()->toArray();
    }
    public static function searchByName($name, $id) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Nation::select(['id', 'name_lang'])
            ->whereRaw("JSON_SEARCH(lower(`name_lang`), 'one', $value)");
        if(is_numeric($id))
            $query->union(Nation::find($id)->select(['id', 'name_lang']));
        return $query->get()->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Nation::class;
    }

    public static function allInUse() {
        return Nation::select('name_lang', 'id')->whereNull('deleted_at')->orderBy('id', 'desc')->get()->toArray();
    }
}
