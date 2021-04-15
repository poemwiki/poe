<?php

namespace App\Http\Requests;

use App\Models\Poem;
use App\Models\Score;
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
            'score' => ['required', 'integer', Rule::in(Score::$SCORE)], // TODO it should be named as "rating"
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

        $isMaster = false;
        $tags = Poem::find($sanitized['poem_id'])->tags;
        if($tags->count() && $tags[0] && $tags[0]->campaign) {
            $campaign = $tags[0]->campaign;
            $isMaster = in_array($user->id, $campaign->settings['masters']);
        }

        // TODO weight should from user.weight
        $sanitized['weight'] = $isMaster ? max(100, $user->weight) : $user->weight;

        return $sanitized;
    }
}
