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
use Exception;
use File;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
        $item = Poem::findOrFail($id);
        $res = $item->toArray();

        $res['poet'] = $item->poetLabel;
        $res['poet_image'] = $item->uploader ? $item->uploader->avatarUrl : '';
        $res['date_ago'] = Carbon::parse($res['created_at'])->diffForHumans(now());
        // TODO save score_count to poem.score_count column
        $res['score_count'] = ScoreRepository::calcCount($id);

        $res['reviews'] = $this->reviewRepository->listByOriginalPoem($item)->get()->map(function ($item) {
            $item['date_ago'] = Carbon::parse($item->created_at)->diffForHumans(now());
            return $item;
        });

        $relatedQuery = $this->poemRepository->random(2)
            ->where('id', '<>', $id);
        if($item->tags->count()) {
            $relatedQuery->whereHas('tags', function ($query) use ($item) {
                $query->where('tag_id', '=', $item->tags[0]->id);
            });
        }

        $res['related'] = $relatedQuery->get()->toArray();

        return $this->responseSuccess($res);
    }

    public function store(CreatePoemRequest $request) {
        $sanitized = $request->getSanitized();

        $wechatApp = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret'),
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


    // TODO poster image generation process should be a command, and invoke after poem created
    // TODO regenerate poster when related poem fields updated
    public function share(int $poemId) {
        $poem = Poem::find($poemId);
        $force = isset($_GET['force']) || config('app.env') === 'local';

        $compositionId = 'pure';
        if(!$force && $poem->share_pics && isset($poem->share_pics[$compositionId])) {
            if(file_exists($poem->share_pics[$compositionId])) {
                return $this->responseSuccess(['url' => route('poem-card', [
                    'id' => $poemId,
                    'compositionId' => $compositionId
                ])]);
            }
        }

        $postData = ['compositionId' => $compositionId, 'poem' => $poem->poem, 'poet' => $poem->poetLabel, 'title' => $poem->title];

        $dir = storage_path('app/public/poem-card/' . $poem->id);
        if(!is_dir($dir)) {
            mkdir($dir);
        }

        // img file name will change if postData change
        $hash = crc32(json_encode($postData));
        $posterPath = "{$dir}/poster_{$compositionId}_{$hash}.png"; // posterImg = poemImg + appCodeImg
        $poemImgFileName = "poem_{$compositionId}_{$hash}.png"; // main part of poster

        $postData['force'] = $force;

        try {
            $poemImgPath = $this->fetchPoemImg($postData, $dir, $poemImgFileName, $force);
            $appCodeImgPath = $this->fetchAppCodeImg($poemId, $dir, $force);

            if(!$this->composite($poemImgPath, $appCodeImgPath, $posterPath)) {
                return $this->responseFail();
            }

            $sharePics = $poem->share_pics ?? [];
            $sharePics[$compositionId] = $posterPath;
            $poem->share_pics = $sharePics;
            $poem->save();
            return $this->responseSuccess(['url' => route('poem-card', [
                'id' => $poemId,
                'compositionId' => $compositionId
            ])]);

        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return $this->responseFail();
        }
    }

    /**
     * @param $postData
     * @param $dir
     * @param $poemImgFileName
     * @param bool $force
     * @return string
     * @throws Exception
     */
    private function fetchPoemImg($postData, string $dir, string $poemImgFileName, bool $force=false) {
        $poemImgPath = $dir .'/'. $poemImgFileName;
        if(!$force && file_exists($poemImgPath)) {
            return $poemImgPath;
        }

        $poemImg = file_get_contents_post(config('app.render_server'), $postData);
        if(file_put_contents($poemImgPath, $poemImg)) {
            if(File::mimeType($poemImgPath) == 'text/plain') {
                unlink($poemImgPath);
                throw new Exception('生成图片失败，请稍后再试。');
            }

            return $poemImgPath;
        }
        throw new Exception('图片写入失败，请稍后再试。');
    }

    /**
     * @param int $poemId
     * @param string $appCodeImgDir
     * @param bool $force
     * @param string $appCodeFileName
     * @return string|false
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    private function fetchAppCodeImg(int $poemId, string $appCodeImgDir, bool $force=false, string $appCodeFileName='app-code.jpg') {
        $app = Factory::miniProgram([
            'app_id' => config('wechat.mini_program.default.app_id'),
            'secret' => config('wechat.mini_program.default.secret')
        ]);
        $response = $app->app_code->getUnlimit($poemId, [
            'page' => 'pages/detail/detail',
            'width' => 280,
            // 'is_hyaline' => true
        ]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $response->saveAs($appCodeImgDir, $appCodeFileName);
            return $appCodeImgDir.'/'.$appCodeFileName;
        }
        return false;
    }

    /**
     * composite poem image and appCode image
     * @param string $poemImgPath
     * @param string $appCodeImgPath
     * @param string $posterPath
     * @param int $quality
     * @return bool
     * @throws Exception
     */
    private function composite(string $poemImgPath, string $appCodeImgPath, string $posterPath, int $quality=100): bool {
        // 绘制小程序码
        // 覆盖海报右下角小程序码区域
        $posterImg = img_overlay($poemImgPath, $appCodeImgPath, 0, 0, 120, 120);

        $imgType = exif_imagetype($poemImgPath);
        if ($imgType === IMAGETYPE_JPEG) {
            $res = imagejpeg($posterImg, $posterPath, $quality);

        } else if($imgType === IMAGETYPE_PNG){
            //see: http://stackoverflow.com/a/7878801/1596547
            $res = imagepng($posterImg, $posterPath, $quality*9/100);

        } else if($imgType === IMAGETYPE_GIF){
            $res = imagegif($posterImg, $posterPath);

        } else {
            throw new Exception('image type not supported');
        }

        imagedestroy($posterImg);
        return $res;
    }
}
