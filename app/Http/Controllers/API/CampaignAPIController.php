<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class CampaignAPIController extends Controller {
    /** @var  CampaignRepository */
    private $campaignRepository;
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(CampaignRepository $campaignRepository, PoemRepository $poemRepository) {
        $this->campaignRepository = $campaignRepository;
        $this->poemRepository = $poemRepository;
    }

    public function index(Request $request) {
        $campaigns = $this->campaignRepository->allInUse()->map(function ($campaign) {
            $ret = $campaign->toArray();
            $ret['settings'] = collect($campaign->settings)->except(['result']);
            $ret['poem_count'] = $campaign->poem_count;
            $ret['user_count'] = $campaign->user_count;
            return $ret;
        });

        return $this->responseSuccess($campaigns);
    }

    public function show($id) {
        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->find($id);

        if (empty($campaign)) {
            return $this->responseFail([], '没有找到这个活动。', self::$CODE['no_entry']);
        }
        $ret = $campaign->toArray();
        $ret['settings'] = collect($campaign->settings)->except(['result']);
        $ret['poem_count'] = $campaign->poem_count;
        $ret['user_count'] = $campaign->user_count;

        $ret['poemData'] = $this->poemRepository->getCampaignPoemsByTagId($campaign->tag_id);

        return $this->responseSuccess($ret);
    }
}
