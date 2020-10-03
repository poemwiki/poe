<?php
namespace App\Http\Controllers;

use App\Models\Poem;
use App\Repositories\PoemRepository;


class PoetController extends AppBaseController {
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->poemRepository = $poemRepo;
    }

    /**
     * Display the specified Poet
     *
     * @param int $fakeId
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($poetName) {
        $poems = Poem::where(['poet' => $poetName])->get();

        // get desc from wikidata
        $poetDesc = '';
        return view('poets.show')->with([
            'poetDesc' => $poetDesc,
            'poetName' => $poetName,
            'randomPoemUrl' => $this->poemRepository->randomOne()->url,
            'poems' => $poems
        ]);
    }

}
