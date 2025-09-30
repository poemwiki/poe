<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\Author\StoreAuthor;
use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
use App\Models\MediaFile;
use App\Models\Nation;
use App\Models\Poem;
use App\Models\Relatable;
use App\Models\Wikidata;
use App\Repositories\AuthorRepository;
use App\Repositories\DynastyRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\NationRepository;
use App\Repositories\PoemRepository;
use App\Services\Tx;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller {
    /** @var AuthorRepository */
    private $authorRepository;

    public function __construct(AuthorRepository $authorRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->authorRepository = $authorRepo;
    }

    /**
     * Display the specified author.
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId, Request $request) {
        $id = Author::getIdFromFakeId($fakeId);
        // Get author with aliases in a single query using JOIN
        $authorData = Author::leftJoin('alias', 'author.id', '=', 'alias.author_id')
            ->where('author.id', $id)
            ->select('author.*',
                     DB::raw('GROUP_CONCAT(DISTINCT alias.name) as alias_names'))
            ->groupBy('author.id')
            ->firstOrFail();

        $author = $authorData;

        // Process aliases from the joined data
        $aliasNames = $authorData->alias_names ?
            collect(explode(',', $authorData->alias_names))
                ->map(function($name) { return \Illuminate\Support\Str::trimSpaces($name); })
                ->unique()
                ->values() :
            collect();

        $sortType = $request->get('sort', 'hottest'); // 'hottest' or 'newest'

        $poemsAsPoet = Poem::where(['poet_id' => $id])->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('relatable')
                ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem'])
            ;
        })->get();

        PoemRepository::preloadTranslatorsForPoems($poemsAsPoet);

        $allOriginalPoems = $poemsAsPoet;
        if ($author->user) {
            $allOriginalPoems = $allOriginalPoems->concat($author->user->originalPoemsOwned);
        }

        $sortedOriginalPoems = PoemRepository::sortAuthorPoems($allOriginalPoems, $sortType);

        // TODO poem.translator_id should be deprecated
        $poemsAsTranslatorAuthor = Poem::where(['translator_id' => $id])->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('relatable')
                ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
        })->get();
        $poemsAsRelatedTranslator = $author->poemsAsTranslator()->get();
        $poemsAsTranslator        = $poemsAsTranslatorAuthor->concat($poemsAsRelatedTranslator)->unique('id');

        $sortedTranslationPoems = PoemRepository::sortAuthorPoems($poemsAsTranslator, $sortType);

        // Optimize poet data loading for translator poems (for fallback cases)
        $poetLabelMap = PoemRepository::getPoetLabelsForPoems($poemsAsTranslator);

        $lastOnlineAgo = '';
        if ($author->user) {
            $key        = 'online_' . $author->user->id;
            $lastOnline = Cache::get($key);

            if ($lastOnline) {
                $lastOnlineAgo = date_ago($lastOnline);
            }
        }

        return view('authors.show')->with([
            'author'            => $author,
            'alias'             => $aliasNames,
            'label'             => $author->label,
            'poemsAsPoet'       => $sortedOriginalPoems,
            'poemsAsTranslator' => $sortedTranslationPoems,
            'poetLabelMap'      => $poetLabelMap,
            'lastOnline'        => $lastOnlineAgo,
            'currentSort'       => $sortType
        ]);
    }

    /**
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit($fakeId) {
        $id     = Author::getIdFromFakeId($fakeId);
        $author = Author::select(['id', 'name_lang', 'describe_lang', 'dynasty_id', 'nation_id', 'avatar',
            'birth_year', 'birth_month', 'birth_day',
            'death_year', 'death_month', 'death_day'])->findOrFail($id);

        $author->name_lang     = $author->name_lang ?: $author->getTranslated('name_lang', 'zh-CN');
        $author->describe_lang = $author->describe_lang ?: $author->getTranslated('describe_lang', 'zh-CN');

        // dd(Nation::where('id', $author->nation_id)->orWhere('id', '>', 0)->limit(10)->get()->toArray());
        return view('authors.edit', [
            'author'     => $author,
            'trans'      => $this->trans(),
            'nationList' => NationRepository::allInUse(),
            // 'defaultNation' => Nation::where('id', $author->nation_id)->get()->toArray(),
            'defaultNation' => Nation::where('id', $author->nation_id)->union(Nation::limit(10))->get()->toArray(),
            'dynastyList'   => DynastyRepository::allInUse(),
        ]);
    }

    public function avatar(Request $request) {
        $file = $request->file('avatar');
        $ID   = $request->get('author', null);

        $author = Author::find($ID);
        if (!$author) {
            return $this->responseFail([], '未能找到该作者。');
        }

        if (!$file->isValid()) {
            logger()->error('user avatar upload Error: file invalid.');

            return $this->responseFail([], '图片上传失败。请稍后再试。');
        }

        $ext   = strtolower($file->getClientOriginalExtension());
        $allow = ['jpg', 'webp', 'png', 'jpeg', 'bmp']; // 支持的类型
        if (!in_array($ext, $allow)) {
            return $this->responseFail([], '不支持的图片类型，请上传 jpg/jpeg/png/webp/bmp 格式图片。', Controller::$CODE['img_format_invalid']);
        }

        $size = $file->getSize();
        if ($size > 5 * 1024 * 1024) {
            return $this->responseFail([], '上传的图片不能超过5M', Controller::$CODE['upload_img_size_limit']);
        }

        [$width, $height] = getimagesize($file);
        $corpSize         = min($width, $height, 600);

        $client     = new Tx();
        $format     = TX::SUPPORTED_FORMAT['webp'];
        $md5        = md5($file->getContent());
        $toFileName = config('app.avatar.author_path') . '/' . $author->fakeId . '.' . $format;
        $tmpFileID  = config('app.cos_tmp_path') . '/' . $md5;

        try {
            $result = $client->scropAndUpload($tmpFileID, $toFileName, $file->getContent(), $format, $corpSize, $corpSize);
            // logger()->info('scropAndUpload finished:', $result);
        } catch (\Exception $e) {
            logger()->error('scropAndUpload Error:' . $e->getMessage());

            return $this->responseFail([], '图片上传失败。请稍后再试。');
        }

        $avatarImage = $result['Data']['ProcessResults']['Object'][0];
        if (isset($avatarImage['Location'])) {
            $objectUrlWithoutSign = 'https://' . $avatarImage['Location'];

            // Tencent cos client has set default timezone to PRC
            date_default_timezone_set(config('app.timezone', 'UTC'));
            $author->avatar = $objectUrlWithoutSign . '?v=' . now()->timestamp;
            $author->save();

            $this->authorRepository->saveAuthorMediaFile($author, MediaFile::TYPE['avatar'], $avatarImage['Key'], $md5, $format, $avatarImage['Size']);

            $client->deleteObject($tmpFileID);

            return $this->responseSuccess(['avatar' => $objectUrlWithoutSign . '?v=' . now()->timestamp]);
        }

        return $this->responseFail([], '图片上传失败。请稍后再试。');
    }

    public function trans() {
        $langs = LanguageRepository::allInUse();

        $locale = $langs->filter(function ($item) {
            return in_array($item->locale, config('translatable.locales'));
        })->pluck('name_lang', 'locale');

        return [
            'Save'    => trans('Save'),
            'Saving'  => trans('Saving'),
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
            'trans'         => $this->trans(),
            'dynastyList'   => DynastyRepository::allInUse(),
            'defaultNation' => Nation::limit(10)->get()->toArray(),
        ]);
    }

    /**
     * Store a newly created author in storage.
     * @param int $wikidata_id
     * @return array
     */
    public function createFromWikidata(int $wikidata_id) {
        $wikidata = Wikidata::find($wikidata_id);
        if (!$wikidata) {
            throw new ModelNotFoundException('no such wikidata entry');
        }

        $author = $this->authorRepository->getExistedAuthor($wikidata_id);

        return $this->responseSuccess(route('author/show', $author->fakeId));
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

    /**
     * Show the form for editing author aliases
     * @param string $fakeId
     * @return \Illuminate\View\View
     */
    public function editAlias($fakeId) {
        $id     = Author::getIdFromFakeId($fakeId);
        $author = Author::findOrFail($id);

        // Simplified: only consider enabled (in-use) languages
        $allInUseLanguages = LanguageRepository::allInUse(['id', 'name_lang', 'name', 'locale', 'sort_order']);
        $inUseLocaleSet    = $allInUseLanguages->pluck('locale')->map(function ($l) { return strtolower($l); })->unique()->values();

        // build lowercase locale -> id map for O(1) lookups
        $localeToIdMap = $allInUseLanguages->pluck('id', 'locale')->mapWithKeys(function($v, $k) { return [strtolower($k) => $v]; })->toArray();

        // Fetch aliases only for in-use language ids
        $inUseIds = $allInUseLanguages->pluck('id')->toArray();
        $aliases  = \App\Models\Alias::where('author_id', $id)
            ->whereIn('language_id', $inUseIds)
            ->get()
            ->map(function ($a) {
                return (object)[
                    'name'        => $a->name,
                    'locale'      => $a->locale,
                    'author_id'   => $a->author_id,
                    'language_id' => $a->language_id,
                ];
            });

        // Merge name_lang entries but only for enabled locales and avoid duplicates
        $nameLangs = $author->getTranslations('name_lang') ?? [];
        // existSet maps key -> language_id for quick duplicate detection and fast id lookup
        $existSet = [];
        foreach ($aliases as $a) {
            $k            = strtolower($a->locale . '|' . trim($a->name));
            $existSet[$k] = true;
        }
        foreach ((array)$nameLangs as $locale => $name) {
            if (empty($name)) {
                continue;
            }
            $lc = strtolower($locale);
            if (! $inUseLocaleSet->contains($lc)) {
                continue;
            }

            $key = strtolower($locale . '|' . trim($name));
            if (isset($existSet[$key])) {
                continue;
            }

            $lid = $localeToIdMap[$lc] ?? null;
            $aliases->push((object)[
                'name'        => $name,
                'locale'      => $locale,
                'author_id'   => $id,
                'language_id' => $lid,
            ]);
            $existSet[$key] = true;
        }

        // Sort aliases by language sort_order
        $langSortMap = $allInUseLanguages->pluck('sort_order', 'id')->toArray();
        $aliases     = $aliases->sortBy(function ($a) use ($langSortMap) {
            return empty($a->language_id) ? PHP_INT_MAX : ($langSortMap[$a->language_id] ?? PHP_INT_MAX - 1);
        })->values();

        return view('authors.edit-alias', [
            'author'    => $author,
            'aliases'   => $aliases,
            'languages' => $allInUseLanguages
        ]);
    }

    /**
     * Update author aliases
     * @param string  $fakeId
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAlias($fakeId, Request $request) {
        $id         = Author::getIdFromFakeId($fakeId);
        $author     = Author::findOrFail($id);
        $wikidataId = $author->wikidata_id;

        // Validate input
        $request->validate([
            'aliases'          => 'nullable|array',
            'aliases.*.name'   => 'required|string|max:255',
            'aliases.*.locale' => 'required|string|exists:language,locale',
        ]);
        // Prepare for upsert: only keep aliases for enabled languages and deduplicate
        $aliasesInput = $request->input('aliases', []);

        // Fetch enabled languages and build lowercase locale -> id map
        $enabledLocales = LanguageRepository::allInUse()->pluck('locale')
            ->map(function ($l) { return strtolower($l); })->unique()->values()->toArray();

        $localeToId = \App\Models\Language::whereIn('locale', $enabledLocales)
            ->pluck('id', 'locale')
            ->mapWithKeys(function ($v, $k) { return [strtolower($k) => $v]; })
            ->toArray();

        $rows            = [];
        $seen            = [];
        $nameLangChanged = false;

        foreach ($aliasesInput as $aliasData) {
            $name   = isset($aliasData['name']) ? trim($aliasData['name']) : '';
            $locale = isset($aliasData['locale']) ? $aliasData['locale'] : '';
            if ($name === '' || $locale === '') {
                continue;
            }

            $lowercaseLocale = strtolower($locale);
            // ignore locales that are not enabled
            if (!in_array($lowercaseLocale, $enabledLocales, true)) {
                continue;
            }

            $key = $lowercaseLocale . '|' . $name;
            if (isset($seen[$key])) {
                continue; // deduplicate
            }

            $rows[] = [
                'author_id'   => $id,
                'name'        => $name,
                'locale'      => $locale,
                'language_id' => $localeToId[$lowercaseLocale] ?? null,
                'wikidata_id' => $wikidataId                   ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            $seen[$key] = true;

            // If author's name_lang missing for this locale, set it from alias
            $existingLocaleName = $author->getTranslated('name_lang', $locale);
            if (empty($existingLocaleName)) {
                $author->setTranslation('name_lang', $locale, $name);
                $nameLangChanged = true;
            }
        }

        // Perform delete + bulk insert inside a transaction for consistency
        DB::transaction(function () use ($id, $rows) {
            \App\Models\Alias::where('author_id', $id)->delete();
            if (!empty($rows)) {
                \App\Models\Alias::insert($rows);
            }
        });

        // Persist name_lang changes without triggering Author model events
        if ($nameLangChanged) {
            \App\Models\Author::withoutEvents(function () use ($author) {
                $author->save();
            });
        }

        // Redirect back to alias edit page (stay on current page) with success flash
        return redirect()
            ->route('author/alias/edit', $author->fakeId) // was: author/show
            ->with('success', __('Aliases updated successfully!'));
    }

}
