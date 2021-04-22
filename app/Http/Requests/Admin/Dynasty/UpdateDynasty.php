<?php

namespace App\Http\Requests\Admin\Dynasty;

use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDynasty extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.dynasty.edit', $this->dynasty);
    }

/**
     * Get the validation rules that apply to the requests untranslatable fields.
     *
     * @return array
     */
    public function untranslatableRules(): array {
        return [
            'f_id' => ['sometimes', 'string'],
            'name' => ['sometimes', Rule::unique('dynasty', 'name')->ignore($this->dynasty->getKey(), $this->dynasty->getKeyName()), 'string'],
            'wikidata_id' => ['nullable', 'integer'],


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
            'name_lang' => ['sometimes', 'string'],

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

        return $sanitized;
    }
}
