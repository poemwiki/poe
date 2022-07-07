<?php

namespace App\Http\Requests\Admin\Score;

use App\Models\Score;
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
        return Gate::allows('admin.score.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'poem_id' => ['required', Rule::unique('score', 'poem_id'), 'string'],
            'score' => ['required', Rule::in(Score::$SCORE)],
            'user_id' => ['required', Rule::unique('score', 'user_id'), 'string'],
            'weight' => ['required', 'numeric'],

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
