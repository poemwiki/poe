<?php


namespace App\Query;

use App\Models\Author;
use Illuminate\Support\Collection;
use Spatie\Searchable\Exceptions\InvalidModelSearchAspect;
use Spatie\Searchable\ModelSearchAspect;
use Spatie\Searchable\SearchAspect;

class BaseSearchAspect extends ModelSearchAspect {

    public function getResults(string $term): Collection {
        if (empty($this->attributes)) {
            throw InvalidModelSearchAspect::noSearchableAttributes($this->model);
        }

        $query = ($this->model)::query();
        $select = $this->attributes;
        $query->select($select);

        foreach ($this->callsToForward as $method => $parameters) {
            $this->forwardCallTo($query, $method, $parameters);
        }

        $this->addSearchConditions($query, $term);

        return $query->get();
    }
}