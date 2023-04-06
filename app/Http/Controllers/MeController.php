<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Repositories\GenreRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\PoemRepository;
use App\User;

class MeController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth');
        $this->poemRepository = $poemRepo;
    }

    public function index() {
        $lists = Collection::find(1)->with('poems')->get();

        $poems = $lists[0]->poems;

        $user         = auth()->user();
        $activityLogs = $user->poemActivityLogs->take(81);

        return view('me.index', [
            'poems'        => $poems,
            'user'         => $user,
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Show user's contribution.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function contributions() {
        /** @var \App\User $user */
        $user = auth()->user();

        /** @var \Illuminate\Support\Collection $activityLogs */
        $activityLogs = $user->poemActivityLogs->take(81);

        return view('me.contribution', [
            'user'         => $user,
            'activityLogs' => $activityLogs,
            // 'poem'          => $poems,
            'languageList'  => LanguageRepository::allInUse()->keyBy('id'),
            'genreList'     => GenreRepository::allInUse()->keyBy('id'),
            'randomPoemUrl' => '/'
        ]);
    }
}