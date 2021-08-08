<?php

namespace App\Http\Requests;

use App\Models\Author;
use App\Models\Poem;
use App\Repositories\AuthorRepository;
use App\Repositories\LanguageRepository;
use App\Rules\NoDuplicatedPoem;
use App\Rules\ValidPoetId;
use App\Rules\ValidTranslatorId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePoemRequest extends FormRequest {
    /**
     * @var AuthorRepository $authorRepository
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
        $this->_poemToChange = Poem::find(Poem::getIdFromFakeId($this->route('fakeId')));
        return Gate::allows('web.poem.change', $this->_poemToChange);
    }

    /**
     * Get the validation rules that apply to the request.
     * TODO merge with App\Http\Requests\CreatePoemRequest::rules()
     * @return array
     */
    public function rules(): array {
        $original_id = request()->input('original_id');

        return [
            'title' => ['nullable', 'string'],
            'language_id' => Rule::in(LanguageRepository::ids()),
            'is_original' => ['nullable', 'boolean'],
            'poet' => ['nullable', 'string'],
            'poet_cn' => ['nullable', 'string'],
            'bedtime_post_id' => ['nullable', 'integer'],
            'bedtime_post_title' => ['nullable', 'string'],
            'poem' => ['nullable', 'string'],
            'length' => ['nullable', 'integer'],
            'translator' => ['nullable', 'string'],
            'from' => ['nullable', 'string'],
            'year' => ['nullable', 'string'],
            'month' => ['nullable', 'string'],
            'date' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'dynasty' => ['nullable', 'string'],
            'nation' => ['nullable', 'string'],
            'need_confirm' => ['nullable', 'boolean'],
            'is_lock' => ['sometimes', 'boolean'],
            'content_id' => ['nullable', 'integer'],
            // 'original_id' => ['nullable', 'integer', 'exists:' . \App\Models\Poem::class . ',id'],

            'preface' => ['nullable', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:128'],
            'genre_id' => ['nullable', 'exists:' . \App\Models\Genre::class . ',id'],
            'poet_id' => ['nullable', new ValidPoetId($original_id)],
            'poet_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
            'translator_ids' => ['nullable', 'array', new ValidTranslatorId()],
            'translator_wikidata_id' => ['nullable', 'exists:' . \App\Models\Wikidata::class . ',id'],
        ];
    }

    /**
     * Modify input data
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
            $sanitized['poet_id'] = null;
            $sanitized['poet_wikidata_id'] = null;
        }
        if (isset($sanitized['translator_id']) && $sanitized['translator_id'] === 'new') {
            $sanitized['translator_id'] = null;
            $sanitized['translator_wikidata_id'] = null;
        }

        // 原创作品不允许更改作者，只允许选择当前用户关联的作者作为 poet_id
        if(in_array($this->_poemToChange->is_owner_uploaded, [Poem::$OWNER['uploader'], Poem::$OWNER['poetAuthor']])) {
            $sanitized['poet_id'] = $this->_poemToChange->poet_id;
            $sanitized['poet_wikidata_id'] = $this->_poemToChange->poet_wikidata_id;
        }
        // TODO 原创译作不允许更改译者，只允许选择当前用户关联的作者作为 translator_id
        // if(in_array($this->_poemToChange->is_owner_uploaded, [Poem::$OWNER['translatorUploader'], Poem::$OWNER['translatorAuthor']])) {
        //     $sanitized['translator_ids'] = ;
        // }
        // TODO 原创共有译作（多译者中有一个为用户关联的作者）



        if (isset($sanitized['translator_ids'])) {
            $sanitized['translator'] = '';

            $translatorLabels = [];
            foreach ($sanitized['translator_ids'] as $key => $id) {
                if(ValidTranslatorId::isWikidataQID($id)) {
                    $translatorAuthor = $this->authorRepository->getExistedAuthor(ltrim($id, 'Q'));
                    $sanitized['translator_ids'][$key] = $translatorAuthor->id;
                    $translatorLabels[] = $translatorAuthor->label;
                } else if(ValidTranslatorId::isNew($id)) {
                    $sanitized['translator_ids'][$key] = mb_substr($id, strlen('new_'));
                    $translatorLabels[] = substr($id, 4, strlen($id));
                } else {
                    $sanitized['translator_ids'][$key] = (int)$id;
                    $translatorLabels[] = Author::find($id)->label;
                }
            }
            $sanitized['translator'] = implode(', ', $translatorLabels);

        }


        // 译作提交空 original_link 视为取消 original_id 链接
        if(!$sanitized['is_original'] && empty($sanitized['original_link'])) {
            $sanitized['original_id'] = 0;
        }

        // TODO 添加测试：原作改为译作，未提交 original_link 时，original_id 应为0；提交 original_link 时，original_id 应为对应的 poem.id

        // TODO 添加测试：译作改为原作，不管是否有 original_link 提交，都应将 original_id 置为自身的 poem.id

        return $sanitized;
    }
}
