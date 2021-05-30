<?php

namespace App\Http\Requests\Admin\Poem;

use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Rules\NoDuplicatedPoem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePoem extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('admin.poem.edit', $this->poem) || Gate::allows('web.poem.change', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     * TODO merge with App\Http\Requests\CreatePoemRequest::rules()
     * @return array
     */
    public function rules(): array {
        return [
            'title' => ['nullable', 'string'],
            'language_id' => Rule::in(LanguageRepository::ids()),
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
            'location' => ['nullable', 'string'],
            'dynasty' => ['nullable', 'string'],
            'nation' => ['nullable', 'string'],
            'need_confirm' => ['nullable', 'boolean'],
            'is_lock' => ['sometimes', 'boolean'],
            'content_id' => ['nullable', 'integer'],
            'original_id' => ['nullable', 'integer', 'exists:' . \App\Models\Poem::class . ',id'],

            'preface' => ['nullable', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:128'],
            'genre_id' => ['nullable', 'exists:' . \App\Models\Genre::class . ',id'],
            'poet_id' => ['nullable', Rule::in(array_merge(AuthorRepository::ids()->toArray(), ['new']))],
            'poet_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
            'translator_id' => ['nullable', Rule::in(array_merge(AuthorRepository::ids()->toArray(), ['new']))],
            'translator_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
        ];
    }

    /**
     * Modify input data
     *
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        // TODO if poet_id starts with new_, create new author
        // 由于前端用户可能在未加载全部搜索结果的情况下，点选新建的作者名，造成重复创建 Author，
        // 故此处暂时不创建新作者，不写入 poem.poet_id,
        // 只将作者名写入 poem.poet
        if (isset($sanitized['poet_id']) && $sanitized['poet_id'] === 'new') {
            $sanitized['poet_id'] = null;
            $sanitized['poet_wikidata_id'] = null;
        }
        if (isset($sanitized['translator_id']) && $sanitized['translator_id'] === 'new') {
            $sanitized['translator_id'] = null;
            $sanitized['translator_wikidata_id'] = null;
        }

        //Add your code for manipulation with request data here

        return $sanitized;
    }
}
