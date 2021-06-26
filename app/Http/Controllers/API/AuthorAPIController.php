<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuthorAPIController extends Controller {

    public function detail($id) {
        $author = Author::find($id);

        if(!$author) {
            return $this->responseFail();
        }

        if($author->user && $author->user->is_v) {
            $author->is_v = true;
        }

        $originalWorks = $this->_prepare($author->poems);
        $authorUserOriginalWorks = $author->user ? $this->_prepare($author->user->originalPoemsOwned) : [];
        $translationWorks = $this->_prepare($author->translatedPoems);

        return $this->responseSuccess([
            'author' => $author->only(['id', 'avatar_url', 'name_lang', 'describe_lang', 'is_v']),
            'original_works' => $originalWorks->concat($authorUserOriginalWorks),
            'translation_works' => $translationWorks
        ]);
    }

    private function _prepare(Collection $result) {
        return $result->map(function (Poem $item) {
            $score = $item->score_array;
            $item['score'] = $score['score'];
            $item['score_count'] = $score['count'];
            $item['score_weight'] = $score['weight'];
            $item['poem'] = $item->firstLine;
            return $item;
        })->sort(function ($a, $b) {
            $scoreOrder = $b['score'] <=> $a['score'];
            $countOrder = $b['score_count'] <=> $a['score_count'];
            return $scoreOrder === 0
                ? ($countOrder === 0 ? $b['score_weight'] <=> $a['score_weight'] : $countOrder)
                : $scoreOrder;
        })->map->only([
            'id', 'created_at', 'date_ago', 'title', //'subtitle', 'preface', 'location',
            'poem', 'poet', 'poet_id', 'poet_avatar', 'poet_cn',
            'score', 'score_count', 'score_weight'
        ])->values();
    }
}