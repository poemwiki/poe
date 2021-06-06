<?php

namespace App\Rules;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;

class ValidOriginalLink implements Rule {
    /**
     * Create a new rule instance.
     *
     * @param int|null $poem_id
     * @param string $transKey
     */
    public function __construct() {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool false if failed
     */
    public function passes($attribute, $value) {
        $pattern = '@^' . str_replace('.', '\.', config('app.url')) . '/p/(.*)$@';
        $fakeId = Str::of($value)->match($pattern)->__toString();
        if ($fakeId) {
            $poem = Poem::find(Poem::getIdFromFakeId($fakeId));
            if($poem) return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('Not a valid poem url');
    }
}
