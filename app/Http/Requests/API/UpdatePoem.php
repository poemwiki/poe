<?php

namespace App\Http\Requests\API;

use App\Http\Requests\UpdatePoemRequest;
use App\Repositories\AuthorRepository;
use App\Repositories\PoemRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UpdatePoem extends UpdatePoemRequest {
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
