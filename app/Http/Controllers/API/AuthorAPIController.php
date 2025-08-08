<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Repositories\PoemRepository;
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

}