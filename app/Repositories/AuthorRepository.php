<?php

namespace App\Repositories;

use App\Models\Author;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository
 * @package App\Repositories
 * @version July 19, 2020, 11:24 am UTC
 */
class AuthorRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
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
        return Author::select(['id', 'name_lang'])
            ->whereRaw('LOWER(author.name_lang) LIKE ' . $value)->first()->toArray();
    }

    /**
     * Configure the Model
     **/
    public static function model() {
        return Author::class;
    }
}
