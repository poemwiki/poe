<?php

namespace App\Http\Requests;

use App\Models\Poem;
use App\Rules\ValidOriginalLink;

// for web
class WebUpdatePoemRequest extends UpdatePoemRequest {
    /**
     * Get the validation rules that apply to the request.
     * TODO merge with App\Http\Requests\CreatePoemRequest::rules()
     * @return array
     */
    public function rules(): array {
        $rules                  = parent::rules();
        $poemIdToChange         = Poem::getIdFromFakeId($this->route('fakeId'));
        $changeToPoetId         = request()->input('poet_id');
        $rules['original_link'] = [new ValidOriginalLink($poemIdToChange, $changeToPoetId), 'nullable', 'string'];

        return $rules;
    }
}
