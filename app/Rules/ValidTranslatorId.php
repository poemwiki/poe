<?php

namespace App\Rules;

use App\Models\Author;
use App\Models\Poem;
use App\Models\Wikidata;
use App\Repositories\PoemRepository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;

class ValidTranslatorId implements Rule {
    /**
     * @var int
     */
    public $reason = '';

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
        foreach ($value as $id) {
            if(!$this->_pass($id)) {
                // $this->reason .= $id;
                return false;
            }
        }

        return true;
    }

    public static function isNew($value) {
        return starts_with($value, 'new_');
    }
    public static function isWikidataQID($value) {
        if(starts_with($value, 'Q')) {
            $wikidataId = substr($value, 1, strlen($value));
            if (is_numeric($wikidataId)) {
                return !!Wikidata::find($wikidataId);
            }
        }
        return false;
    }

    public function _pass($value):bool {
        if(self::isNew($value) or self::isWikidataQID($value)) {
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
            'reason' => $this->reason
        ]);
    }
}
