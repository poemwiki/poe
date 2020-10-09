<?php

namespace App\Http\Requests\Admin\Score;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreScore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.score.create') || Gate::allows('web.score.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'content_id' => ['nullable', 'integer'],
            'factor' => ['required', 'numeric'],
            'poem_id' => ['required', 'integer'],
            'score' => ['required', 'boolean'],
            'user_id' => ['required', 'integer'],

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
