<?php

namespace App\Rules;

use App\Models\Author;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;

class ValidTranslatorId implements Rule {
    /**
     * @var int
     */
    // private $poetTestFailed;

    /**
     * Create a new rule instance.
     *
     * @param $original_id
     */
    public function __construct() {
        // $this->poetTestFailed = 0;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool false if failed
     */
    public function passes($attribute, $value):bool {
        if($value === 'new') {
            return true;
        }

        return !!Author::find($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('error.Not a valid translator', [
            'reason' => ''
        ]);
    }
}
