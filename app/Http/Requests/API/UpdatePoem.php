<?php

namespace App\Http\Requests\API;

use App\Http\Requests\UpdatePoemRequest;
use App\Repositories\AuthorRepository;
use App\Repositories\PoemRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UpdatePoem extends UpdatePoemRequest {
    protected function prepareForValidation(): void {
        $translatorIds = $this->input('translator_ids');
        if (!is_array($translatorIds)) {
            return;
        }

        $this->merge([
            'translator_ids' => array_map(function ($translatorId) {
                if (!is_string($translatorId) || $translatorId === '' || is_numeric($translatorId)) {
                    return $translatorId;
                }

                if (Str::startsWith($translatorId, ['new_', 'Q'])) {
                    return $translatorId;
                }

                // Prefix bare strings so the shared ValidTranslatorId rule accepts them.
                // UpdatePoemRequest::getSanitized() strips the prefix before persisting.
                return 'new_' . $translatorId;
            }, $translatorIds),
        ]);
    }

    public function rules(): array {
        $rules = parent::rules();

        unset(
            $rules['bedtime_post_id'],
            $rules['bedtime_post_title'],
            $rules['need_confirm'],
            $rules['poet_wikidata_id'],
            $rules['translator_wikidata_id']
        );

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     * For an existing poem, enforce the same `api.poem.update` gate used by the API endpoint.
     *
     * If `idOrFakeId` cannot be resolved, we still allow authenticated requests to continue
     * so the controller can return the normal `Poem not found` API payload instead of failing
     * early as a generic authorization error.
     */
    public function authorize(AuthorRepository $authorRepository): bool {
        $this->authorRepository = $authorRepository;
        $this->_poemToChange    = app(PoemRepository::class)->findPoemByIdOrFakeId($this->route('idOrFakeId'));

        if (!$this->_poemToChange) {
            return Auth::check();
        }

        return Gate::allows('api.poem.update', $this->_poemToChange);
    }
}
