<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePoemRequest;
use App\Models\Campaign;
use App\Models\Poem;
use App\Models\Tag;
use App\Repositories\PoemRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ScoreRepository;
use Illuminate\Http\Request;

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

    public function index(Request $request) {
        // TODO pagination
        // TODO order by
        if(is_numeric($request->input('tagId'))) {
            return $this->responseSuccess($this->poemRepository->getByTagId($request->input('tagId'))->toArray());
        }
    }

    public function mine(Request $request) {
        return $this->responseSuccess($this->poemRepository->getByOwner($request->user()->id));
    }

    public function detail($id) {
        /** @var Poem $item */
        $item = Poem::find($id);
        $res = $item->toArray();
        $res['poet_image'] = $item->uploader->avatarUrl;
        $res['reviews'] = $this->reviewRepository->listByOriginalPoem($item, 100);

        return $this->responseSuccess($res);
    }

    public function store(CreatePoemRequest $request) {
        $this->responseSuccess([]);
        $sanitized = $request->getSanitized();

        $poem = Poem::create($sanitized);
        $poem->tags()->save(Tag::find($sanitized['tag_id']));

        return $this->responseSuccess();
    }
}
