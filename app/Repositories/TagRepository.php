<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class TagRepository extends BaseRepository {
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
        return Tag::select(['id', 'name_lang'])
            ->whereRaw('LOWER(tag.name_lang) LIKE ' . $value)->first();
    }
    public function findByCategoryId($cid) {
        return $this->newQuery()->where('category_id', '=', $cid)->get();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Tag::class;
    }

    public static function allInUse() {
        return Tag::where('name', '<>', '')->get();
    }

}
