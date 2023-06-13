<?php

namespace App\Http\Controllers;

use App\Repositories\AuthorRepository;
use App\Repositories\NationRepository;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Searchable\Search;

class QueryController extends Controller {
    // public function __construct() {
    // }
    public function index() {
        return view('query.search');
    }

    public function nation($keyword, $id) {
        return $this->response(NationRepository::searchByName(Str::trimSpaces($keyword), $id));
    }

    public function author($keyword, $id = null) {
        // TODO do i need urldecode $id here?
        return $this->response(AuthorRepository::searchLabel(Str::trimSpaces($keyword), $id ? explode(',', $id) : null));
    }

    // TODO support multiple word search like bot search, order by relative
    public function poem($keyword) {
        return $this->response(PoemRepository::searchByName(Str::trimSpaces($keyword)));
    }

    // TODO support multiple word search like bot search, order by relative
    public function search($keyword) {
        $keyword = Str::trimSpaces($keyword);
        if ($keyword === '' || is_null($keyword)) {
            return view('query.search');
        }

        $keyword4Query = Str::of($keyword)
            // ->replace('·', ' ')
            // ->replaceMatches('@[[:punct:]]+@u', ' ')
            ->replaceMatches('@\b[a-zA-Z]{1,2}\b@u', ' ')
            ->replaceMatches('@\s+@u', ' ')
            ->trim();

        if ($keyword4Query->length < 1) {
            return view('query.search')->with([
                'authors' => [],
                'poems'   => [],
                'keyword' => $keyword
            ]);
        }

        $authors = \App\Models\Author::search($keyword4Query)->paginate();

        return view('query.search')->with([
            'authors' => $authors,
            'poems'   => \App\Models\Poem::search($keyword4Query)->paginate(),
            'keyword' => $keyword
        ]);
    }

    public function query(Request $request) {
        $keyword = $request->input('keyword');

        return $this->search($keyword);
    }
}
