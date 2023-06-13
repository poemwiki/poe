<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Repositories\TagRepository;
use Illuminate\Http\Request;

class TagAPIController extends Controller {
    /** @var  TagRepository */
    private $tagRepository;

    public function __construct(TagRepository $tagRepository) {
        $this->tagRepository = $tagRepository;
    }

    public function index(Request $request) {
        $tags = $this->tagRepository->findByCategoryId($request->input('tag_id'));

        return $this->responseSuccess($tags->toArray());
    }

    public function show($id) {
        /** @var Tag $tag */
        $tag = $this->tagRepository->find($id);

        if (empty($tag)) {
            return $this->sendError('Language not found');
        }

        return $this->responseSuccess($tag->toArray());
    }
}
