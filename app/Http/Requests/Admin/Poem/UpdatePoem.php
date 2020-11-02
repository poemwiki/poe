<?php

namespace App\Http\Requests\Admin\Poem;

use App\Models\Language;
use App\Models\Poem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\ValidationRules\Rules\ModelsExist;

class UpdatePoem extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.poem.edit', $this->poem) || Gate::allows('web.poem.change', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => ['integer'],
            'title' => ['nullable', 'string'],
            'language' => Rule::in(Language::ids()),
            'is_original' => ['nullable', 'boolean'],
            'poet' => ['nullable', 'string'],
            'poet_cn' => ['nullable', 'string'],
            'bedtime_post_id' => ['nullable', 'integer'],
            'bedtime_post_title' => ['nullable', 'string'],
            'poem' => ['nullable', 'string'],
            'length' => ['nullable', 'integer'],
            'translator' => ['nullable', 'string'],
            'from' => ['nullable', 'string'],
            'year' => ['nullable', 'string'],
            'month' => ['nullable', 'string'],
            'date' => ['nullable', 'string'],
            'dynasty' => ['nullable', 'string'],
            'nation' => ['nullable', 'string'],
            'need_confirm' => ['nullable', 'boolean'],
            'is_lock' => ['sometimes', 'boolean'],
            'content_id' => ['nullable', 'integer'],
            'original_id' => ['nullable', 'integer', new ModelsExist(Poem::class, 'id')],
            'preface' => ['nullable', 'string', 'max:64'],
            'subtitle' => ['nullable', 'string', 'max:32']
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
