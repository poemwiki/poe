<?php
namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Poem\IndexPoem;
use App\Http\Requests\Admin\Poem\StorePoem;
use App\Http\Requests\Admin\Poem\UpdatePoem;
use App\Models\Language;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use Auth;
use Brackets\AdminListing\Facades\AdminListing;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class PoemController extends Controller
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
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        if (empty($poem)) {
            return redirect(404);
        }

        $randomPoem = $this->poemRepository->random()->first();

        return view('poems.show')->with([
            'poem' => $poem,
            'randomPoemUrl' => $randomPoem->getUrl(),
            'fakeId' => $fakeId
        ]);
    }

    public function random() {
        $randomPoems = $this->poemRepository->random()->get();
        return redirect($randomPoems[0]->getUrl());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create() {
        $user = Auth::user();
        $this->authorize('web.poems.create');

        if($t = request()->get('translated_fake_id')) {
            $translatedPoem = $this->poemRepository->getPoemFromFakeId($t);
        }
        if($o = request()->get('original_fake_id')) {
            $originalPoem = $this->poemRepository->getPoemFromFakeId($o);
        }


        return view('poems.create', [
            'userName' => $user->name,
            'languageList' => Language::all(),
            'translatedPoem' => $translatedPoem ?? null,
            'originalPoem' => $originalPoem ?? null
        ]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param StorePoem $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StorePoem $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Poem
        $poem = Poem::create($sanitized);

        if ($request->ajax()) {
            return [
                'code' => 0,
                'redirect' => route('poems/edit', Poem::fakeId($poem->id)),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('poems/edit', Poem::fakeId($poem->getFakeId()));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $fakeId
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit($fakeId) {
        $user = Auth::user();
        $this->authorize('web.poems.edit', $user);
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        if (empty($poem)) {
            return redirect(404);
        }

//        $l = $this->poemRepository->find(2749);
//        dd($l->getUrl());
        return view('poems.edit', [
            'poem' => $poem,
            'userName' => $user->name,
            'languageList' => Language::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePoem $request
     * @param Poem $poem
     * @return array|RedirectResponse|Redirector
     */
    public function update($fakeId, UpdatePoem $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        if (empty($poem)) {
            return redirect(404);
        }
        // Update changed values Poem
        $this->poemRepository->update($sanitized, $poem->id);

        if ($request->ajax()) {
            return [
                'code' => 0,
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('poems/edit', Poem::fakeId($request->get('id')));
    }

}
