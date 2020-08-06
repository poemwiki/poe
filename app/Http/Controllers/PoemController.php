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
        $randomPoems = $this->poemRepository->random(2)->get();

        return view('poems.show')->with([
            'poem' => $randomPoems->first(),
            'randomPoemUrl' => $randomPoems[1]->getUrl(),
            'fakeId' => $randomPoems->first()->getFakeId()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create() {
//        $this->authorize('admin.poem.create');

        return view('admin.poem.create');
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
            return ['redirect' => url('admin/poems'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/poems');
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
//        dd($fakeId, $request);die;
        // Sanitize input
        $sanitized = $request->getSanitized();
//        $sanitized['id'] = $request->get('id');

        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        if (empty($poem)) {
            return redirect(404);
        }
        // Update changed values Poem
        $this->poemRepository->update($sanitized, $poem->id);
//        dd($sanitized);die;

        if ($request->ajax()) {
            return [
                'redirect' => route('poems/edit', Poem::fakeId($request->get('id'))),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('poems/edit', Poem::fakeId($request->get('id')));
    }

}
