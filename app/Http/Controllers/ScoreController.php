<?php
namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Poem\IndexPoem;
use App\Http\Requests\Admin\Poem\StorePoem;
use App\Http\Requests\Admin\Poem\UpdatePoem;
use App\Models\ActivityLog;
use App\Models\Language;
use App\Models\Poem;
use App\Models\Score;
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
use Spatie\Activitylog\Models\Activity;


class ScoreController extends AppBaseController
{
    /** @var  PoemRepository */
    private $poemRepository;

    public function __construct(ScoreRepository $scoreRepo) {
        $this->middleware('auth')->except(['show', 'showContributions']);
    }

    /**
     * Display the specified Poem.
     *
     * @param Int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show(Int $id){
        $poem = Score::findOrFail($id);
        $logs = ActivityLog::findByPoem($poem);

        return view('score.show')->with([
            'poem' => $poem,
            'fakeId' => $poem->fake_id,
            'logs' => $logs
        ]);
    }

    public function showContributions($fakeId) {
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        $logs = ActivityLog::findByPoem($poem);

        return view('poems.contribution')->with([
            'poem' => $poem,
            'randomPoemUrl' => '/',
            'logs' => $logs
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function create() {
        $user = Auth::user();

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

        if(isset($sanitized['translated_id'])) {
            $translatedPoem = Poem::find($sanitized['translated_id']);
            if($translatedPoem) {
                $translatedPoem->original_id = $poem->id;
                $translatedPoem->save();
            }
        }

        if ($request->ajax()) {
            return [
                'code' => 0,
                'redirect' => route('poems/edit', Poem::getFakeId($poem->id)),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('poems/edit', $poem->fake_id);
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

        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);

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

        // Update changed values Poem
        $id = Poem::getIdFromFakeId($fakeId);
        $this->poemRepository->update($sanitized, $id);

        if ($request->ajax()) {
            return [
                'code' => 0,
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('poems/edit', Poem::getFakeId($id));
    }

}