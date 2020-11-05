<?php

namespace App\Http\Requests\Admin\Poem;

use App\Models\Genre;
use App\Models\Language;
use App\Models\Poem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\ValidationRules\Rules\ModelsExist;

class StorePoem extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('admin.poem.create') || Gate::allows('web.poem.change', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'title' => ['nullable', 'string'],
            'language_id' => Rule::in(Language::ids()),
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
            'is_lock' => ['nullable', 'boolean'],
            'content_id' => ['nullable', 'integer'],
            'original_id' => ['nullable', 'integer', 'exists:'.\App\Models\Poem::class.',id'],
            'translated_id' => ['nullable', 'integer', 'exists:'.\App\Models\Poem::class.',id'],
            'genre_id' => ['nullable', Rule::in(Genre::ids())],
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
