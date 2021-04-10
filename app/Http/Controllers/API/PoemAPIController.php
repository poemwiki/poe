<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePoemRequest;
use App\Models\Poem;
use App\Models\Tag;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use EasyWeChat\Factory;
use File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class PoemAPIController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;
    /** @var  ReviewRepository */
    private $reviewRepository;

    public function __construct(PoemRepository $poemRepository, ReviewRepository $reviewRepository) {
        $this->poemRepository = $poemRepository;
        $this->reviewRepository = $reviewRepository;
    }

    // TODO return poem.id only if client don't need
    public function campaignIndex(Request $request) {
        $tagId = $request->input('tagId');

        if(!is_numeric($tagId)) {
            return $this->responseFail();
        }
        $campaign = Tag::find($tagId)->campaign;

        $dateInterval = Carbon::parse($campaign->end)->diff(now());
        $campaignEnded = $dateInterval->invert === 0;

        if(isset($campaign->settings['result'])) {
            $byScoreData = $campaign->settings['result'];
        } else {

            // poem before endDate, scores before endDate
            $byScore = $this->poemRepository->getByTagId($tagId, 'score', $campaign->start, $campaign->end);
            $limit = $campaign->settings['rank_min_weight'] ?? 3;
            $byScoreData = $byScore->filter(function ($value) use ($limit) {
                // 票数不足的不参与排名
                // dump($value['score_count']);
                return $value['score_count'] >= $limit;
            })->map(function (Poem $item) use ($campaign) {
                $item['score'] = $item->getCampaignScore($campaign)['score'];
                return $item;
            })->sort(function ($a, $b) {
                $score = $b['score'] <=> $a['score'];
                return $score === 0 ? $b['score_count'] <=> $a['score_count'] : $score;
            })->values()->map(function ($item, $index) {
                $item = $item->toArray();
                $item['rank'] = $index + 1;
                return $item;
            });

            if($campaignEnded) {
                $newSetting = $campaign->settings;
                $newSetting['result'] = $byScoreData;
                $campaign->settings = $newSetting;
                $campaign->save();
            }
        }


        $data = [
            'byScore' => $byScoreData,
            'byCreatedAt' => $this->poemRepository->getByTagId($tagId, 'created_at')
        ];
        return $this->responseSuccess($data);
    }

    public function index(Request $request) {
        // TODO pagination
        // TODO order by score updated before campaign end time
        $tagId = $request->input('tagId');
        $orderBy = $request->input('orderBy', 'created_at');
        if(!is_numeric($tagId)) {
            return $this->responseFail();
        }

        $data = $this->poemRepository->getByTagId($tagId, $orderBy);
        if($orderBy === 'score') {
            $limit = Tag::find($tagId)->campaign->settings['rank_min_weight'] ?? 3;
            $data = $data->filter(function ($value) use ($limit) {
                // 票数不足的不参与排名
                return $value['score_count'] >= $limit;
            })->sort(function ($a, $b) {
                $score = $b['score'] <=> $a['score'];
                return $score === 0 ? $b['score_count'] <=> $a['score_count'] : $score;
            })->values()->map(function ($item, $index) {
                $item = $item->toArray();
                $item['rank'] = $index + 1;
                return $item;
            });
        }
        return $this->responseSuccess($data);
    }

    public function mine(Request $request) {
        return $this->responseSuccess($this->poemRepository->getByOwner($request->user()->id));
    }

    public function relatedAll(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id));
    }

    /**
     * get related poem which has related campaign
     * @param Request $request
     * @return array
     */
    public function related(Request $request) {
        return $this->responseSuccess($this->poemRepository->getRelated($request->user()->id, true));
    }

    public function detail($id) {
        /** @var Poem $item */
        $item = Poem::find($id);
        $res = $item->toArray();

        $res['poet'] = $item->poetLabel;
        $res['poet_image'] = $item->uploader->avatarUrl;
        $res['date_ago'] = Carbon::parse($res['created_at'])->diffForHumans(now());
        // TODO save score_count to poem.score_count column
        $res['score_count'] = ScoreRepository::calcCount($id);

        $res['reviews'] = $this->reviewRepository->listByOriginalPoem($item)->get()->map(function ($item) {
            $item['date_ago'] = Carbon::parse($item->created_at)->diffForHumans(now());
            return $item;
        });

        $res['related'] = $this->poemRepository->random(2)
            ->where('id', '<>', $id)
            ->whereHas('tags', function ($query) use ($item) {
                $query->where('tag_id', '=', $item->tags[0]->id);
            })->get()->toArray();

        return $this->responseSuccess($res);
    }

    public function store(CreatePoemRequest $request) {
        $sanitized = $request->getSanitized();

        $wechatApp = Factory::miniProgram([
            'app_id' => env('WECHAT_MINI_PROGRAM_APPID'),
            'secret' => env('WECHAT_MINI_PROGRAM_SECRET'),
            'response_type' => 'object',
        ]);
        $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['poem']);
        if($result->errcode) {
            return $this->responseFail([], '请检查是否含有敏感词', -2);
        }

        $poem = Poem::create($sanitized);
        $poem->tags()->save(Tag::find($sanitized['tag_id']));

        return $this->responseSuccess();
    }

    public function delete($poemId) {
        try {
            $this->authorize('api.poem.delete', Poem::find($poemId));
        } catch (AuthorizationException $e) {
            return $this->responseFail();
        }

        $poem = Poem::find($poemId);
        $poem->delete();

        return $this->responseSuccess();
    }

    public function share($poemId) {
        $poem = Poem::find($poemId);

        $postData = ['compositionId' => 'pure', 'poem' => $poem->poem, 'poet' => $poem->poetLabel, 'title' => $poem->title];
        $dir = storage_path('app/public/poem-card/' . $poem->id);
        // TODO file name should be compositionId . hash(postData)
        $storeDir = $dir .'/element-0.png';

        if(isset($_GET['force']) or env('APP_ENV') === 'local') {
            $postData['force'] = 1;
        } else if(file_exists($storeDir) && File::mimeType($storeDir) !== 'text/plain') {
            // TODO save route('poem-card', $poemId) to $poem->image if $storeDir exists,
            // if not then generate the image before saving
            return $this->responseSuccess(['url' => route('poem-card', $poemId)]);
        }

        $img = file_get_contents_post(config('app.render_server'), $postData);
        // TODO 绘制小程序码

        if(!is_dir($dir)) {
            mkdir($dir);
        }
        if(file_put_contents($storeDir, $img)) {
            return $this->responseSuccess(['url' => route('poem-card', $poemId)]);
        }

        return $this->responseFail();
    }
}
