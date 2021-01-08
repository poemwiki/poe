<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */
class PoemAPIController extends Controller {
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepository) {
        $this->poemRepository = $poemRepository;
    }

    public function index(Request $request) {
        // todo pagination
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
