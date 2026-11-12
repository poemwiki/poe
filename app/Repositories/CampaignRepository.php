<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguageRepository.
 * @version July 19, 2020, 11:24 am UTC
 */
class CampaignRepository extends BaseRepository {
    private const INDEX_CACHE_TTL_SECONDS = 180;

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
        return self::visibleCampaignQuery()->get();
    }

    private static function visibleCampaignQuery() {
        return Campaign::with('tag:id,name_lang')
            ->select(['id', 'image', 'start', 'end', 'name_lang', 'tag_id', 'settings'])
            ->whereRaw("(settings->'$.test' is null or settings->'$.test' = 0)")
            ->orderBy('start', 'desc')
            ->orderBy('id', 'desc');
    }

    public function paginatedIndex($offset, $limit) {
        $cacheKey = "api-campaign-index-{$offset}-{$limit}";
        $cache    = Cache::tags(['campaign-index']);
        $cached   = $cache->get($cacheKey);

        // Empty index results can become stale because campaigns are populated outside this application.
        if ($cached !== null && collect($cached)->isNotEmpty()) {
            return $cached;
        }

        $campaigns = self::visibleCampaignQuery()
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($campaign) {
                $ret               = $campaign->toArray();
                $ret['settings']   = collect($campaign->settings)->except(['result']);
                $ret['poem_count'] = $campaign->poem_count;
                $ret['user_count'] = $campaign->user_count;

                return $ret;
            });

        if ($campaigns->isNotEmpty()) {
            $cache->put($cacheKey, $campaigns, self::INDEX_CACHE_TTL_SECONDS);
        }

        return $campaigns;
    }

    /**
     * Clear all campaign index cache entries using cache tags
     */
    public static function clearCampaignIndexCache() {
        Cache::tags(['campaign-index'])->flush();
    }
}
