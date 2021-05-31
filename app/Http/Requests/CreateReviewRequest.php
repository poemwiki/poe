<?php

namespace App\Http\Requests;

use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CreateReviewRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('api.review.create', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'title' => ['nullable', 'string', 'max:64'],
            'content' => ['required', 'string', 'max:1000'],
            'poem_id' => ['required', 'integer', 'exists:' . \App\Models\Poem::class . ',id'],
            'reply_id' => ['nullable', 'integer', 'exists:' . \App\Models\Review::class . ',id'],
        ];
    }

    /**
     * Modify input data
     *
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        $sanitized['user_id'] = Auth::user()->id;

        return $sanitized;
    }
}
