<?php

namespace App\Rules;

use App\Models\Author;
use App\Models\Poem;
use Illuminate\Contracts\Validation\Rule;

class ValidPoetId implements Rule {
    /**
     * @var int
     */
    private $_poetTestFailed;
    /**
     * @var Poem|null
     */
    public $originalPoem;

    /**
     * Create a new rule instance.
     *
     * @param $original_id
     */
    public function __construct($original_id = null) {
        $this->originalPoem    = $original_id ? Poem::find($original_id) : null;
        $this->_poetTestFailed = 0;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * @return bool false if failed
     */
    public function passes($attribute, $value): bool {
        if ($this->originalPoem) {
            // if(!$this->originalPoem->poet_id) {
            //     $this->poetTestFailed = 1;
            //     return false;
            // }

            // TODO 原作译作任意一个关联了作者，以此作者为准，更改所有译作的作者
            if (!empty($this->originalPoem->poet_id) && is_numeric($this->originalPoem->poet_id) && $this->originalPoem->poet_id !== $value) {
                $this->_poetTestFailed = 2;

                return false;
            }
        }

        if ($value === 'new') {
            return true;
        }

        return (bool) Author::find($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        if ($this->_poetTestFailed == 1) {
            return trans('error.Not a valid poet', [
                'reason' => trans('error.The translated form poem should link to a author page.')
            ]);
        }
        if ($this->_poetTestFailed == 2) {
            return trans('error.Not a valid poet', [
                'reason' => trans('error.Two poem should belong to same poet')
            ]);
        }

        return trans('error.Not a valid poet', [
            'reason' => ''
        ]);
    }
}
