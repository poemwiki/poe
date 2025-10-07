<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateScoreRequest;
use App\Repositories\ScoreRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Class ScoreAPIController
 * @package App\Http\Controllers\API
 */
class ScoreAPIController extends Controller {
    /** @var ScoreRepository */
    private $repository;

    public function __construct(ScoreRepository $itemRepository) {
        $this->repository = $itemRepository;
    }

    public function store(CreateScoreRequest $request) {
        $sanitized = $request->getSanitized();
        // Upsert user score per poem; tolerate races by repository fallback
        $res = $this->repository->updateOrCreate(
            ['poem_id' => $sanitized['poem_id'], 'user_id' => $sanitized['user_id']],
            [
                'score'  => $sanitized['score'],
                'weight' => $sanitized['weight']
            ]
        );

        if($res) {
            return $this->responseSuccess(ScoreRepository::calc($sanitized['poem_id']));
        }

        return $this->responseFail();
    }

    public function mine() {
        $scores = $this->repository->listByUserId(Auth::user()->id)->get()
            ->keyBy('poem_id')->map(function ($item) {
                return $item['score'];
            });

        if($scores)
            return $this->responseSuccess($scores);

        return $this->responseFail();
    }
}
