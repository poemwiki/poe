<?php

namespace App\Http\Requests\Admin\Author;

use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreAuthor extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('web.author.change', Auth::user());
    }

/**
     * Get the validation rules that apply to the requests untranslatable fields.
     *
     * @return array
     */
    public function untranslatableRules(): array {
        return [
            'pic_url' => ['nullable', 'string'],
            'user_id' => ['nullable', 'string'],
            'wikidata_id' => ['nullable', 'integer'],
            'wikidata_url' => ['nullable', 'string'],

        ];
    }

    /**
     * Get the validation rules that apply to the requests translatable fields.
     *
     * @return array
     */
    public function translatableRules($locale): array {
        return [
            'describe_lang' => ['nullable', 'string'],
            'name_lang' => ['nullable', 'string'],

        ];
    }

    /**
    * Modify input data
    *
    * @return array
    */
    public function getSanitized(): array
    {
        $sanitized = $this->validated();

        //Add your code for manipulation with request data here
        $sanitized['upload_user_id'] = Auth::user()->id;

        return $sanitized;
    }
}
