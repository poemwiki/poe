<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Repositories\PoemRepository;

class ListController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth');
        $this->poemRepository = $poemRepo;
    }

    public function index() {
        $lists = Collection::find(1)->with('poems')->get();

        $poems = $lists[0]->poems;

        $user = auth()->user();

        return view('list.index', compact('poems', 'user'));
    }
}