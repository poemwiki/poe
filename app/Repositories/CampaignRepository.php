<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository.
 * @version July 19, 2020, 11:24 am UTC
 */
class CampaignRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'name_lang',
        'describe_lang',
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

        return Campaign::select(['id', 'name_lang'])
            ->whereRaw('LOWER(tag.name_lang) LIKE ' . $value)->first();
    }

    /**
     * Configure the Model.
     **/
    public static function model() {
        return Campaign::class;
    }

    public static function allInUse() {
        return Campaign::with('tag:id,name_lang')
            ->select(['id', 'image', 'start', 'end', 'name_lang', 'tag_id'])
            ->orderBy('start', 'desc')->get();
    }
}
