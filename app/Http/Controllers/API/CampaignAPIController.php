<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Cache;

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

    public function index() {
        // TODO Cache::forget('api-campaign-index') if new campaign set
        $campaigns = Cache::remember('api-campaign-index', now()->addMinutes(config('app.env') === 'production' ? 3 : 0), function () {
            return $this->campaignRepository->allInUse()->slice(0, 15)
                ->map(function ($campaign) {
                    if (isset($campaign->settings['test']) && $campaign->settings['test']) {
                        return null;
                    }
                    $ret               = $campaign->toArray();
                    $ret['settings']   = collect($campaign->settings)->except(['result']);
                    $ret['poem_count'] = $campaign->poem_count;
                    $ret['user_count'] = $campaign->user_count;

                    return $ret;
                })
                ->filter(function ($campaign) {
                    return $campaign;
                })->values();
        });

        return $this->responseSuccess($campaigns);
    }

    // TODO get campaign app code image from cache or generate it
    // (new Weapp())->fetchAppCodeImg('campaign-35', storage_path('app/public/campaign/'.'35'), 'pages/index/index')

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

        return $this->responseSuccess($ret);
    }
}
