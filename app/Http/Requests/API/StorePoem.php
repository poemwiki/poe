<?php

namespace App\Http\Requests\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePoemRequest;
use App\Repositories\AuthorRepository;
use App\Rules\NoDuplicatedPoem;
use App\Rules\ValidTranslatorId;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * For API request
 * Class StoreOwnerUploaderPoem.
 */
class StorePoem extends CreatePoemRequest {
    public $poemMinLength = 10;

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
        $rules         = parent::rules();
        $rules['poem'] = [new NoDuplicatedPoem(null, 'id'), 'required', 'string', 'min:' . $this->poemMinLength];

        return $rules;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException|HttpResponseException
     */
    protected function failedValidation(Validator $validator) {
        $failedRules = $validator->failed();
        if (isset($failedRules['poem']['App\Rules\NoDuplicatedPoem'])) {
            $messages = $validator->getMessageBag()->getMessages();

            throw new HttpResponseException(response()->json(['message' => '与已有诗歌重复', 'id' => $messages['poem'][0], 'errors' => $messages, /* TODO NoDuplicatedPoem validation should be in controller */ 'code' => Controller::$CODE['duplicated']]));
        }

        if (isset($failedRules['poem']['Min'])) {
            $messages = $validator->getMessageBag()->getMessages();

            throw new HttpResponseException(response()->json(['message' => $messages['poem'], 'errors' => $messages, /* TODO NoDuplicatedPoem validation should be in controller */ 'code' => Controller::$CODE['invalid_poem_length']]));
        }

        parent::failedValidation($validator);
    }

    /**
     * Modify input data.
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        $sanitized['upload_user_id'] = Auth::user()->id;

        if (is_null($sanitized['original_id'])) {
            $sanitized['original_id'] = 0;
        }

        if (isset($sanitized['translator_ids'])) {
            $sanitized['translator']  = '';

            $translatorsOrder = [];
            foreach ($sanitized['translator_ids'] as $key => $id) {
                if (ValidTranslatorId::isWikidataQID($id)) {
                    $translatorAuthor                  = $this->authorRepository->getExistedAuthor(ltrim($id, 'Q'));
                    $sanitized['translator_ids'][$key] = $translatorAuthor->id;
                    $translatorsOrder[]                = $translatorAuthor->id;

                    continue;
                }

                if (ValidTranslatorId::isNew($id)) {
                    $sanitized['translator_ids'][$key] = mb_substr($id, strlen('new_'));
                    $translatorsOrder[]                = substr($id, 4, strlen($id));
                } else {
                    $sanitized['translator_ids'][$key] = (int) $id;
                    $translatorsOrder[]                = $id;

                    $userAuthor = Auth::user()->author;
                    if ($key === 0 && $userAuthor && $userAuthor->id === $id) {
                        $sanitized['is_owner_uploaded'] = \App\Models\Poem::$OWNER['translatorUploader'];
                    }
                }
            }

            // WARNING for poems have related translator(relatable record), poem.translator is just for indicating translator order
            if (!empty($translatorsOrder)) {
                $sanitized['is_original'] = false;
                $sanitized['translator'] = json_encode($translatorsOrder, JSON_UNESCAPED_UNICODE);
            }
        }

        return $sanitized;
    }
}
