<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use App\Repositories\PoemRepository;
use Cache;

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

    /**
     * for /campaign page.
     * @param $page
     * @return array
     */
    public function list($page = 1) {
        $paginator = Campaign::with('tag:id,name_lang')
            ->orderBy('start', 'desc')->paginate(20,
                ['id', 'image', 'start', 'end', 'name_lang', 'tag_id'],
                'page', $page
            );

        $data = $paginator->map(function ($campaign) {
            $ret = $campaign->toArray();

            return $ret;
        });

        // TODO cache this
        return $this->responseSuccess([
            'data'           => $data,
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
                ->filter(function ($campaign) {
                    return !isset($campaign->settings['test']) || $campaign->settings['test'] != true;
                })->map(function ($campaign) {
                    $ret = $campaign->toArray();
                    $ret['settings'] = collect($campaign->settings)->except(['result']);
                    $ret['poem_count'] = $campaign->poem_count;
                    $ret['user_count'] = $campaign->user_count;

                    return $ret;
                });
        });

        return $this->responseSuccess($campaigns);
    }

    // TODO get campaign app code image from cache or generate it
    // (new Weapp())->fetchAppCodeImg('campaign-35', storage_path('app/public/campaign/'.'35'), 'pages/index/index', 0)

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
