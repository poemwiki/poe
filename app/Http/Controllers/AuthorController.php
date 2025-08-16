<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\Author\StoreAuthor;
use App\Http\Requests\Admin\Author\UpdateAuthor;
use App\Models\Author;
use App\Models\Entry;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
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
        $id          = Author::getIdFromFakeId($fakeId);
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
            logger()->info('scropAndUpload finished:', $result);
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

}
