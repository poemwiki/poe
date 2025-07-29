<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\Poem\StorePoem;
use App\Http\Requests\Admin\Poem\UpdatePoem;
use App\Models\Author;
use App\Models\Genre;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\GenreRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\PoemRepository;
use App\Rules\NoDuplicatedPoem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PoemController extends Controller {
    /** @var PoemRepository */
    private $poemRepository;
    /** @var AuthorRepository */
    private $authorRepository;

    public function __construct(PoemRepository $poemRepo, AuthorRepository $authorRepository) {
        $this->middleware('auth')->except(['show', 'showPoem', 'random', 'showContributions', 'compare']);
        $this->poemRepository   = $poemRepo;
        $this->authorRepository = $authorRepository;
    }

    private function _getRandomPoem() {
        $randomPoem = $this->poemRepository->randomOne();

        return $randomPoem;
    }

    /**
     * Display the specified Poem.
     *
     * @param string $fakeId
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $randomPoem = $this->_getRandomPoem();
        
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId, [
            'id', 'title', 'poem', 'is_original', 'original_id', 'poet', 'poet_cn', 
            'year', 'month', 'date', 'preface', 'subtitle', 'from', 'genre_id', 'poet_id', 'translator_id', 'location', 'poet_wikidata_id', 'translator_wikidata_id', 'is_owner_uploaded', 'upload_user_id', 'weapp_url'
        ]);
        if ($poem->mergedToPoem) {
            return redirect($poem->mergedToPoem->url);
        }

        $logs = $poem->activityLogs;

        return view('poems.show')->with([
            'poem'                => $poem,
            'randomPoemUrl'       => $randomPoem->url,
            'randomPoemTitle'     => $randomPoem->title,
            'randomPoemFirstLine' => $randomPoem->firstLine,
            'logs'                => $logs
        ]);
    }

    public function showContributions($fakeId) {
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId);

        return view('poems.contribution')->with([
            'poem'          => $poem,
            'languageList'  => LanguageRepository::allInUse()->keyBy('id'),
            'genreList'     => GenreRepository::allInUse()->keyBy('id'),
            'randomPoemUrl' => '/'
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
        $preset = null;
        $poem   = new Poem();
        $mode   = 'create new';
        if ($t = request()->get('translated_fake_id')) {
            $translatedPoem = $this->poemRepository->getPoemFromFakeId($t);
            $preset         = $translatedPoem;
            $mode           = 'create original';
            // TODO should use fake id $t here
            $poem['#translated_id'] = $translatedPoem->id;
            $poem->is_original      = 1;
        }
        if ($o = request()->get('original_fake_id')) {
            $originalPoem      = $this->poemRepository->getPoemFromFakeId($o);
            $preset            = $originalPoem;
            $mode              = 'create translated';
            $poem->original_id = $originalPoem->id;
            $poem->is_original = 0;
        }
        if ($a = request()->get('author_fake_id')) {
            $author          = Author::findOrFail(Author::getIdFromFakeId($a));
            $mode            = 'create poem by author';
            $preset          = new Poem();
            $preset->poet_id = $author->id;
            $preset->poet    = $author->label;
            $preset->poet_cn = $author->label_cn;
        }
        if ($presetTranslatorFakeID = request()->get('translator_fake_id')) {
            $translator                     = Author::findOrFail(Author::getIdFromFakeId($presetTranslatorFakeID));
            $mode                           = 'create poem by translator';
            $poem->is_original              = 0;
            $poem->translator_ids           = [$translator->id];
            $poem->translator               = $translator->label;
            $poem['#translators_label_arr'] = $poem->translatorsLabelArr;
        }

        if ($preset) {
            $poem->poet_id                = $preset->poet_id;
            $poem->poet_wikidata_id       = $preset->poet_wikidata_id;
            $poem->poet                   = $preset->poet;
            $poem->poet_cn                = $preset->poet_cn;
            $poem->genre_id               = $preset->genre_id;
            $poem->bedtime_post_id        = $preset->bedtime_post_id;
            $poem->bedtime_post_title     = $preset->bedtime_post_title;
            $poem->year                   = $preset->year;
            $poem->month                  = $preset->month;
            $poem->date                   = $preset->date;
            $poem->translator_id          = null;
            $poem->translator_wikidata_id = null;
            $poem->language_id            = null;
            // TODO 前端可编辑 original_id 而非 original_link，这样输入翻译自链接时，自动转换为作者+标题的链接，
            // 此处给出翻译自的 id 作为表单默认 original_id 即可
        }
        $poem['#user_name'] = Auth::user()->name;
        $poem['poem']       = "\n\n\n\n\n\n";

        $deftaultAuthors = ($preset && $preset->poetLabel)
            ? AuthorRepository::searchLabel($preset->poetLabel, [$preset->poet_id])
            : [];
        $defaultTranslators = $mode === 'create poem by translator'
            ? AuthorRepository::searchLabel($poem->translatorsStr, $poem->translator_ids)
            : [];

        return view('poems.create', [
            'poem'               => $poem,
            'trans'              => $this->trans(),
            'languageList'       => LanguageRepository::allInUse(),
            'genreList'          => Genre::select('name_lang', 'id')->get(),
            'translatedPoem'     => $translatedPoem ?? null, // TODO don't pass translatedPoem
            'originalPoem'       => $originalPoem ?? null, // TODO don't pass originalPoem
            'defaultAuthors'     => $deftaultAuthors, //Author::select('name_lang', 'id')->limit(10)->get()->toArray(),
            'defaultTranslators' => $defaultTranslators,
            'mode'               => $mode
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

        // if wikidata_id valid and not null, create a author by wikidata_id
        if (isset($sanitized['poet_wikidata_id']) && is_numeric($sanitized['poet_wikidata_id']) && is_null($sanitized['poet_id'])) {
            $poetAuthor           = $this->authorRepository->getExistedAuthor($sanitized['poet_wikidata_id']);
            $sanitized['poet_id'] = $poetAuthor->id;
            $sanitized['poet']    = $poetAuthor->label;
            $sanitized['poet_cn'] = $poetAuthor->label_cn;
        }

        $sanitized['upload_user_id'] = $request->user()->id;

        // Store the Poem
        $poem = Poem::create($sanitized);
        if ($sanitized['translator_ids']) {
            $poem->relateToTranslators($sanitized['translator_ids']);
        }

        if (isset($sanitized['#translated_id'])) {
            $translatedPoem = Poem::find($sanitized['#translated_id']);
            if ($translatedPoem) {
                $translatedPoem->original_id = $poem->id;
                $translatedPoem->save();
            }
        }

        // return $this->responseSuccess();
        return $this->responseSuccess(route('p/show', Poem::getFakeId($poem->id)));
    }

    public function trans() {
        $langs = LanguageRepository::allInUse();

        $locale = $langs->filter(function ($item) {
            return in_array($item->locale, config('translatable.locales'));
        })->pluck('name_lang', 'locale');

        return [
            'Save'    => trans('Save'),
            'Submit'  => trans('Submit'),
            'Publish' => trans('Publish'),
            'Saving'  => trans('Saving'),
            'locales' => $locale
        ];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string                 $fakeId
     * @throws AuthorizationException
     * @return Factory|View
     */
    public function edit($fakeId) {
        $poem = Poem::find(Poem::getIdFromFakeId($fakeId));
        $this->authorizeForUser(Auth::user(), 'web.poem.change', [$poem]);
        $poem = $this->poemRepository->getPoemFromFakeId($fakeId, [
            'id', 'title', 'language_id', 'is_original', 'original_id', 'poet', 'poet_cn', 'bedtime_post_id', 'bedtime_post_title',
            'poem', 'translator', 'from', 'year', 'month', 'date', 'preface', 'subtitle', 'genre_id',
            'poet_id', 'translator_id', 'location', 'poet_wikidata_id', 'translator_wikidata_id', 'is_owner_uploaded', 'upload_user_id'
        ]);

        $poem['original_link'] = $poem->originalLink;

        $poem['#user_name']             = Auth::user()->name;
        $poem['#translators_label_arr'] = empty($poem->translatorsLabelArr) ? $this->_splitTranslatorStr($poem->translator) : $poem->translatorsLabelArr;

        $translatorIds = [];
        if (empty($poem->translatorsLabelArr) && $poem->translator_id) {
            $translatorIds = [$poem->translator_id];
        } else {
            foreach ($poem->translatorsLabelArr as $translator) {
                if (isset($translator['id'])) {
                    $translatorIds[] = $translator['id'];
                }
            }
        }

        return view('poems.edit', [
            'poem'               => $poem,
            'trans'              => $this->trans(),
            'languageList'       => LanguageRepository::allInUse(),
            'genreList'          => Genre::select('name_lang', 'id')->get(),
            'defaultAuthors'     => $poem->poetLabel ? AuthorRepository::searchLabel($poem->poetLabel, [$poem->poet_id]) : [],
            'defaultTranslators' => $poem->translatorLabel ? AuthorRepository::searchLabel($poem->translatorLabel, $translatorIds) : [],
        ]);
    }

    private function _splitTranslatorStr($str) {
        $arr = explode(',', $str);
        $res = [];
        foreach ($arr as $translator) {
            $res[] = ['name' => trim($translator)];
        }

        return $res;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePoem $request
     * @param Poem       $poem
     * @return array
     */
    public function update($fakeId, UpdatePoem $request) {
        $id   = intval(Poem::getIdFromFakeId($fakeId));
        $poem = Poem::find($id);
        // Sanitize input
        $sanitized = $request->getSanitized();
        // manually validate if poem is duplicated
        $request->validate([
            'poem' => new NoDuplicatedPoem($id),
        ]);

        if (isset($sanitized['original_link'])) {
            $pattern = '@^' . str_replace('.', '\.', config('app.url')) . '/p/(.*)$@';
            $fakeId  = Str::of($sanitized['original_link'])->match($pattern)->__toString();

            $orginalPoem              = Poem::find(Poem::getIdFromFakeId($fakeId));
            $sanitized['original_id'] = $orginalPoem->id;
        }

        // if wikidata_id valid and not null, create a author by wikidata_id
        if (is_numeric($sanitized['poet_wikidata_id']) && is_null($sanitized['poet_id'])) {
            $poetAuthor           = $this->authorRepository->getExistedAuthor($sanitized['poet_wikidata_id']);
            $sanitized['poet_id'] = $poetAuthor->id;
            $sanitized['poet']    = $poetAuthor->label;
            $sanitized['poet_cn'] = $poetAuthor->label_cn;
        }
        // TODO translator_wikidata_id and translator_id is deprecated

        if ($sanitized['translator_ids']) {
            if ($poem->translators->count()) {
                $translatorsArr = collect($poem->translatorsLabelArr)->map(function ($label) {
                    return isset($label['id']) ? $label['id'] : $label['name'];
                })->toArray();

                // check if translators are not changed
                if ($translatorsArr !== $sanitized['translator_ids']) {
                    // TODO update relatable records instead of delete and insert
                    // TODO add order property for "translator is" relation
                    $poem->relatedTranslators()->delete();
                    $poem->relateToTranslators($sanitized['translator_ids']);
                }
            } else {
                $poem->relateToTranslators($sanitized['translator_ids']);
            }
        }

        $topOriginalPoem = $poem->topOriginalPoem;

        // TODO test this
        // if ($topOriginalPoem->is_owner_uploaded === Poem::$OWNER['uploader']) {
        //     $poem->poet    = $topOriginalPoem->poet;
        //     $poem->poet_id = $topOriginalPoem->poet_id;
        // }

        // Update changed values Poem
        $poem   = $this->poemRepository->update($sanitized, $id);
        $poetId = $topOriginalPoem->poet_id ?: $poem->poet_id;
        // 修改原作作者时，同步修改所有译作作者(仅当顶层原作 poet_id 或当前作品 poet_id 不为空，且 is_owner_uploaded=0)
        if ($poetId && $topOriginalPoem->is_owner_uploaded === Poem::$OWNER['none']) {
            $this->poemRepository->updateAllTranslatedPoemPoetId($topOriginalPoem, $poetId);
        }

        return $this->responseSuccess(route('p/show', Poem::getFakeId($poem->id)));
    }

    public function compare($ids) {
        $idArr = array_map(function ($id) {
            return intval($id);
        }, explode(',', $ids));
        $idCount = count($idArr);
        if (!Auth::check() && $idCount > config('app.max_compare_poem_count_guest')) {
            return redirect(route('login', ['ref' => route('compare', $ids)]));
        }
        if ($idCount > config('app.max_compare_poem_count')) {
            abort(400, 'compare only support 4 poems');
        }

        // Get poem in for loop and union them all
        // to keep the same order as $ids
        $query = Poem::where('id', $idArr[0]);
        for ($i = 1; $i < $idCount; ++$i) {
            $query->union(Poem::where('id', $idArr[$i]));
        }
        $poems = $query->get();

        $maxLineCount = $poems->max(function (Poem $poem) {
            return Str::of($poem->poem)->toLines()->count();
        });

        $poemsLines = $poems->map(function (Poem $poem) {
            return Str::of($poem->poem)->toLines();
        });

        $compareLines = [];
        for ($i = 0; $i < $maxLineCount; ++$i) {
            $compareLine = [];
            $compareLine = $poemsLines->map(function ($lines) use ($i) {
                return $lines[$i] ?? null;
            });
            $compareLines[] = $compareLine;
        }

        $randomPoem = $this->poemRepository->randomOne();

        return view('poems.compare', [
            'poems'          => $poems,
            'compareLines'   => $compareLines,
            'ids'            => $ids,
            'idArr'          => $idArr,
            'url'            => route('compare', $ids),
            // todo add translators to authors string
            'authors' => implode(',', $poems->map(function (Poem $poem) {
                return $poem->poetLabel;
            })->toArray()),
            'randomPoemUrl'       => $randomPoem->url,
            'randomPoemTitle'     => $randomPoem->title,
            'randomPoemFirstLine' => $randomPoem->firstLine,
        ]);
    }
}
