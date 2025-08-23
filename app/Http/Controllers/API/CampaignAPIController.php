<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Illuminate\Support\Facades\Cache;

class CampaignAPIController extends Controller {
    /** @var CampaignRepository */
    private $campaignRepository;
    /** @var PoemRepository */
    private $poemRepository;

    public function __construct(CampaignRepository $campaignRepository, PoemRepository $poemRepository) {
        $this->campaignRepository = $campaignRepository;
        $this->poemRepository     = $poemRepository;
    }

    /**
     * for /campaign page.
     * @param $page
     * @return array
     */
    public function list($page = 1) {
        $paginator = Campaign::with('tag:id,name_lang')
            ->where('start', '<=', now())
            ->whereRaw("(settings->'$.test' is null or settings->'$.test' = 0)")
            ->orderBy('start', 'desc')
            ->paginate(5,
                ['id', 'image', 'start', 'end', 'name_lang', 'tag_id'],
                'page', $page
            );

        // TODO cache this
        return $this->responseSuccess([
            'data'           => $paginator->items(),
            'total'          => $paginator->total(),
            'per_page'       => $paginator->perPage(),
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages()
        ]);
    }

    public function index($offset = 0, $limit = 15) {
        $campaigns = $this->campaignRepository->paginatedIndex($offset, $limit);

        return $this->responseSuccess($campaigns);
    }

    // TODO get campaign app code image from cache or generate it
    // (new Weapp())->fetchAppCodeImg('campaign-35', storage_path('app/public/campaign/'.'35'), 'pages/index/index')

    public function show($id) {
        // TODO Cache::forget('api-campaign-show-') if new campaign poem uploaded
        $ttl = 60;
        $campaignData = Cache::remember('api-campaign-show-' . $id, $ttl, function () use ($id) {
            /** @var Campaign $campaign */
            $campaign = $this->campaignRepository->find($id);

            if (empty($campaign)) {
                return null;
            }
            $ret = $campaign->toArray();

            $poems                              = $this->poemRepository->getCampaignPoemsByTagId($campaign->tag_id);
            $ret['poemData']                    = $poems;
            $ret['settings']                    = collect($campaign->settings)->except(['result'])->toArray();
            $ret['settings']['inner_image_url'] = cosUrl($campaign->settings['inner_image_url'] ?? $campaign->image
            );
            $ret['settings']['share_image_url'] = cosUrl($campaign->settings['share_image_url'] ?? $campaign->image);
            if (isset($ret['settings']['sell']['picUrl'])) {
                $ret['settings']['sell']['picUrl'] = cosUrl($campaign->settings['sell']['picUrl']);
            }
            $ret['poem_count'] = $campaign->poem_count;
            $ret['user_count'] = $campaign->user_count;

            $ret['image_url'] = cosUrl($campaign->image_url);

            // additional data
            // TODO remove this if weapp don't need campaign.share_image_url
            // (after onShareAppMessage function of weapp is updated)
            $ret['share_image_url'] = $ret['settings']['share_image_url'];

            return $ret;
        });

        $isHidden = (isset($campaignData['settings']['test']) && $campaignData['settings']['test']);
        if (!$isHidden) {
            $this->flushCampaignIndexCache($campaignData['id']);
        }

        return $this->responseSuccess($campaignData);
    }

    private function flushCampaignIndexCache($id) {
        // clear campaign index cache if current campaign id bigger than the latest cached one
        $cacheCampaignCollection = Cache::tags(['campaign-index'])->get('api-campaign-index-0-15');
        if ($cacheCampaignCollection) {
            $latestCampaign = $cacheCampaignCollection->first();
            if (empty($latestCampaign)) {
                return;
            }
            $latestCampaignId = $latestCampaign['id'] ?? 0;
            if ($id > $latestCampaignId) {
                Cache::tags(['campaign-index'])->flush();
            }
        }
    }
}
