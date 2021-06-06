<?php

namespace App\Http\Requests\Admin\Poem;

use App\Http\Requests\UpdatePoemRequest;
use App\Rules\ValidOriginalLink;

// for web
class UpdatePoem extends UpdatePoemRequest {
    /**
     * Get the validation rules that apply to the request.
     * TODO merge with App\Http\Requests\CreatePoemRequest::rules()
     * @return array
     */
    public function rules(): array {
        $rules = parent::rules();
        $rules['original_link'] = [new ValidOriginalLink, 'sometimes', 'string'];
        return $rules;
    }
}
