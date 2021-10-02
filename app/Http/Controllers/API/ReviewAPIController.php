<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Models\Review;
use App\Repositories\ScoreRepository;
use EasyWeChat\Factory;

/**
 * Class ReviewAPIController.
 */
class ReviewAPIController extends Controller {
    /** @var ScoreRepository */
    private $repository;

    public function __construct(ScoreRepository $itemRepository) {
        $this->repository = $itemRepository;
    }

    public function like($action, $id) {
        $review   = Review::find($id);
        $user     = request()->user();
        $hasLiked = $user->hasLiked($review);

        if ($action === 'like' && !$hasLiked) {
            $user->like($review);

            $review->timestamps = false;
            $review->like       = $review->likers()->count();
            $review->save();
        } elseif ($action === 'unlike' && $hasLiked) {
            $user->unlike($review);

            $review->timestamps = false;
            $review->like       = $review->likers()->count();
            $review->save();
        }

        return $this->responseSuccess(['like' => $review->like]);
    }

    public function store(CreateReviewRequest $request) {
        $sanitized = $request->getSanitized();

        if (config('app.env') === 'production') {
            $wechatApp = Factory::miniProgram([
                'app_id'        => config('wechat.mini_program.default.app_id'),
                'secret'        => config('wechat.mini_program.default.secret'),
                'response_type' => 'object',
            ]);
            $result = $wechatApp->content_security->checkText($sanitized['title'] . $sanitized['content']);

            if ($result->errcode) {
                return $this->responseFail([], '请检查是否含有敏感词', Controller::$CODE['content_security_failed']);
            }
        }

        if (Review::create($sanitized)) {
            return $this->responseSuccess();
        }

        return $this->responseFail();
    }

    public function delete($id) {
        // TODO
    }
}
