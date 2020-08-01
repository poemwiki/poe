<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePoemRequest;
use App\Http\Requests\UpdatePoemRequest;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Response;
use Flash;

class PostController extends AppBaseController
{
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(PoemRepository $poemRepo) {
        $this->poemRepository = $poemRepo;
    }

    /**
     * Display the specified Poem.
     *
     * @param int $fakeId
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $poem = $this->poemRepository->find(Poem::getIdFromFakeId($fakeId));
        $randomPoem = $this->poemRepository->random()->first();
//        dd($randomPoem);

        if (empty($poem)) {
            Flash::error('Poem not found');

            return redirect(route(''));
        }

        return view('posts.show')->with([
            'poem' => $poem,
            'randomPoemUrl' => $randomPoem->getUrl(),
            'fakeId' => $fakeId
        ]);
    }

    public function edit() {

    }
}
