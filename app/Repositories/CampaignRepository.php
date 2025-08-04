<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
            ->select(['id', 'image', 'start', 'end', 'name_lang', 'tag_id', 'settings'])
            ->orderBy('start', 'desc')->get();
    }

    public function paninatedIndex($offset, $limit) {
        $cacheKey = "api-campaign-index-{$offset}-{$limit}";
        return Cache::tags(['campaign-index'])->remember($cacheKey, 4600*72, function () use ($offset, $limit) {
            return $this->allInUse()->slice($offset, $limit)
                ->map(function ($campaign) {
                    if (isset($campaign->settings['test']) && $campaign->settings['test']) {
                        return null;
                    }
                    $ret = $campaign->toArray();
                    $ret['settings'] = collect($campaign->settings)->except(['result']);
                    $ret['poem_count'] = $campaign->poem_count;
                    $ret['user_count'] = $campaign->user_count;

                    return $ret;
                })
                ->filter(function ($campaign) {
                    return $campaign;
                })->values();
        });
    }
    
    /**
     * Clear all campaign index cache entries using cache tags
     */
    public static function clearCampaignIndexCache() {
        Cache::tags(['campaign-index'])->flush();
    }
}
