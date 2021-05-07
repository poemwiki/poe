<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use Illuminate\Http\Request;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class CampaignAPIController extends Controller {
    /** @var  CampaignRepository */
    private $campaignRepository;

    public function __construct(CampaignRepository $campaignRepository) {
        $this->campaignRepository = $campaignRepository;
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
            return $this->sendError('Language not found');
        }

        return $this->responseSuccess($campaign->toArray());
    }
}
