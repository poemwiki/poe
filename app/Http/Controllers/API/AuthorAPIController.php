<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Poem;
use App\Models\Score;
use App\Repositories\ScoreRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuthorAPIController extends Controller {
    private $_authorInfoFields = ['id', 'avatar_url', 'name_lang', 'describe_lang', 'is_v', 'birth', 'birth_fields', 'death', 'death_fields'];

    public function detail($id): array {
        $author = Author::find($id);
        $user   = null;

        if (!$author) {
            return $this->responseFail();
        }

        if ($author->user && $author->user->is_v) {
            $author->is_v = true;
            $user         = $author->user;
        }

        $originalWorks           = $this->_prepare($author->poems, ['noAvatar' => true, 'noPoet' => true]);
        $authorUserOriginalWorks = $author->user ? $this->_prepare($author->user->originalPoemsOwned, ['noAvatar' => true, 'noPoet' => true]) : [];

        // TODO poem.translator_id should be deprecated
        // TODO consider different type of poem owner
        $poemsAsTranslator = $author->translatedPoems->concat($author->poemsAsTranslator);
        $translationWorks  = $this->_prepare($poemsAsTranslator);

        return $this->responseSuccess([
            'author'            => $author->only($this->_authorInfoFields),
            'user'              => $user ? $user->only(['id', 'avatar', 'name', 'is_v']) : null,
            'original_works'    => $originalWorks->concat($authorUserOriginalWorks),
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

    private function _prepare(Collection $result, $opt = ['noAvatar' => false, 'noPoet' => false]) {
        list('noAvatar' => $noAvatar, 'noPoet' => $noPoet) = $opt;
        $columns                                           = [
            'id', 'created_at', 'date_ago', 'title', //'subtitle', 'preface', 'location',
            'poem', 'poet', 'poet_id',
            'score', 'score_count', 'score_weight'
        ];
        if (!$noAvatar) {
            $columns[] = 'poet_avatar';
        }

        $poemScores = ScoreRepository::batchCalc($result->pluck('id')->values()->all());

        return $result->map(function (Poem $item) use ($noPoet, $poemScores) {
            $score = isset($poemScores[$item->id]) ? $poemScores[$item->id] : Score::$DEFAULT_SCORE_ARR;
            $item['score'] = $score['score'];
            $item['score_count'] = $score['count'];
            $item['score_weight'] = $score['weight'];
            $item['poem'] = $item->firstLine;

            if (!$noPoet) {
                $item['poet'] = $item->poetLabel;
            }

            return $item;
        })->sort(function ($a, $b) {
            $scoreOrder = $b['score'] <=> $a['score'];
            $countOrder = $b['score_count'] <=> $a['score_count'];

            return $scoreOrder === 0
                ? ($countOrder === 0 ? $b['score_weight'] <=> $a['score_weight'] : $countOrder)
                : $scoreOrder;
        })->map->only($columns)->values();
    }
}