<?php

namespace App\Http\Requests;

use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Rules\NoDuplicatedPoem;
use App\Rules\ValidPoetId;
use App\Rules\ValidTranslatorId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreatePoemRequest extends FormRequest {
    /**
     * @var AuthorRepository
     */
    protected $authorRepository;
    public $poemMinLength = 2;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @param AuthorRepository $authorRepository
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
        $original_id = request()->input('original_id');

        return [
            'title'              => ['required', 'string'],
            'language_id'        => Rule::in(LanguageRepository::ids()),
            'is_original'        => ['nullable', 'boolean'],
            'poet'               => ['nullable', 'string'],
            'poet_cn'            => ['nullable', 'string'],
            'bedtime_post_id'    => ['nullable', 'integer'],
            'bedtime_post_title' => ['nullable', 'string'],
            'poem'               => [new NoDuplicatedPoem(null), 'required', 'string', 'min:' . $this->poemMinLength],
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
            'is_lock'            => ['nullable', 'boolean'],
            'content_id'         => ['nullable', 'integer'],
            'original_id'        => ['nullable', 'integer', 'exists:' . \App\Models\Poem::class . ',id'],
            '#translated_id'     => ['nullable', 'integer', 'exists:' . \App\Models\Poem::class . ',id'], // TODO use fake ID here
            'preface'            => ['nullable', 'string', 'max:300'],
            'subtitle'           => ['nullable', 'string', 'max:128'],
            'genre_id'           => ['nullable', 'exists:' . \App\Models\Genre::class . ',id'],
            'poet_id'            => ['nullable', new ValidPoetId($original_id)],
            'poet_wikidata_id'   => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
            'translator_ids'     => ['nullable', 'array', new ValidTranslatorId()],
            // 'translators' = [],
            'translator_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
            'upload_user_id'         => ['nullable', 'exists:' . \App\User::class . ',id'],
            'is_owner_uploaded'      => ['required', Rule::in([Poem::$OWNER['none'], Poem::$OWNER['uploader'], Poem::$OWNER['translatorUploader']])],
            'tag_id'                 => ['nullable', 'exists:' . \App\Models\Tag::class . ',id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'poem.min' => '诗歌正文至少 ' . $this->poemMinLength . '个字。'
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

        if (!isset($sanitized['original_id'])) {
            $sanitized['original_id'] = 0;
        }

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
            // WARNING for poems have related translator(relatable record), poem.translator is just for indicating translator order
            if (!empty($translatorsOrder)) {
                $sanitized['translator'] = json_encode($translatorsOrder, JSON_UNESCAPED_UNICODE);
            }
        }

        $user = Auth::user();
        // 原创作品不允许更改作者，只允许选择关联作者或只填入名字
        // TODO 诗歌编辑页，原创作品模式下只允许选择当前用户关联的作者作为poet_id
        // if($sanitized['is_poet_uploaded']) {
        //     $sanitized['poet_id'] = null;
        //     $sanitized['poet_wikidata_id'] = null;
        // }
        // // 原创译作不允许更改译者，只允许选择关联译者或只填入名字
        // // TODO 诗歌编辑页，原创译作模式下只允许选择当前用户关联的作者作为translator_id
        // if($sanitized['is_translator_uploaded']) {
        //     $sanitized['translator_id'] = null;
        //     $sanitized['translator_wikidata_id'] = null;
        // }

        $sanitized['upload_user_id'] = $user->id;

        $sanitized['title'] = Str::trimSpaces($sanitized['title']);
        if (isset($sanitized['subtitle'])) {
            $sanitized['subtitle'] = Str::trimSpaces($sanitized['subtitle']);
        }
        if (isset($sanitized['preface'])) {
            $sanitized['preface'] = Str::trimSpaces($sanitized['preface']);
        }

        return $sanitized;
    }
}
