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
    private $poetTestFailed;

    /**
     * Create a new rule instance.
     *
     * @param $original_id
     */
    public function __construct() {
        $this->poetTestFailed = 0;
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
        if($this->poetTestFailed == 1) {
            return trans('error.Not a valid translator', [
                'reason' => trans('error.The translated form poem should link to a author page.')
            ]);
        }
        if($this->poetTestFailed == 2) {
            return trans('error.Not a valid translator', [
                'reason' => trans('error.Two poem should belong to same poet')
            ]);
        }

        return trans('error.Not a valid translator');
    }
}
