<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Campaign;
use App\Models\Poem;
use App\Models\Reward;
use App\Models\RewardResult;
use App\Repositories\PoemRepository;
use App\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller {
    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth')->except(['reward', 'index', 'poems']);
        $this->poemRepository = $poemRepo;
    }

    public function reward(int $campaignId, string $fakeUID) {
        $uid      = User::getIdFromFakeId($fakeUID);
        $user     = User::find($uid);
        $campaign = Campaign::find($campaignId);
        if (!$user or !$campaign) {
            return view('campaign.reward', [
                'error' => '参数错误'
            ]);
        }

        Auth::loginUsingId($uid);

        $isParticipated = $user->originalPoemsOwned->filter(function (Poem $poem) use ($campaignId) {
            return $poem->campaign_id === $campaignId;
        })->count();

        $awards = [];
        $error  = '';
        if ($isParticipated) {
            $awards = RewardResult::where([
                ['campaign_id', $campaignId],
                ['user_id', $uid]
            ])->get()->unique(function ($res) {
                return $res->reward->award->id;
            })->map(function ($res) {
                return Award::find($res->reward->award->id);
            });

            try {
                // $autoAward = Award::where([
                //     ['campaign_id', $campaignId],
                //     ['result_type', Award::$RESULT_TYPE['auto']]
                // ])->first();
                // if ($autoAward) {
                //     // TODO show autoRewardResult
                //     $autoRewardResult = $this->getOrConsumeReward($campaignId, $autoAward->id, $uid);
                // }
            } catch (\Exception $e) {
                $error = '获取奖励失败，请刷新重试';
            }

            if (!$awards->count() && !$error) {
                $error = '本次活动奖励已领完，欢迎参加下次活动。';
            }
        } else {
            $error = ('请先在 #' . $campaign->name_lang . ' 活动页面发表你的原创作品，再来此页面领取奖励。');
        }

        return view('campaign.reward-index', [
            'awards'      => $awards,
            'error'       => $error,
            'campaign'    => $campaign
        ]);
    }

    public function show(int $awardID) {
        $userID = auth()->user()->id;
        // TODO save show time to reward_result
        logger()->info('award:' . $awardID . '-' . $userID);
        $results = RewardResult::where([
            ['user_id', $userID]
        ])->whereHas('reward', function ($query) use ($awardID) {
            return $query->where('award_id', '=', $awardID);
        })->get();

        $error = '';
        if (!$results->count()) {
            $error = '参数错误，请联系下方微信处理。';
        }

        return view('campaign.reward', [
            'results'     => $results,
            'error'       => $error,
            'campaignId'  => $results->first()->campaign_id
        ]);
    }

    public function getRewardResult($campaignID, $userID) {
        $consumedReward = RewardResult::where('user_id', '=', $userID)
            ->whereHas('reward', function ($query) use ($campaignID) {
                return $query->where('campaign_id', '=', $campaignID);
            })->get();
        if ($consumedReward->count()) {
            return $consumedReward[0]->reward->reward;
        }

        return null;
    }

    public function getOrConsumeReward($campaignID, $awardID, $userID) {
        $res = $this->getRewardResult($campaignID, $userID);
        if ($res) {
            return $res;
        }

        return $this->consumeReward($campaignID, $awardID, $userID);
    }

    /**
     * @param $campaignID
     * @param $awardID
     * @param $userID
     * @return |null
     * @throws \Exception
     */
    public function consumeReward($campaignID, $awardID, $userID) {
        $reward = Reward::where([
            ['campaign_id', '=', $campaignID],
            ['award_id', '=', $awardID]
        ])->doesntHave('rewardResult')->limit(1)->get();
        if ($reward->count()) {
            $toSave = $reward[0];

            try {
                $result = RewardResult::create([
                    'user_id'     => $userID,
                    'campaign_id' => $campaignID,
                    'reward_id'   => $toSave->id,
                    // 'poem_id'    => // TODO save poem_id
                ]);

                return $result->reward->reward;
            } catch (QueryException $e) {
                throw new \Exception('获取奖励失败，请刷新重试');
            }
        } else {
            return null;
        }
    }

    public function index() {
        return view('campaign.index', [
            'campaigns' => Campaign::all()
        ]);
    }

    public function poems(int $campaignID) {
        $poems = Poem::with('uploader')
            ->select([
                'poem.id', 'title', 'poem', 'poet', 'poet_cn', 'poet_id', 'upload_user_id', 'translator', 'translator_id', 'is_owner_uploaded', 'created_at'
            ])->where('campaign_id', $campaignID)->get();

        return view('campaign.poems', [
            'poems' => $poems
        ]);
    }
}
