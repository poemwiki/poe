<?php

namespace App\Http\Requests\Admin\Poem;

use App\Http\Requests\CreatePoemRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StorePoem extends CreatePoemRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return Gate::allows('admin.poem.create') || Gate::allows('web.poem.change', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return parent::rules();
    }
}
