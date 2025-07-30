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
use App\Services\Tx;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller {
    /** @var AuthorRepository */
    private $authorRepository;

    public function __construct(AuthorRepository $poemRepo) {
        $this->middleware('auth')->except(['show', 'random']);
        $this->authorRepository = $poemRepo;
    }

    /**
     * Display the specified author.
     * @param string $fakeId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function show($fakeId) {
        $id          = Author::getIdFromFakeId($fakeId);
        $author      = Author::findOrFail($id/*, [
            'id', 'name_lang', 'describe_lang',
            'nation_id', 'user_id', 'avatar', 'wikidata_id',
            'short_url', 'wiki_desc_lang'
        ]*/);
        $poemsAsPoet = Poem::where(['poet_id' => $id])->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('relatable')
                ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem'])
            ;
        })->orderByRaw('convert(title using gb2312)')->get();
        
        // Optimize translator data loading (for fallback cases)
        $this->preloadTranslatorsForPoems($poemsAsPoet);
        
        $authorUserOriginalWorks = $author->user ? $author->user->originalPoemsOwned : [];

        // TODO poem.translator_id should be deprecated
        $poemsAsTranslatorAuthor = Poem::where(['translator_id' => $id])->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('relatable')
                ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
        })->get();
        $poemsAsRelatedTranslator = $author->poemsAsTranslator()->get();
        $poemsAsTranslator        = $poemsAsTranslatorAuthor->concat($poemsAsRelatedTranslator)->unique('id');
        
        // Optimize poet data loading for translator poems (for fallback cases)
        $this->preloadPoetsForPoems($poemsAsTranslator);

        $from         = request()->get('from');
        $fromPoetName = '';
        if (is_numeric($from)) {
            $fromPoem = Poem::findOrFail($from);
            if ($fromPoem->poet_cn) {
                $fromPoetName = $fromPoem->poet_cn;
                if ($fromPoem->poet_cn !== $fromPoem->poet) {
                    $fromPoetName .= $fromPoem->poet;
                }
            } else {
                $fromPoetName = $fromPoem->poet;
            }
        }

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
            'alias'             => $author->alias_arr,
            'label'             => $author->label,
            'poemsAsPoet'       => $poemsAsPoet->concat($authorUserOriginalWorks),
            'poemsAsTranslator' => $poemsAsTranslator,
            'fromPoetName'      => $fromPoetName,
            'lastOnline'        => $lastOnlineAgo
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

    /**
     * Preload translator data for poems to avoid N+1 queries
     */
    private function preloadTranslatorsForPoems($poems) {
        if ($poems->isEmpty()) {
            return;
        }

        $poemIds = $poems->pluck('id');
        
        // Get translator data from translator_id field (direct relationship)
        $translatorIds = $poems->whereNotNull('translator_id')->pluck('translator_id')->unique();
        $directTranslatorsData = collect();
        
        if ($translatorIds->isNotEmpty()) {
            $directTranslatorsData = DB::table('author')
                ->whereIn('id', $translatorIds)
                ->select('id', 'name_lang')
                ->get()
                ->keyBy('id');
        }
        
        // Get translator data from relatable table (many-to-many relationship)
        $relatableTranslatorsData = DB::table('relatable')
            ->leftJoin('author', function($join) {
                $join->on('relatable.end_id', '=', 'author.id')
                     ->where('relatable.end_type', '=', Author::class);
            })
            ->leftJoin('entry', function($join) {
                $join->on('relatable.end_id', '=', 'entry.id')
                     ->where('relatable.end_type', '=', Entry::class);
            })
            ->whereIn('relatable.start_id', $poemIds)
            ->where('relatable.relation', '=', Relatable::RELATION['translator_is'])
            ->where('relatable.start_type', '=', Poem::class)
            ->select('relatable.start_id as poem_id', 'relatable.end_type', 'relatable.end_id',
                    'author.name_lang as author_name', 'entry.name as entry_name')
            ->get()
            ->groupBy('poem_id');

        // Cache translator data for each poem
        foreach ($poems as $poem) {
            $translators = collect();
            
            // First try translator_id (direct relationship)
            if ($poem->translator_id && isset($directTranslatorsData[$poem->translator_id])) {
                $authorData = $directTranslatorsData[$poem->translator_id];
                $authorName = json_decode($authorData->name_lang, true);
                $name = is_array($authorName) ? ($authorName['zh-CN'] ?? $authorName['en'] ?? reset($authorName)) : $authorData->name_lang;
                
                $translators->push([
                    'id' => $poem->translator_id,
                    'name' => $name
                ]);
            }
            
            // Then add relatable translators (many-to-many relationship)
            if (isset($relatableTranslatorsData[$poem->id])) {
                $relatableTranslators = $relatableTranslatorsData[$poem->id]->map(function($item) {
                    if ($item->end_type === Author::class) {
                        $authorName = json_decode($item->author_name, true);
                        $name = is_array($authorName) ? ($authorName['zh-CN'] ?? $authorName['en'] ?? reset($authorName)) : $item->author_name;
                    } else {
                        $name = $item->entry_name;
                    }
                    return [
                        'id' => $item->end_id,
                        'name' => $name
                    ];
                });
                
                $translators = $translators->concat($relatableTranslators);
            }
            
            if ($translators->isNotEmpty()) {
                $poem->setRelation('cached_translators', $translators);
                
                // Also cache the translators string for direct access
                $translatorsStr = $translators->pluck('name')->implode(', ');
                $poem->setRelation('cached_translators_str', $translatorsStr);
            } else {
                // If no translators found in relations but poem has translator field, cache it
                if (!empty($poem->translator)) {
                    $poem->setRelation('cached_translators_str', $poem->translator);
                }
            }
        }
    }

    /**
     * Preload poet data for poems to avoid N+1 queries
     */
    private function preloadPoetsForPoems($poems) {
        if ($poems->isEmpty()) {
            return;
        }

        $poemIds = $poems->pluck('id');
        
        // Get poet data from poet_id field
        $poetIds = $poems->whereNotNull('poet_id')->pluck('poet_id')->unique();
        $poetsData = collect();
        
        if ($poetIds->isNotEmpty()) {
            $poetsData = DB::table('author')
                ->whereIn('id', $poetIds)
                ->select('id', 'name_lang')
                ->get()
                ->keyBy('id');
        }

        // Get poet data from relatable table
        $relatablePoetsData = DB::table('relatable')
            ->leftJoin('author', function($join) {
                $join->on('relatable.end_id', '=', 'author.id')
                     ->where('relatable.end_type', '=', Author::class);
            })
            ->leftJoin('entry', function($join) {
                $join->on('relatable.end_id', '=', 'entry.id')
                     ->where('relatable.end_type', '=', Entry::class);
            })
            ->whereIn('relatable.start_id', $poemIds)
            ->where('relatable.relation', '=', Relatable::RELATION['poet_is'])
            ->where('relatable.start_type', '=', Poem::class)
            ->select('relatable.start_id as poem_id', 'relatable.end_type', 'relatable.end_id',
                    'author.name_lang as author_name', 'entry.name as entry_name')
            ->get()
            ->groupBy('poem_id');

        // Cache poet data for each poem
        foreach ($poems as $poem) {
            $cachedPoet = null;
            $cachedPoetAuthor = null;
            
            // First try poet_id
            if ($poem->poet_id && isset($poetsData[$poem->poet_id])) {
                $authorData = $poetsData[$poem->poet_id];
                $authorName = json_decode($authorData->name_lang, true);
                $name = is_array($authorName) ? ($authorName['zh-CN'] ?? $authorName['en'] ?? reset($authorName)) : $authorData->name_lang;
                
                $cachedPoet = [
                    'id' => $poem->poet_id,
                    'name' => $name
                ];
                
                // Create a cached poetAuthor object
                $cachedPoetAuthor = (object) [
                    'id' => $poem->poet_id,
                    'name_lang' => $authorData->name_lang,
                    'label' => $name
                ];
            }
            // Then try relatable poets
            elseif (isset($relatablePoetsData[$poem->id])) {
                $poetItem = $relatablePoetsData[$poem->id]->first();
                if ($poetItem->end_type === Author::class) {
                    $authorName = json_decode($poetItem->author_name, true);
                    $name = is_array($authorName) ? ($authorName['zh-CN'] ?? $authorName['en'] ?? reset($authorName)) : $poetItem->author_name;
                    
                    $cachedPoetAuthor = (object) [
                        'id' => $poetItem->end_id,
                        'name_lang' => $poetItem->author_name,
                        'label' => $name
                    ];
                } else {
                    $name = $poetItem->entry_name;
                }
                
                $cachedPoet = [
                    'id' => $poetItem->end_id,
                    'name' => $name
                ];
            }
            
            if ($cachedPoet) {
                $poem->setRelation('cached_poet', collect([$cachedPoet]));
            }
            
            if ($cachedPoetAuthor) {
                $poem->setRelation('poetAuthor', $cachedPoetAuthor);
            }
        }
    }
}
