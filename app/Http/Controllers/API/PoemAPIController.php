<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\PoemRepository;
use App\Repositories\ScoreRepository;
use Illuminate\Http\Request;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class PoemAPIController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;
    /** @var  ScoreRepository */
    private $scoreRepository;

    public function __construct(PoemRepository $poemRepository, ScoreRepository $scoreRepository) {
        $this->poemRepository = $poemRepository;
        $this->scoreRepository = $scoreRepository;
    }

    public function index(Request $request) {
        // todo pagination
        if(is_numeric($request->input('tagId'))) {
            return $this->responseSuccess($this->poemRepository->getByTagId($request->input('tagId'))->toArray());
        }
        // $items = $this->poemRepository->allInUse();
        //
        // return $this->responseSuccess($items->toArray());
    }

    public function show($id) {
        /** @var Poem $item */
        $item = $this->poemRepository->find($id);

        if (empty($item)) {
            return $this->sendError('Language not found');
        }

        return $this->responseSuccess($item->toArray());
    }
}
