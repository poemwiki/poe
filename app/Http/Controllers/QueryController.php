<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Nation;
use App\Models\Poem;
use App\Query\AuthorAliasSearchAspect;
use App\Repositories\AuthorRepository;
use App\Repositories\NationRepository;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Collection\Collection;
use Spatie\Searchable\ModelSearchAspect;
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

    public function author($keyword, $id=null) {
        return $this->response(AuthorRepository::searchLabel(Str::trimSpaces($keyword), $id ? [$id] : null));
    }

    // TODO support multiple word search like bot search, order by relative
    public function poem($keyword) {
        return $this->response(PoemRepository::searchByName(Str::trimSpaces($keyword)));
    }

    // TODO support multiple word search like bot search, order by relative
    public function search($keyword) {
        $keyword = Str::trimSpaces($keyword);
        if ($keyword === '' || is_null($keyword)) return view('query.search');

        $keyword4Query = Str::of($keyword)
            // ->replace('·', ' ')
            // ->replaceMatches('@[[:punct:]]+@u', ' ')
            ->replaceMatches('@\b[a-zA-Z]{1,2}\b@u', ' ')
            ->replaceMatches('@\s+@u', ' ')
            ->trim();
        // dd($keyword4Query);
        if ($keyword4Query->length < 1) {
            return view('query.search')->with([
                'authors' => [],
                'poems' => [],
                'keyword' => $keyword
            ]);
        }

        // DB::enableQueryLog();
        $searchResults = (new Search())
            ->registerAspect(AuthorAliasSearchAspect::class)
            ->registerModel(Poem::class, function (ModelSearchAspect $modelSearchAspect) {
                $modelSearchAspect
                    ->addSearchableAttribute('title') // return results for partial matches
                    ->addSearchableAttribute('poem')
                    ->addSearchableAttribute('poet')
                    ->addSearchableAttribute('poet_cn')
                    ->addSearchableAttribute('translator')
                    ->addSearchableAttribute('preface')
                    ->addSearchableAttribute('subtitle')
                    ->addSearchableAttribute('location')
                    ->with('poetAuthor')->limit(100);
                // ->addExactSearchableAttribute('upload_user_name') // only return results that exactly match the e-mail address
            })
            // ->registerModel(Poem::class, 'title', 'poem', 'poet', 'poet_cn', 'translator')//, 'poet')
            ->search($keyword4Query);

        // dd(DB::getQueryLog());
        $results = $searchResults->groupByType();
        $authors = $results->get('authorAlias') ?: collect();
        $authors = $authors->filter(function($author) {
            // TODO show wikidata poet on search result page: $author->searchable instanceof \App\Models\Wikidata
            return $author->searchable instanceof \App\Models\Author;
        });

        $poems = $results->get('poem') ?: [];

        $shiftPoems = collect();

        foreach ($poems as $p) {
            $shiftPoems->push($p->searchable);
        }

        foreach ($authors as $key => $author) {
            if ($key >= 5) break;

            // in case of $author->searchable instanceof \App\Models\Wikidata
            // after show wikidata poet on search result page
            if ($author->searchable instanceof \App\Models\Author) {
                foreach ($author->searchable->poems as $poem) {
                    $shiftPoems->push($poem);
                }
            }
        }

        // TODO append translated poems
        $mergedPoems = $shiftPoems->unique('id');

        // dd($mergedPoems);
        return view('query.search')->with([
            'authors' => $authors,
            'poems' => $mergedPoems,
            'keyword' => $keyword
        ]);
    }

    public function query(Request $request) {
        $keyword = $request->input('keyword');
        return $this->search($keyword);
    }
}
