<?php

namespace App\Http\Requests\Admin\Author;

use Brackets\Translatable\TranslatableFormRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexAuthor extends TranslatableFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.author.index');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'orderBy' => 'in:updated_at,user_id,wikidata_id,authorUser.name,uploader.name,describe_lange,id,name_lang,pic_url,wikipedia_url|nullable',
            'orderDirection' => 'in:asc,desc|nullable',
            'search' => 'string|nullable',
            'page' => 'integer|nullable',
            'per_page' => 'integer|nullable',
        ];
    }
}
