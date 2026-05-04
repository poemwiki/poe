<?php

namespace App\Http\Requests;

use App\Models\Author;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Rules\ValidPoetId;
use App\Rules\ValidTranslatorId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePoemRequest extends FormRequest {
    /**
     * @var AuthorRepository
     */
    protected $authorRepository;
    protected $_poemToChange;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @param AuthorRepository $authorRepository
     * @return bool
     */
    public function authorize(AuthorRepository $authorRepository): bool {
        $this->authorRepository = $authorRepository;
        $this->_poemToChange    = Poem::find(Poem::getIdFromFakeId($this->route('fakeId')));

        return Gate::allows('web.poem.change', $this->_poemToChange);
    }

    public function getPoemToChange(): ?Poem {
        return $this->_poemToChange;
    }

    /**
     * Get the validation rules that apply to the request.
     * TODO merge with App\Http\Requests\CreatePoemRequest::rules().
     * @return array
     */
    public function rules(): array {
        $original_id = request()->input('original_id');

        return [
            'title'              => ['nullable', 'string'],
            'language_id'        => Rule::in(LanguageRepository::ids()),
            'is_original'        => ['nullable', 'boolean'],
            'original_id'        => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ((int) $value === 0) {
                    return;
                }

                if (!Poem::query()->whereKey($value)->exists()) {
                    $fail(trans('validation.exists', ['attribute' => $attribute]));
                }
            }],
            'poet'               => ['nullable', 'string'],
            'poet_cn'            => ['nullable', 'string'],
            'bedtime_post_id'    => ['nullable', 'integer'],
            'bedtime_post_title' => ['nullable', 'string'],
            'poem'               => ['nullable', 'string'],
            'length'             => ['nullable', 'integer'],
            'translator'         => ['nullable', 'string'],
            'from'               => ['nullable', 'string'],
            'year'               => ['nullable', 'string'],
            'month'              => ['nullable', 'string'],
            'date'               => ['nullable', 'string'],
            'location'           => ['nullable', 'string'],
            'dynasty'            => ['nullable', 'string'],
            'nation'             => ['nullable', 'string'],
            'need_confirm'       => ['nullable', 'boolean'],
            'is_lock'            => ['sometimes', 'boolean'],
            'content_id'         => ['nullable', 'integer'],

            'preface'                => ['nullable', 'string', 'max:10000'],
            'subtitle'               => ['nullable', 'string', 'max:128'],
            'genre_id'               => ['nullable', 'exists:' . \App\Models\Genre::class . ',id'],
            'poet_id'                => ['nullable', new ValidPoetId($original_id)],
            'poet_wikidata_id'       => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
            'translator_ids'         => ['nullable', 'array', new ValidTranslatorId()],
            'translator_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
        ];
    }

    /**
     * Modify input data.
     *
     * @return array
     */
    public function getSanitized(): array {
        $sanitized = $this->validated();

        // TODO if poet_id starts with new_, create new author
        // 由于前端用户可能在未加载全部搜索结果的情况下，点选新建的作者名，造成重复创建 Author，
        // 故此处暂时不创建新作者，不写入 poem.poet_id,
        // 只将作者名写入 poem.poet
        if (isset($sanitized['poet_id']) && $sanitized['poet_id'] === 'new') {
            $sanitized['poet_id']          = null;
            $sanitized['poet_wikidata_id'] = null;
        }
        if (isset($sanitized['translator_id']) && $sanitized['translator_id'] === 'new') {
            $sanitized['translator_id']          = null;
            $sanitized['translator_wikidata_id'] = null;
        }

        // 原创作品不允许更改作者，只允许选择当前用户关联的作者作为 poet_id
        if (in_array($this->_poemToChange->is_owner_uploaded, [Poem::$OWNER['uploader'], Poem::$OWNER['poetAuthor']])) {
            $sanitized['poet_id']          = $this->_poemToChange->poet_id;
            $sanitized['poet_wikidata_id'] = $this->_poemToChange->poet_wikidata_id;
        }
        // TODO 原创译作不允许更改译者，只允许选择当前用户关联的作者作为 translator_id
        // if(in_array($this->_poemToChange->is_owner_uploaded, [Poem::$OWNER['translatorUploader'], Poem::$OWNER['translatorAuthor']])) {
        //     $sanitized['translator_ids'] = ;
        // }
        // TODO 原创共有译作（多译者中有一个为用户关联的作者）

        if (isset($sanitized['translator_ids'])) {
            $sanitized['translator'] = '';

            $translatorsOrder = [];
            foreach ($sanitized['translator_ids'] as $key => $id) {
                if (ValidTranslatorId::isWikidataQID($id)) {
                    $translatorAuthor                  = $this->authorRepository->getExistedAuthor(ltrim($id, 'Q'));
                    $sanitized['translator_ids'][$key] = $translatorAuthor->id;
                    $translatorsOrder[]                = $translatorAuthor->id;
                } elseif (ValidTranslatorId::isNew($id)) {
                    $sanitized['translator_ids'][$key] = mb_substr($id, strlen('new_'));
                    $translatorsOrder[]                = substr($id, 4, strlen($id));
                } else {
                    $sanitized['translator_ids'][$key] = (int) $id;
                    $translatorsOrder[]                = $id;
                }
            }
            $sanitized['translator'] = json_encode($translatorsOrder, JSON_UNESCAPED_UNICODE);
        }

        $sanitized = $this->normalizeOriginalRelation($sanitized);

        if (isset($sanitized['title'])) {
            $sanitized['title'] = Str::trimSpaces($sanitized['title']);
        }
        if (isset($sanitized['subtitle']) && $sanitized['subtitle']) {
            $sanitized['subtitle'] = Str::trimSpaces($sanitized['subtitle']);
        }
        if (isset($sanitized['preface']) && $sanitized['preface']) {
            $sanitized['preface'] = Str::trimSpaces($sanitized['preface']);
        }

        return $sanitized;
    }

    protected function normalizeOriginalRelation(array $sanitized): array {
        $hasIsOriginal = array_key_exists('is_original', $sanitized);
        $isOriginal    = $hasIsOriginal ? (bool) $sanitized['is_original'] : null;
        $originalLink  = $sanitized['original_link'] ?? null;

        if ($hasIsOriginal && $isOriginal === false && empty($originalLink) && !isset($sanitized['original_id'])) {
            $sanitized['original_id'] = 0;
        }

        return $sanitized;
    }
}
