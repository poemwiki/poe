<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Models\Review;
use App\Models\Score;
use App\Repositories\ScoreRepository;

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

    public function store(CreateReviewRequest $request) {
        $sanitized = $request->getSanitized();

        if(Review::create($sanitized)) {
            return $this->responseSuccess();
        }
        return $this->responseFail();
    }
}
