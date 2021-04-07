<?php

namespace App\Http\Requests\Admin\Score;

use App\Models\Score;
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
        return Gate::allows('admin.score.edit', $this->score);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'poem_id' => ['sometimes', Rule::unique('score', 'poem_id')->ignore($this->score->getKey(), $this->score->getKeyName()), 'string'],
            'score' => ['sometimes', Rule::in(Score::$SCORE)],
            'user_id' => ['sometimes', Rule::unique('score', 'user_id')->ignore($this->score->getKey(), $this->score->getKeyName()), 'string'],
            'weight' => ['sometimes', 'numeric'],

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
