<?php
namespace App\Http\Controllers;

use App\Http\Requests\Admin\Author\StoreAuthor;
use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
use App\Models\Nation;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\DynastyRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\NationRepository;
use Illuminate\Routing\Redirector;


class AuthorController extends Controller {
    /** @var  AuthorRepository */
    private $authorRepository;

    public function __construct(AuthorRepository $poemRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->authorRepository = $poemRepo;
    }

    /**
     * Display the specified author
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $id = Author::getIdFromFakeId($fakeId);
        $author = Author::findOrFail($id);
        $poemsAsPoet = Poem::where(['poet_id' => $id])->get();
        $poemsAsTranslator = Poem::where(['translator_id' => $id])->get();

        $from = request()->get('from');
        $fromPoetName = '';
        if (is_numeric($from)) {
            $fromPoem = Poem::findOrFail($from);
            if($fromPoem->poet_cn) {
                $fromPoetName = $fromPoem->poet_cn;
                if ($fromPoem->poet_cn !== $fromPoem->poet) {
                    $fromPoetName .= $fromPoem->poet;
                }
            } else {
                $fromPoetName = $fromPoem->poet;
            }
        }


        return view('authors.show')->with([
            'poetDesc' => $author->describe_lang,
            'poetName' => $author->name_lang,
            'author' => $author,
            'poemsAsPoet' => $poemsAsPoet,
            'poemsAsTranslator' => $poemsAsTranslator,
            'fromPoetName' => $fromPoetName
        ]);
    }


    /**
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit($fakeId) {
        $id = Author::getIdFromFakeId($fakeId);
        $author = Author::select(['id', 'name_lang', 'describe_lang', 'dynasty_id', 'nation_id'])->findOrFail($id);

        // dd(Nation::where('id', $author->nation_id)->orWhere('id', '>', 0)->limit(10)->get()->toArray());
        return view('authors.edit', [
            'author' => $author,
            'trans' => $this->trans(),
            'nationList' => NationRepository::allInUse(),
            // 'defaultNation' => Nation::where('id', $author->nation_id)->get()->toArray(),
            'defaultNation' => Nation::where('id', $author->nation_id)->union(Nation::limit(10))->get()->toArray(),
            'dynastyList' => DynastyRepository::allInUse(),
        ]);
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
     * Update the specified resource in storage.
     *
     * @param UpdateAuthor $request
     * @return array
     */
    public function update($fakeId, UpdateAuthor $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        $id = Author::getIdFromFakeId($fakeId);
        $this->authorRepository->update($sanitized, $id);

        return $this->responseSuccess();
    }

    /**
     * Show the form for creating a new author.
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create() {
        return view('authors.create', [
            'trans' => $this->trans(),
            'dynastyList' => DynastyRepository::allInUse(),
            'defaultNation' => Nation::limit(10)->get()->toArray(),
        ]);
    }

    /**
     * Store a newly created author in storage.
     * @param StoreAuthor $request
     * @return array
     */
    public function store(StoreAuthor $request) {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Poem
        $author = Author::create($sanitized);

        return $this->responseSuccess(route('author/show', $author->fakeId));
    }
}
