<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Cache;
use Illuminate\Http\Request;

/**
 * Class LanguageController.
 */
class CampaignAPIController extends Controller {
    /** @var CampaignRepository */
    private $campaignRepository;
    /** @var PoemRepository */
    private $poemRepository;

    public function __construct(CampaignRepository $campaignRepository, PoemRepository $poemRepository) {
        $this->campaignRepository = $campaignRepository;
        $this->poemRepository     = $poemRepository;
    }

    public function index(Request $request) {
        // TODO Cache::forget('api-campaign-index') if new campaign set
        $campaigns = Cache::remember('api-campaign-index', now()->addMinutes(config('app.env') === 'production' ? 3 : 0), function () {
            return $this->campaignRepository->allInUse()->map(function ($campaign) {
                $ret = $campaign->toArray();
                $ret['settings'] = collect($campaign->settings)->except(['result']);
                $ret['poem_count'] = $campaign->poem_count;
                $ret['user_count'] = $campaign->user_count;

                return $ret;
            });
        });

        return $this->responseSuccess($campaigns);
    }

    public function show($id) {
        // TODO Cache::forget('api-campaign-show-') if new campaign poem uploaded
        $ttl = now()->addMinutes(config('app.env') === 'production' ? 1 : 0);
        $ret = Cache::remember('api-campaign-show-' . $id, $ttl, function () use ($id) {
            /** @var Campaign $campaign */
            $campaign = $this->campaignRepository->find($id);

            if (empty($campaign)) {
                return $this->responseFail([], '没有找到这个活动。', self::$CODE['no_entry']);
            }
            $ret = $campaign->toArray();

            $poems = $this->poemRepository->getCampaignPoemsByTagId($campaign->tag_id);
            $ret['poemData'] = $poems;
            $ret['settings'] = collect($campaign->settings)->except(['result']);
            $ret['poem_count'] = $campaign->poem_count;
            $ret['user_count'] = $campaign->user_count;

            return $ret;
        });

        return $this->responseSuccess($ret);
    }
}
