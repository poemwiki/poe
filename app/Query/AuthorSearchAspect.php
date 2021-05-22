<?php


namespace App\Query;

use App\Models\Author;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\SearchAspect;

// TODO AuthorSearchAspect extends MultiLangSearchAspect
class AuthorSearchAspect extends SearchAspect {

    public function getType(): string {
        if (isset(static::$searchType)) {
            return static::$searchType;
        }

        $className = class_basename(static::class);

        $type = Str::before($className, 'SearchAspect');

        $type = Str::snake(Str::plural($type));

        return Str::plural($type);
    }

    public function getResults(string $term): Collection {

        return Author::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_lang, '$.*'))) LIKE \"%$term%\" ")->limit(10)->get();

    }
}