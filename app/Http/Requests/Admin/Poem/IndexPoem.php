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
            'orderBy' => 'in:id,title,language,is_original,poet,poet_cn,bedtime_post_id,bedtime_post_title,length,translator,from,year,month,date,dynasty,nation,need_confirm,is_lock,content_id|nullable',
            'orderDirection' => 'in:asc,desc|nullable',
            'search' => 'string|nullable',
            'page' => 'integer|nullable',
            'per_page' => 'integer|nullable',

        ];
    }
}
