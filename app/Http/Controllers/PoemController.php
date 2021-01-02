<?php
namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Poem\IndexPoem;
use App\Http\Requests\Admin\Poem\StorePoem;
use App\Http\Requests\Admin\Poem\UpdatePoem;
use App\Models\ActivityLog;
use App\Models\Author;
use App\Models\Genre;
use App\Models\Poem;
use App\Models\Wikidata;
use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\PoemRepository;
use Auth;
use Brackets\AdminListing\Facades\AdminListing;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;


class PoemController extends Controller
{
    /** @var  PoemRepository */
    private $poemRepository;
    /** @var  AuthorRepository */
    private $authorRepository;

    public function __construct(PoemRepository $poemRepo, AuthorRepository $authorRepository) {
        $this->middleware('auth')->except(['show', 'showPoem', 'random', 'showContributions']);
        $this->poemRepository = $poemRepo;
        $this->authorRepository = $authorRepository;
    }

    private function _poem(Poem $poem){
        $randomPoem = $this->poemRepository->randomOne();

        $logs = ActivityLog::findByPoem($poem);

        return view('poems.show')->with([
            'poem' => $poem,
            'randomPoemUrl' => $randomPoem->url,
            'randomPoemTitle' => $randomPoem->title,
            'randomPoemFirstLine' => Str::of($randomPoem->poem)->firstLine(),
            'fakeId' => $poem->fake_id,
            'logs' => $logs
        ]);
    }
    /**
     * Display the specified Poem.
     *
     * @param String $fakeId
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);
        return $this->_poem($poem);
    }

    // route('poem');
    public function showPoem($id){
        $poem = Poem::findOrFail($id);
        return $this->_poem($poem);
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

    public function random() {
        $randomPoems = $this->poemRepository->randomOne();
        return redirect($randomPoems->url);
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
            'trans' => $this->trans(),
            'languageList' => LanguageRepository::allInUse(),
            'genreList' => Genre::select('name_lang', 'id')->get(),
            'translatedPoem' => $translatedPoem ?? null,
            'originalPoem' => $originalPoem ?? null,
            'defaultAuthors' => Author::select('name_lang', 'id')->limit(10)->get()->toArray(),
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

        return $this->responseSuccess(route('poems/edit', Poem::getFakeId($poem->id)));
    }


    public function trans() {
        $langs = LanguageRepository::allInUse();

        $locale = $langs->filter(function ($item) {
            return in_array($item->locale, config('translatable.locales'));
        })->pluck('name_lang', 'locale');
        return [
            'Save' => trans('Save'),
            'Saving' => trans('Saving'),
            'locales' => $locale
        ];
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

        $poem = $this->poemRepository->getPoemFromFakeId($fakeId, [
            'id', 'title', 'language_id', 'is_original', 'original_id', 'poet', 'poet_cn', 'bedtime_post_id', 'bedtime_post_title',
            'poem', 'translator', 'from', 'year', 'month', 'date', 'dynasty', 'nation', 'preface', 'subtitle', 'genre_id',
            'poet_id', 'translator_id', 'location', 'poet_wikidata_id', 'translator_wikidata_id'
        ]);

        return view('poems.edit', [
            'poem' => $poem,
            'userName' => $user->name,
            'trans' => $this->trans(),
            'languageList' => LanguageRepository::allInUse(),
            'genreList' => Genre::select('name_lang', 'id')->get(),
            'defaultAuthors' => Author::select('name_lang', 'id')->whereIn('id', [$poem->poet_id, $poem->translator_id])
                ->union(Author::select('name_lang', 'id')->limit(10))->get()->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePoem $request
     * @param Poem $poem
     * @return array
     */
    public function update($fakeId, UpdatePoem $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // if wikidata_id valid and not null, create a author by wikidata_id
        if(is_numeric($sanitized['poet_wikidata_id']) && is_null($sanitized['poet_id'])) {
            $authorExisted = $this->getExistedAuthor($sanitized['poet_wikidata_id']);
            $sanitized['poet_id'] = $authorExisted->id;
        }
        if(is_numeric($sanitized['translator_wikidata_id']) && is_null($sanitized['translator_id'])) {
            $authorExisted = $this->getExistedAuthor($sanitized['translator_wikidata_id']);
            $sanitized['translator_id'] = $authorExisted->id;
        }

        // Update changed values Poem
        $id = Poem::getIdFromFakeId($fakeId);
        $this->poemRepository->update($sanitized, $id);

        return $this->responseSuccess();
    }

    /**
     * TODO move it to AuthorRepositoy
     * @param $poet_wikidata_id
     * @return Author
     */
    private function getExistedAuthor($poet_wikidata_id): Author {
        $authorExisted = Author::where('wikidata_id', '=', $poet_wikidata_id)->count();

        if (!$authorExisted) {
            $wiki = Wikidata::find($poet_wikidata_id);
            $authorExisted = $this->authorRepository->importFromWikidata($wiki);
            Artisan::call('alias:import', ['--id' => $poet_wikidata_id]);
        }
        return $authorExisted;
    }

}
