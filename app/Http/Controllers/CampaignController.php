<?php


namespace App\Http\Controllers;


use App\Models\Campaign;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\PoemRepository;
use App\User;

class CampaignController extends Controller {
    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth')->except(['reward']);
        $this->poemRepository = $poemRepo;
    }

    public function reward(int $campaignId, string $fakeUID) {
        $uid = User::getIdFromFakeId($fakeUID);
        $user = User::find($uid);
        $campaign = Campaign::find($campaignId);
        if(!$user or !$campaign) {
            return view('campaign.reward', [
                'error' => '参数错误'
            ]);
        }


        $isParticipated = $user->originalPoemsOwned->filter(function (Poem $poem) use ($campaignId) {
            return $poem->campaign_id === $campaignId;
        })->count();

        $reward = '';
        if($isParticipated) {
            $reward = '喜马拉雅会员兑换码 aabbcc';
        }else {
            $error = ('请先在 #' . $campaign->name_lang . ' 活动页面发表你的原创作品，再来此页面领取奖励');
        }

        return view('campaign.reward', [
            'reward' => $reward,
            'error' => $error,
            'campaignId' => $campaignId
        ]);
    }
}