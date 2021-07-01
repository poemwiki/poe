<?php


namespace App\Query;

use App\Models\Alias;
use App\Models\Author;
use App\Repositories\AuthorRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\SearchAspect;

// TODO AuthorSearchAspect extends MultiLangSearchAspect
class AuthorAliasSearchAspect extends SearchAspect {

    static $searchType = 'authorAlias';

    public function getResults(string $term): Collection {
        // TODO split $term to keywords
        $value = DB::connection()->getPdo()->quote('%' . strtolower($term) . '%');

        return Alias::selectRaw('wikidata_id, min(wikidata_id) as id, min(name) as name, author_id')
            ->whereRaw("`name` LIKE $value ")
            ->groupBy(['wikidata_id', 'author_id'])
            ->orderBy('author_id', 'desc')
            ->limit(10)->get();
        // return AuthorRepository::searchLabel($term);
        // return Author::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_lang, '$.*'))) LIKE \"%$term%\" ")->limit(10)->get();
    }
}