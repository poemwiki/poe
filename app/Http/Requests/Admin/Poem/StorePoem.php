<?php

namespace App\Http\Requests\Admin\Poem;

use App\Http\Requests\CreatePoemRequest;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Rules\ValidPoetId;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * web store poem request
 * Class StorePoem
 * @package App\Http\Requests\Admin\Poem
 */
class StorePoem extends CreatePoemRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        // return true;
        return Gate::allows('web.poem.create', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    // public function rules(): array {
    //     $rules = parent::rules();
    //     return $rules;
    // }
}
