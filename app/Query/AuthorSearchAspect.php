<?php


namespace App\Query;

use App\Models\Author;
use App\Repositories\AuthorRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\SearchAspect;

// TODO AuthorSearchAspect extends MultiLangSearchAspect
class AuthorSearchAspect extends SearchAspect {

    public function getResults(string $term): Collection {
        $value = DB::connection()->getPdo()->quote('%' . strtolower($term) . '%');
        // return AuthorRepository::searchLabel($term);
        return Author::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_lang, '$.*'))) LIKE $value ")->limit(10)->get();
    }
}