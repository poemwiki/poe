<?php

namespace App\Http\Requests\Admin\Poem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexPoem extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('admin.poem.index') || Gate::allows('web.posts.index');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'orderBy' => 'in:updated_at,created_at,uploader.name,campaign.id,title,id,language_id,nation,is_original,nation_id,poet, poet_cn,translator,poet_id, translator_id|nullable',
            'orderDirection' => 'in:desc,asc|nullable',
            'search' => 'string|nullable',
            'page' => 'integer|nullable',
            'per_page' => 'integer|nullable',
        ];
    }
}
