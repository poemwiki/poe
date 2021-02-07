<?php

namespace App\Http\Requests;

use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CreateScoreRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('api.score.create', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'poem_id' => ['required', 'integer', 'exists:' . \App\Models\Poem::class . ',id'],
        ];
    }

    /**
     * Modify input data
     *
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        $user = Auth::user();
        $sanitized['user_id'] = $user->id;
        $sanitized['weight'] = $user->is_v ? 100 : 1;

        return $sanitized;
    }
}
