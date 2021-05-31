<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Models\Review;
use App\Models\Score;
use App\Repositories\ScoreRepository;
use EasyWeChat\Factory;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class ReviewAPIController extends Controller {
    /** @var  ScoreRepository */
    private $repository;

    public function __construct(ScoreRepository $itemRepository) {
        $this->repository = $itemRepository;
    }

    public function like($action, $id) {
        $review = Review::find($id);
        if($action === 'like') {
            $review->like = $review->like+1;
        } else if($action === 'unlike') {
            $review->like = $review->like-1;
        }
        $review->save();
        return $this->responseSuccess(['like' => $review->like]);
    }

    public function store(CreateReviewRequest $request) {
        $sanitized = $request->getSanitized();

        $wechatApp = Factory::miniProgram([
            'app_id' => env('WECHAT_MINI_PROGRAM_APPID'),
            'secret' => env('WECHAT_MINI_PROGRAM_SECRET'),
            'response_type' => 'object',
        ]);
        $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['content']);

        if($result->errcode) {
            return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
        }

        if(Review::create($sanitized)) {
            return $this->responseSuccess();
        }
        return $this->responseFail();
    }
}
