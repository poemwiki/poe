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
        $tagId = $request->input('tagId');
        $orderBy = $request->input('orderBy', 'created_at');
        if(is_numeric($tagId)) {
            return $this->responseSuccess($this->poemRepository->getByTagId($tagId, $orderBy)->toArray());
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
        $res['related'] = $this->poemRepository->random(2)
            ->where('id', '<>', $id)
            ->whereHas('tags', function ($query) use ($item) {
                $query->where('tag_id', '=', $item->tags[0]->id);
            })->get()->toArray();

        return $this->responseSuccess($res);
    }

    public function store(CreatePoemRequest $request) {
        $sanitized = $request->getSanitized();

        $poem = Poem::create($sanitized);
        $poem->tags()->save(Tag::find($sanitized['tag_id']));

        return $this->responseSuccess();
    }
}
