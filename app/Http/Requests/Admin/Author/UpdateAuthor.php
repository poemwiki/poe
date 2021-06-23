<?php

namespace App\Http\Requests\Admin\Author;

use App\Repositories\DynastyRepository;
use App\Repositories\NationRepository;
use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateAuthor extends TranslatableFormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('web.author.change', Auth::user());
    }

    /**
     * Get the validation rules that apply to the requests untranslatable fields.
     *
     * @return array
     */
    public function untranslatableRules(): array {
        return [
            // 'pic_url' => ['sometimes', 'string'],
            'user_id' => ['nullable', 'string'],
            'wikidata_id' => ['nullable', 'integer'],
            'nation_id' => ['nullable', Rule::in(NationRepository::ids())],
            'dynasty_id' => ['nullable', Rule::in(DynastyRepository::ids())],
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
    public function getSanitized(): array {
        $sanitized = $this->validated();


        //Add your code for manipulation with request data here

        return $sanitized;
    }
}
