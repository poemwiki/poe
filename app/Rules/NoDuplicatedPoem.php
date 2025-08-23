<?php

namespace App\Rules;

use App\Models\Poem;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;

class NoDuplicatedPoem implements Rule {
    private ?int $poem_id;
    private string $transKey;
    private Poem|false $dupPoem;

    /**
     * Create a new rule instance.
     *
     * @param int|null $poem_id  set to null if you want to create a new poem, or set to the id of the poem you want to update
     * @param string   $transKey
     */
    public function __construct(?int $poem_id, string $transKey = 'poem.duplicated poem') {
        $this->poem_id  = $poem_id;
        $this->transKey = $transKey;
        $this->dupPoem  = false;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @return bool false if failed(has duplicated poem content)
     */
    public function passes($attribute, $value) {
        $this->dupPoem = PoemRepository::isDuplicated($value);
        if (is_null($this->poem_id)) {
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
        if ($this->transKey === 'id') {
            return $this->dupPoem->id;
        }

        return '[NoDuplicatedPoem]: ' . trans($this->transKey, [
            'url'   => route('p/show', Poem::getFakeId($this->dupPoem->id)),
            'title' => $this->dupPoem->title,
            'id'    => $this->dupPoem->id,
            'poet'  => $this->dupPoem->poetLabel
        ]);
    }
}
