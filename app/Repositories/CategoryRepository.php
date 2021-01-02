<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class CategoryRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'name_lang',
        'describe_lang'
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
        return Category::select(['id', 'name_lang'])
            ->whereRaw('LOWER(name_lang) LIKE ' . $value)->first()->toArray();
    }
    public static function searchByName($name, $id=null) {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($name) . '%');
        $query = Category::select(['id', 'name_lang'])
            ->whereRaw("JSON_SEARCH(lower(`name_lang`), 'one', $value)");
        if(is_numeric($id))
            $query->union(Category::find($id)->select(['id', 'name_lang']));
        return $query->get()->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Category::class;
    }

    public static function allInUse() {
        return Category::select(['id', 'name'])->where('name', '<>', '')->get();
    }
}
