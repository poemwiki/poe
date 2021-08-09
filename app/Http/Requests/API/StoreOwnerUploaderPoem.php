<?php

namespace App\Http\Requests\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePoemRequest;
use App\Repositories\AuthorRepository;
use App\Rules\NoDuplicatedPoem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * For API request
 * Class StoreOwnerUploaderPoem
 * @package App\Http\Requests\API
 */
class StoreOwnerUploaderPoem extends CreatePoemRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(AuthorRepository $authorRepository): bool {
        $this->authorRepository = $authorRepository;
        return Gate::allows('api.poem.create', Auth::user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        $rules = parent::rules();
        $rules['poem'] = [new NoDuplicatedPoem(null, 'id'), 'required', 'string'];
        return $rules;
    }


    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException|HttpResponseException
     */
    protected function failedValidation(Validator $validator) {
        $failedRules = $validator->failed();
        if(isset($failedRules['poem']['App\Rules\NoDuplicatedPoem'])) {
            $messages = $validator->getMessageBag()->getMessages();
            throw new HttpResponseException(response()->json([
                'message' => '与已有诗歌重复',
                'id' => $messages['poem'][0],
                'errors' => $messages,
                // TODO NoDuplicatedPoem validation should be in controller
                'code' => Controller::$CODE['duplicated']
            ]));
        }

        parent::failedValidation($validator);
    }
    /**
     * Modify input data
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        $sanitized['upload_user_id'] = Auth::user()->id;

        if (!isset($sanitized['original_id'])) {
            $sanitized['original_id'] = 0;
        }

        return $sanitized;
    }
}
