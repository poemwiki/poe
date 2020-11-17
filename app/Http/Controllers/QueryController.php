<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\Search;

class QueryController extends Controller {

    // public function __construct() {
    // }

    public function search(string $keyword){
        $keyword = Str::of($keyword)
            // ->replace('·', ' ')
            ->replaceMatches('@[[:punct:]]+@u', ' ')
            ->replaceMatches('@\s+@u', ' ')
            ->trim();//->lower();
        // dd($keyword);

        DB::enableQueryLog();
        $searchResults = (new Search())
            ->registerModel(Author::class, function(ModelSearchAspect $modelSearchAspect) {
                return $modelSearchAspect
                    ->addSearchableAttribute('name_lang')
                    // ->has('poems')
                    // ->with('poems')
                    // ->with('translatedPoems')
                ;
            })
            ->registerModel(Poem::class, 'title', 'poem', 'poet', 'poet_cn', 'translator')//, 'poet')
            ->search($keyword);

        // dd(DB::getQueryLog());
        // dd($searchResults);
        $authorCount = $searchResults->filter(function ($item) {
            return $item->type === 'author';
        })->count();
        return view('query.search')->with([
            'res' => $searchResults,
            'keyword' => $keyword,
            'authorCount' => $authorCount
        ]);
    }

    public function query(Request $request) {
        $keyword = $request->input('keyword');
        return $this->search($keyword);
    }
}