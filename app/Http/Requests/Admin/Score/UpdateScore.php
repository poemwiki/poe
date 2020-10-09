<?php

namespace App\Http\Requests\Admin\Score;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateScore extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('admin.score.edit', $this->score) || Gate::allows('web.score.update', $this->score);
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
            'factor' => ['sometimes', 'numeric'],
            'poem_id' => ['sometimes', 'integer'],
            'score' => ['sometimes', 'boolean'],
            'user_id' => ['sometimes', 'integer'],

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
