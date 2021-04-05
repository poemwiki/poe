<?php

namespace App\Rules;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;
use phpDocumentor\Reflection\Types\Integer;

class NoDuplicatedPoem implements Rule {
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $poem_id = null) {
        $this->poem_id = $poem_id;
        $this->dupPoem = null;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value) {
        $this->dupPoem = PoemRepository::isDuplicated($value);
        if(is_null($this->poem_id)) {
            return !$this->dupPoem;
        }

        return $this->dupPoem ? $this->poem_id === $this->dupPoem->id : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('poem.duplicated poem', [
            'url' => route('p/show', Poem::getFakeId($this->dupPoem->id)),
            'title' => $this->dupPoem->title,
            'poet' => $this->dupPoem->poetLabel
        ]);
    }
}
