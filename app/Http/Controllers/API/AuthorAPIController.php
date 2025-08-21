<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Repositories\PoemRepository;
use App\Repositories\AuthorRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Wikidata;
use Illuminate\Http\Request;

class AuthorAPIController extends Controller {
    private $_authorInfoFields = ['id', 'avatar_url', 'name_lang', 'describe_lang', 'is_v', 'birth', 'birth_fields', 'death', 'death_fields'];

    public function detail($id, Request $request): array {
        $author = Author::find($id);
        $user   = null;

        if (!$author) {
            return $this->responseFail();
        }

        if ($author->user && $author->user->is_v) {
            $author->is_v = true;
            $user         = $author->user;
        }

        $sortType = $request->get('sort', 'hottest'); // 'hottest' or 'newest'

        $allOriginalPoems = $author->poems;
        if ($author->user) {
            $allOriginalPoems = $allOriginalPoems->concat($author->user->originalPoemsOwned);
        }

        $originalWorks = PoemRepository::prepareAuthorPoemsForAPI($allOriginalPoems, $sortType, ['noAvatar' => true, 'noPoet' => true]);

        // TODO poem.translator_id should be deprecated
        // TODO consider different type of poem owner
        $poemsAsTranslator = $author->translatedPoems->concat($author->poemsAsTranslator);
        $translationWorks  = PoemRepository::prepareAuthorPoemsForAPI($poemsAsTranslator, $sortType);

        return $this->responseSuccess([
            'author'            => $author->only($this->_authorInfoFields),
            'user'              => $user ? $user->only(['id', 'avatar', 'name', 'is_v']) : null,
            'original_works'    => $originalWorks,
            'translation_works' => $translationWorks
        ]);
    }

    public function info($id): array {
        $author = Author::find($id);

        if (!$author) {
            return $this->responseFail();
        }

        return $this->responseSuccess($author->only($this->_authorInfoFields));
    }

    public function create(Request $request): array {
        $author = Author::create([
            'name_lang'     => $request->input('name'),
            'describe_lang' => $request->input('desc'),
        ]);

        return $this->responseSuccess(['id' => $author->id]);
    }

    public function update(Request $request, $id): array {
        $author      = Author::find($id);
        $birth       = $request->input('birth');
        $birthFields = $request->input('birth_fields'); //'year' | 'month' | 'day' | null,
        if (!$author) {
            return $this->responseFail();
        }

        switch ($birthFields) {
            case 'year':
                if (is_numeric($birth)) {
                    $author->birth_year = $birth;
                }

                break;
            case 'month':
                $peaces = explode('-', $birth);
                if (count($peaces) == 2) {
                    $author->birth_year  = $peaces[0];
                    $author->birth_month = $peaces[1];
                }

                break;
            case 'day':
                $peaces = explode('-', $birth);
                if (count($peaces) == 3) {
                    $author->birth_year  = $peaces[0];
                    $author->birth_month = $peaces[1];
                    $author->birth_day   = $peaces[2];
                }

                break;
            default:
                break;
        }

        $author->name_lang     = $request->input('name');
        $author->describe_lang = $request->input('desc');
        $author->save();

        return $this->responseSuccess(['id' => $author->id]);
    }

        /**
         * Import single author used by automated agents.
         * Accepts minimal payload: name, describe, describe_locale, wikidata_id
         */
        public function importSimple(Request $request): array {
            $input = $request->only(['name', 'describe', 'describe_locale', 'wikidata_id']);

            $validator = Validator::make($input, [
                'name' => 'required|string|min:1|max:50',
                'describe' => 'nullable|string|max:2000',
                'describe_locale' => 'nullable|string|max:10',
                'wikidata_id' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return $this->responseFail($validator->errors()->toArray(), 'invalid', Controller::$CODE['invalid'] ?? 422);
            }

            $name = trim($input['name']);
            if ($name === '' || preg_match('/^[\p{P}\p{S}0-9]+$/u', $name)) {
                return $this->responseFail([], 'invalid name', Controller::$CODE['invalid'] ?? 422);
            }

            $describe = $input['describe'] ?? null;
            $describeLocale = $input['describe_locale'] ?? config('app.locale', 'zh-CN');

            // 1. wikidata branch
            if (!empty($input['wikidata_id'])) {
                $wikidataId = (int)$input['wikidata_id'];
                $authorExisted = Author::where('wikidata_id', $wikidataId)->first();
                if ($authorExisted) {
                    return $this->responseSuccess(['status' => 'existed', 'author' => $this->buildAuthorResource($authorExisted)], 'Author exists');
                }

                $wiki = Wikidata::find($wikidataId);
                if ($wiki) {
                    $repo = new AuthorRepository(app());
                    $author = $repo->importFromWikidata($wiki, optional($request->user())->id);
                    if ($describe) {
                        $author->setTranslation('describe_lang', $describeLocale, $describe);
                        $author->save();
                    }

                    return $this->responseSuccess(['status' => 'created', 'author' => $this->buildAuthorResource($author)], 'Author created from wikidata');
                }

                // create minimal record if wikidata not found
                $author = Author::create([
                    'name_lang' => [$this->getDefaultLocale() => $name],
                    'wikidata_id' => $wikidataId,
                    'upload_user_id' => optional($request->user())->id
                ]);

                if ($describe) {
                    $author->setTranslation('describe_lang', $describeLocale, $describe);
                    $author->save();
                }

                return $this->responseSuccess(['status' => 'created', 'author' => $this->buildAuthorResource($author)], 'Author created');
            }

            // 2. name based branch
            $norm = $this->normalizeName($name);
            $candidates = $this->findCandidates($norm);

            if ($candidates->count() === 0) {
                $author = Author::create([
                    'name_lang' => [$this->getDefaultLocale() => $name],
                    'upload_user_id' => optional($request->user())->id
                ]);
                if ($describe) {
                    $author->setTranslation('describe_lang', $describeLocale, $describe);
                    $author->save();
                }

                return $this->responseSuccess(['status' => 'created', 'author' => $this->buildAuthorResource($author)], 'Author created');
            }

            if ($candidates->count() === 1) {
                $author = Author::find($candidates->first()['author_id']);
                if ($author) {
                    return $this->responseSuccess(['status' => 'existed', 'author' => $this->buildAuthorResource($author)], 'Author exists');
                }
            }



            // multiple candidates -> score and decide
        $scored = $this->evaluateAuthorCandidates($candidates, $norm);
            $first = $scored->first();
            $second = $scored->skip(1)->first();

            $firstScore = $first['score'] ?? 0;
            $secondScore = $second['score'] ?? 0;

            if ($firstScore >= $secondScore + 25) {
                $author = Author::find($first['author_id']);
                if ($author) {
                    return $this->responseSuccess(['status' => 'existed', 'author' => $this->buildAuthorResource($author)], 'Author exists (auto selected)');
                }
            }

            // ambiguous
            $cands = $scored->map(function ($item) {
                return [
                    'id' => $item['author_id'] ?? $item['id'] ?? null,
                    'label' => $item['label'] ?? ($item['name'] ?? null),
                    'wikidata_id' => $item['wikidata_id'] ?? null,
                    'poem_count' => $item['poem_count'] ?? 0,
                    'score' => $item['score'] ?? 0
                ];
            })->values();

            return $this->responseSuccess(['status' => 'ambiguous', 'candidates' => $cands], 'Multiple authors match');
        }

        private function normalizeName(string $name): string {
            $s = Str::of($name)->trim()->__toString();
            $s = preg_replace('/[·•‧·、\.，,\s]+/u', ' ', $s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return mb_strtolower(trim($s));
        }

        private function findCandidates(string $norm) {
            // search alias
            $aliasRes = AuthorRepository::searchByAlias($norm, null);

            // search name_lang via repository searchByName
            $nameRes = collect(AuthorRepository::searchByName($norm))->map(function ($item) {
                return ['author_id' => $item['id'], 'label' => $item['name_lang'] ?? null, 'wikidata_id' => null];
            });

            return $nameRes->concat($aliasRes)->unique('author_id')->values();
        }

        private function evaluateAuthorCandidates($candidates, $norm) {
            return $candidates->map(function ($item) use ($norm) {
                $score = 0;
                $label = $item['label'] ?? ''; // may be array
                $name = is_array($label) ? ($label[config('app.locale')] ?? join(' ', $label)) : $label;
                if ($name && $this->normalizeName($name) === $norm) {
                    $score += 50;
                }
                if (!empty($item['wikidata_id'])) {
                    $score += 20;
                }
                $poemCount = 0;
                if (!empty($item['author_id'])) {
                    $author = Author::find($item['author_id']);
                    if ($author) {
                        $poemCount = $author->poems()->count();
                    }
                }
                $score += min($poemCount, 30);
                $item['poem_count'] = $poemCount;
                $item['score'] = $score;

                return $item;
            })->sortByDesc('score')->values();
        }

        private function buildAuthorResource(Author $author) {
            return [
                'id' => $author->id,
                'label' => $author->label,
                'label_cn' => $author->label_cn,
                'label_en' => $author->label_en,
                'wikidata_id' => $author->wikidata_id,
                'url' => $author->url,
                'avatar_url' => $author->avatar_url
            ];
        }

        private function getDefaultLocale() {
            return config('app.locale', 'zh-CN');
        }

    /**
     * Search authors only. Returns authors array under data.authors.
     * Query params: keyword (string), limit (int, optional)
     */
    public function search(Request $request): array {
        $keyword = trim((string)$request->json('keyword', ''));
        $limit = (int)$request->get('limit', 10);

        if ($keyword === '') {
            return $this->responseFail([], 'empty keyword');
        }

        // reuse repository label search which includes alias/wikidata
        $authorIds = [];
        // AuthorRepository::searchLabel accepts name and array of ids (per signature)
        $res = AuthorRepository::searchLabel($keyword, $authorIds)->take($limit)->map(function ($item) {
            if ($item['source'] !== 'PoemWiki') return null;
            return [
                'id' => $item['author_id'] ?? $item['id'] ?? null,
                'label' => $item['label'] ?? ($item['name'] ?? null),
                'wikidata_id' => $item['wikidata_id'] ?? null,
                'avatar_url' => $item['avatar_url'] ?? ($item['pic_url'] ?? null),
                'desc' => $item['desc'] ?? null,
                'source' => $item['source'] ?? null,
            ];
        })->filter()->values();

        return $this->responseSuccess(['authors' => $res]);
    }

}