<?php

namespace App\Models;

use App\Repositories\ScoreRepository;
use App\Repositories\PoemRepository;
use App\Traits\HasFakeId;
use App\Traits\RelatableNode;
use App\User;
use Carbon\Traits\Date;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Poem.
 *
 * @property mixed                               $original_id
 * @property mixed                               $translatedPoems
 * @property mixed                               $id
 * @property string                              $poetLabel
 * @property string                              $poet_label
 * @property string                              $poet_label_cn
 * @property string                              $translator          For poems have related translator(relatable record), poem.translator is a json string just for indicating translator $order
 * @property int                                 $is_owner_uploaded
 * @property User                                $uploader
 * @property Illuminate\Support\Collection|Tag[] $tags
 * @property User|null                           $owner
 * @property mixed                               $translator_label_cn
 * @property Poem                                $originalPoem
 * @property bool                                $is_translated
 * @property int                                 $poet_id
 * @property Author|null                         $poetAuthor
 * @property User|null                           $poetUser
 * @property bool                                $poet_is_v
 * @property int                                 $campaign_id
 * @property array                               $scoreArray
 * @property string                              $firstLine
 * @property Date                                $updated_at
 * @property mixed                               $activityLogs
 * @property NFT                                 $nft
 * @property int                                 $flag
 * @property string                              $poem
 * @property string                              $translatorsStr
 * @property string                              $title
 * @property string                              $subtitle
 * @property string                              $preface
 */
class Poem extends Model {
    use SoftDeletes;
    use LogsActivity;
    use HasFakeId;
    use RelatableNode;
    use Searchable;

    /**DO NOT CHANGE FAKEID STATICS**/
    public static $FAKEID_KEY    = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    public static $FAKEID_SPARSE = 96969696969;
    /**DO NOT CHANGE FAKEID STATICS**/

    public static array $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length', 'score', 'share_pics', 'short_url', 'poet_wikidata_id', 'translator_wikidata_id'];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept(self::$ignoreChangedAttributes);
    }

    public static $OWNER = [
        'none'                    => 0, // 上传时未标注原创，此时应以 poetAuthor 为作者 owner，以 translatorAuthor 为译者 owner
        'uploader'                => 1, // 作者用户上传原创原作，此时以 upload_user_id 代表作者 owner
        'translatorUploader'      => 2, // 译者用户上传原创译作，且为单译者情况，此时以 upload_user_id 代表译者 owner
        'poetAuthor'              => 3, // 上传时未标注原创，后来被作者认领且标注原创，upload_user_id 不代表作者 owner，只代表上传人，此时应以 poetAuthor 为作者 owner
        'translatorAuthor'        => 4, // 上传时未标注原创译作，后来被译者认领且标注原创，upload_user_id 不代表译者 owner，只代表上传人，此时应以 translatorAuthor或唯一的related translator 为译者 owner
        'multiTranslatorUploader' => 5, // 译者之一的用户上传，且为多译者情况，此时以 related translator 为共有译者 owner
        'multiTranslatorAuthor'   => 6, // 上传时未标注原创译作，后来被译者中的任何一个认领且标注原创，upload_user_id 不代表译者 owner，只代表上传人，此时应以 related translator 为共有译者 owner
    ];

    // 标记流程：标记、修改、复审、去标记
    // 编辑流程：标记、编辑、去标记
    // TODO “我是作者”：触发关联认证流程
    public static $FLAG = [
        'none'                   => 0,
        'hideByAuthor'           => 1 << 1,  // 原作者要求不显示他的诗歌或作者条目：触发标记流程
        'infoNeedConfirm'        => 1 << 2,  // 诗作信息有误（作者、译者错误，正文有错别字等）：触发标记流程
        'originalNeedConfirm'    => 1 << 3,  // 原创标注有争议
        'botContentNeedConfirm'  => 1 << 4,  // 由机器人导入的内容，待人工审核确认
        'hideByModerator1'       => 1 << 10, // 涉政敏感内容：触发标记流程
        'hideByModerator2'       => 1 << 11, // 涉黄内容：触发标记流程
        'hideByModerator3'       => 1 << 12, // 涉暴内容：触发标记流程
    ];

    protected $table = 'poem';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    // default value for attributes
    protected $attributes = [
        'language_id'       => 1,
        'is_original'       => 1,
        'is_owner_uploaded' => 0,
    ];

    public $fillable = [
        'title',
        'language_id',
        'is_original',
        'poet',
        'poet_cn',
        'bedtime_post_id',
        'bedtime_post_title',
        'poem',
        //        'length',
        'translator',
        'from',
        'year',
        'month',
        'date',
        'location',
        'dynasty',
        'nation',
        'need_confirm',
        //        'is_lock',
        //        'content_id',
        'original_id',
        'preface',
        'subtitle',
        'genre_id',
        'poet_wikidata_id',
        'translator_wikidata_id',
        'short_url',
        'poet_id',
        'translator_id',
        'upload_user_id',
        'is_owner_uploaded',
        'share_pics',
        'campaign_id',
        'weapp_url',
        'flag',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'                     => 'integer',
        'title'                  => 'string',
        'language_id'            => 'integer',
        'is_original'            => 'integer',
        'poet'                   => 'string',
        'poet_cn'                => 'string',
        'bedtime_post_id'        => 'integer',
        'bedtime_post_title'     => 'string',
        'poem'                   => 'string',
        'length'                 => 'integer',
        'translator'             => 'string',
        'from'                   => 'string',
        'year'                   => 'string',
        'month'                  => 'string',
        'date'                   => 'string',
        'location'               => 'string',
        'dynasty'                => 'string',
        'nation'                 => 'string',
        'need_confirm'           => 'integer',
        'is_lock'                => 'boolean',
        'content_id'             => 'integer',
        'original_id'            => 'integer',
        'translated_id'          => 'integer',
        'preface'                => 'string',
        'subtitle'               => 'string',
        'genre_id'               => 'integer',
        'poet_wikidata_id'       => 'integer',
        'translator_wikidata_id' => 'integer',
        'short_url'              => 'string',
        'upload_user_id'         => 'integer',
        'score'                  => 'float',
        'is_owner_uploaded'      => 'integer',
        'share_pics'             => 'json',
        'campaign_id'            => 'integer',
        'weapp_url'              => 'json',
        'flag'                   => 'integer',
    ];

    protected $dates = [
        'updated_at',
        'created_at'
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required',
        'poet'  => 'required',
        //        'updated_at' => 'required',
        //        'created_at' => 'required',
        //        'is_lock' => 'required'
    ];

    // protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/poems/' . $this->getKey());
    }

    public function getFirstLineAttribute() {
        return Str::firstLine($this->poem, 20);
    }

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->poem = Str::trimEmptyLines(Str::trimTailSpaces($model->poem));
            if (is_null($model->flag)) {
                // Because the flag field is nullable (with a default value of 0) and fillable,
                // if it is not manually set to 0 when the flag is null, the flag field will be null.
                // After saving, the flag field will be automatically set to 0, which will cause
                // changes in the value of the flag field and generate unnecessary activity logs.
                $model->flag = self::$FLAG['none'];
            }
            $model->length = grapheme_strlen($model->poem);
        });

        self::created(function ($model) {
            // TODO is_original is deprecated
            if ($model->is_owner_uploaded === self::$OWNER['none'] && $model->is_original) {
                $model->original_id = $model->id;
            }

            if ($model->is_owner_uploaded === self::$OWNER['uploader']) {
                $model->original_id = $model->id;
            }

            $content = Content::create([
                'entry_id'    => $model->id,
                'type'        => 0,
                'content'     => $model->poem,
                'hash_f'      => '',             // parent pure content hash
                'hash'        => Str::contentHash($model->poem),        // current pure content hash（用于去重）
                'full_hash_f' => '',        // parent version's full hash
                'full_hash'   => Str::contentFullHash($model->poem)    // current version's full hash（用于追踪版本变化）
            ]);

            $model->content_id = $content->id;
            $model->save();
        });

        self::updating(function ($model) {
            $model->poem   = Str::trimEmptyLines(Str::trimTailSpaces($model->poem));
            $model->length = grapheme_strlen($model->poem);

            $fullHash = Str::contentFullHash($model->poem);
            if (!$model->content) {
                $oldPoem     = Poem::find($model->id);
                $oldFullHash = Str::contentFullHash($oldPoem->poem);
                $oldHash     = Str::contentHash($oldPoem->poem);
                $oldContent  = Content::create([
                    'entry_id'    => $oldPoem->id,
                    'type'        => 0,
                    'content'     => $oldPoem->poem,
                    'hash_f'      => '',             // parent pure content hash
                    'hash'        => $oldHash,        // current pure content hash（用于去重）
                    'full_hash_f' => '',        // parent version's full hash
                    'full_hash'   => $oldFullHash
                ]);
                // does this trigger a infinite recursion?
                $oldPoem->content_id = $oldContent->id;
                $oldPoem->save();
            } else {
                $oldFullHash = $model->content->full_hash ?: Str::contentFullHash($model->content->content);
                $oldHash     = $model->content->hash;
            }

            if ($fullHash !== $oldFullHash) {
                // update content when full hash changed
                $content = Content::create([
                    'entry_id'    => $model->id,
                    'type'        => 0,
                    'content'     => $model->poem,
                    'hash_f'      => $oldHash,
                    'hash'        => Str::contentHash($model->poem),
                    'full_hash_f' => $oldFullHash,
                    'full_hash'   => $fullHash
                ]);
                // TODO WHY content_id modification not loged in activityLog?
                $model->content_id   = $content->id;
                $model->need_confirm = 0;
            }
        });

        self::updated(function ($model) {
            Poem::withoutEvents(function () use ($model) {
                if ($model->is_owner_uploaded === self::$OWNER['none'] && $model->is_original) {
                    $model->original_id = $model->id;
                    $model->save();
                }

                if ($model->is_owner_uploaded === self::$OWNER['uploader']) {
                    $model->original_id = $model->id;
                    $model->save();
                }
            });

            // Clear translated poems tree cache when poem is updated
            $model->clearTranslatedPoemsTreeCache();
        });

        // Clear cache when poem is created or deleted
        self::created(function ($model) {
            $model->clearTranslatedPoemsTreeCache();
        });

        self::deleted(function ($model) {
            $model->clearTranslatedPoemsTreeCache();
        });

        // TODO delete related scores?
        // TODO delete related relatable record
    }

    /**
     * Clear translated poems tree cache for this poem
     */
    public function clearTranslatedPoemsTreeCache() {
        PoemRepository::clearTranslatedPoemsTreeCache($this);
    }

    /**
     * // if poem deleted, all it's relatable record should be deleted?
     * // if author deleted, all it's relatable record should be deleted?
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getTranslatorsAttribute() {
        // Use cached translators if available (performance optimized)
        if ($this->relationLoaded('cached_translators')) {
            return $this->cached_translators->map(function ($translator) {
                // Create mock Author/Entry objects from cached data
                if (isset($translator['id'])) {
                    // This is an Author
                    $author = new \App\Models\Author();
                    $author->id = $translator['id'];
                    // Provide name_lang so Author::getLabelAttribute() works consistently
                    $author->name_lang = json_encode(['zh-CN' => $translator['name']]);
                    return $author;
                } else {
                    // This is an Entry
                    $entry = new \App\Models\Entry();
                    $entry->name = $translator['name'];
                    return $entry;
                }
            });
        }

        // Fallback to original implementation if not cached

        $translators = $this->relatedTranslators()->get();

        return $translators->map(function ($translator) {
            if ($translator->end_type === Author::class) {
                return Author::find($translator->end_id);
            } elseif ($translator->end_type === Entry::class) {
                return Entry::find($translator->end_id);
            }

            throw new \Error('unexpected end_type: ' . $translator->end_type);
        });
    }

    public function relatedTranslators() {
        return Relatable::translatorIs(self::class, $this->id);
    }

    public function relatedPoets(): MorphToMany {
        return Relatable::poetIs(self::class, $this->id);
    }

    public function relatedMergedTo() {
        return Relatable::mergedToPoem(self::class, $this->id);
    }

    public function getMergedToPoemAttribute() {
        $relatable = $this->relatedMergedTo()->first();
        if ($relatable) {
            return self::find($relatable->end_id);
        }

        return null;
    }

    public function getTranslatorsLabelArrAttribute() {
        // Use cached translators if available (for performance optimization)
        if ($this->relationLoaded('cached_translators')) {
            return $this->cached_translators->toArray();
        }

        return $this->translators->map(function ($translator) {
            if ($translator instanceof Author) {
                return ['id' => $translator->id, 'name' => $translator->label];
            } elseif ($translator instanceof Entry) {
                return ['name' => $translator->name];
            }
        })->toArray();
    }

    public function getTranslatorsStrAttribute() {
        // Use cached translators string if available (for performance optimization)
        if ($this->relationLoaded('cached_translators_str')) {
            return $this->cached_translators_str;
        }

        $translators = implode(', ', array_map(function ($translator) {
            return $translator['name'];
        }, $this->translatorsLabelArr));

        return strlen($translators) ? $translators : $this->translator;
    }

    /**
     * add translator_is relation to author.
     * @param array $ids
     */
    public function relateToTranslators(array $ids) {
        foreach ($ids as $id) {
            if (is_integer($id) && Author::find($id)) {
                $endType = Author::class;
                $endID   = $id;
            } else {
                $endType  = Entry::class;
                $newEntry = Entry::create([
                    'name' => $id,
                    'type' => Entry::class
                ]);
                $endID = $newEntry->id;
            }
            Relatable::create([
                'relation'   => Relatable::RELATION['translator_is'],
                'start_type' => self::class,
                'start_id'   => $this->id,
                'end_type'   => $endType,
                'end_id'     => $endID
            ]);
        }

        // TODO don't need remove translator_id if they are removed from db
        $this->translator_id          = null;
        $this->translator_wikidata_id = null;
        $this->save();

        // TODO log relatedTranslators change
        // TODO make translators change revertible
    }

    /**
     * merge current poem to main poem.
     * @param int $id
     * @return Relatable
     */
    public function mergeToMainPoem(int $id) {
        return Relatable::updateOrCreate([
            'relation'   => Relatable::RELATION['merged_to_poem'],
            'start_type' => self::class,
            'start_id'   => $this->id,
            'end_type'   => self::class,
        ], [
            'end_id'     => $id
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function lang() {
        return $this->belongsTo(\App\Models\Language::class, 'language_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    private function _originalPoem() {
        /*
         * original_id 为 $this->id 的 poem 为原作，不为 0 的为有原作的译作， 为 0 的为无原作的译作。
         *
         * 2021.6.10 更改 original_id 为非空(为了方便使用索引) unsigned int（为了保持类型与 poem.id 相同） 字段。@see ChangeOriginalIdOfPoem
         * 早期版本使用 original_id 和 is_original 两个字段来表述 翻译自和原作译作属性，
         * 因为有一种译作没有 original_id，只能将其 original_id 字段置空。
         * TODO 删除 is_original 字段（由于 original_id 已经隐含了原作/译作信息，
         *  可以用 dynamic attribute 代替：original_id 为 $this->id 表示原作，为 0 表示无原作的译作）
         */
        return $this->belongsTo(\App\Models\Poem::class, 'original_id', 'id');
    }

    public function originalPoem() {
        return $this->_originalPoem()->with(['lang']);
    }

    /**
     * TODO remove all is_original ref, remove poem.is_original from database, use is_translated here.
     * @return bool
     */
    public function getIsTranslatedAttribute() {
        return $this->id !== $this->original_id;
    }

    public function getOriginalLinkAttribute() {
        return ($this->is_translated && $this->originalPoem) ? $this->originalPoem->url : null;
    }

    /**
     * @caution TopOriginalPoem 有可能是译作
     *
     * TODO [谨慎考虑！] 添加 top_original_id 字段（非空，为 0 表示无原作的译作，为 $this->id 表示原作），表示最顶层的翻译自的 poem id，省去此查询过程
     *      删除 is_original 字段（用 dynamic attribute 代替：top_original_id 为 $this->id 表示原作，为 0 表示无原作的译作）
     *      另外，还需要额外的逻辑实现 一致性
     * @return Poem|null
     */
    public function getTopOriginalPoemAttribute(): ?Poem {
        $processedIds  = [];
        $translateFrom = $this;

        do {
            // exclude processed ids to prevent infinite loop
            $processedIds[] = $translateFrom->id;
            if (!$translateFrom->original_id
                or in_array($translateFrom->original_id, $processedIds)
                or !$translateFrom->is_translated
            ) {
                break;
            }
        } while ($translateFrom = $translateFrom->originalPoem);

        return $translateFrom;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    private function _translatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'original_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function translatedPoems(): HasMany {
        return $this->_translatedPoems()->with(['lang'])->whereRaw('original_id <> poem.id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function scores() {
        return $this->hasMany(\App\Models\Score::class, 'poem_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function reviews() {
        return $this->hasMany(\App\Models\Review::class, 'poem_id', 'id');
    }

    /**
     * 其它同一原作下的翻译版本.
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    // public function sameTranslatedPoems() {
    //     return $this->hasMany(\App\Models\Poem::class, 'original_id', 'original_id');
    // }

    /**
     * 其它同一原作下的翻译版本，排除自身.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function otherTranslatedPoems() {
    //     return $this->sameTranslatedPoems()->where('id', '<>', $this->id);
    // }

    /**
     * 其它所有同一原作下的翻译版本，含二级、三级、N级翻译版本.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function allSameTranslatedPoems() {
    //     return $this->sameTranslatedPoems()->with('allSameTranslatedPoems');
    // }
    /**
     * 其它所有同一原作下的翻译版本，含二级、三级、N级翻译版本，排除自身.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function AllOtherTranslatedPoems() {
    //     return $this->allSameTranslatedPoems()->where('id', '<>', $this->id);
    // }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function genre() {
        return $this->belongsTo(\App\Models\Genre::class, 'genre_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function content() {
        return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    }

    public function wx() {
        return $this->hasMany(\App\Models\WxPost::class, 'poem_id', 'id');
    }

    public function poetWikidata() {
        return $this->belongsTo(\App\Models\Wikidata::class, 'poet_wikidata_id', 'id');
    }

    public function translatorWikidata() {
        return $this->belongsTo(\App\Models\Wikidata::class, 'translator_wikidata_id', 'id');
    }

    public function poetAuthor() {
        return $this->belongsTo(\App\Models\Author::class, 'poet_id', 'id');
    }

    public function poetThroughWikidata() {
        return $this->belongsTo(\App\Models\Author::class, 'poet_wikidata_id', 'wikidata_id');
    }

    // TODO poem.translator_id should be deprecated
    public function translatorAuthor() {
        return $this->belongsTo(\App\Models\Author::class, 'translator_id', 'id');
    }

    public function translatorThroughWikidata() {
        return $this->belongsTo(\App\Models\Author::class, 'translator_wikidata_id', 'wikidata_id');
    }

    public function tags() {
        return $this->morphToMany(\App\Models\Tag::class, 'taggable', 'taggable');
    }

    public function uploader() {
        return $this->belongsTo(\App\User::class, 'upload_user_id', 'id');
    }

    public function campaign() {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'id');
    }

    public function nft(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(\App\Models\NFT::class, 'poem_id', 'id');
    }

    // TODO public function getCampaginsAttribute() {}

    public function getIsCampaignAttribute() {
        if ($this->tags->count()) {
            return $this->tags->first(function ($tag) {
                return $tag->campaign;
            }) ? true : false;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getUrlAttribute() {
        return route('p/show', ['fakeId' => $this->fakeId]);
    }

    public function getTotalScoreAttribute() {
        return ScoreRepository::calc($this->id)['score'];
    }

    public function getScoreArrayAttribute() {
        return ScoreRepository::calc($this->id);
    }

    /**
     * get score between campaign start and end time
     * TODO update poem.score after each score updated/created.
     * @param Campaign $campaign
     * @return array
     */
    public function getCampaignScore(Campaign $campaign) {
        return ScoreRepository::calc($this->id, $campaign->start, $campaign->end);
    }

    /**
     * 是否为用户的原创作品
     */
    public function getIsOwnedAttribute() {
        return $this->is_owner_uploaded !== static::$OWNER['none'];
    }

    /**
     * 获取原创作者.
     */
    public function getOwnerAttribute() {
        if ($this->poetAuthor && $this->poetAuthor->user) {
            return $this->poetAuthor->user;
        } elseif ($this->is_owner_uploaded === static::$OWNER['uploader'] && $this->uploader) {
            return $this->uploader;
        }

        return null;
    }

    /**
     * @return \App\User|null
     */
    public function getPoetUserAttribute() {
        if ($this->is_owner_uploaded === self::$OWNER['uploader'] && $this->uploader) {
            return $this->uploader;
        }

        if ($this->is_owner_uploaded === self::$OWNER['poetAuthor'] || $this->is_owner_uploaded === self::$OWNER['none']) {
            if ($this->poetAuthor && $this->poetAuthor->user) {
                return $this->poetAuthor->user;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getPoetIsVAttribute() {
        if ($this->poetUser) {
            return $this->poetUser->is_v;
        }

        return false;
    }

    /**
     * TODO enable set locales while getting poet name
     * or poetLabel depend on client side locale.
     * @return string
     */
    public function getPoetLabelAttribute() {
        // TODO 考虑认领诗歌的情况。如果一首诗歌被用户成功认领，那么upload_user_id将不代表作者
        // 此时需要用 is_owner_uploaded==Poem::OWNER['author'] 来标志作者上传
        // is_owner_uploaded==Poem::$OWNER['none'] 标志默认状态，无人认领，未标注原创
        // is_owner_uploaded==Poem::$OWNER['uploader'] 标注原创，作者用户上传，此时以 upload_user_id 代表作者
        // is_owner_uploaded==Poem::$OWNER['translatorUploader'] 标注为原创译作，译者用户上传，此时upload_user_id将代表译者
        // is_owner_uploaded==Poem::$OWNER['poetAuthor'] 标注原创且已被作者认领，upload_user_id不代表作者，只代表上传人，此时应以poetAuthor为作者
        // is_owner_uploaded==Poem::$OWNER['translatorAuthor'] 标注为原创译作且已被译者认领，upload_user_id不代表作者，只代表上传人，此时应以translatorAuthor为作者
        // TODO if is_owner_uploaded==Poem::OWNER['poetAuthor'] && $this->uploader
        // TODO use poetAuthor->label if poem.poet and poem.poet_cn is used for SEO
        if ($this->is_owner_uploaded === static::$OWNER['uploader'] && $this->uploader) {
            return $this->uploader->name; // . '（' . $this->poet . '）';
        } elseif ($this->poetAuthor) {
            return $this->poetAuthor->label;
        }

        return ($this->poet === $this->poet_cn or is_null($this->poet_cn)) ? $this->poet : $this->poet_cn . '（' . $this->poet . '）';
    }

    public function getPoetLabelCnAttribute() {
        if ($this->is_owner_uploaded === static::$OWNER['uploader'] && $this->uploader) {
            return $this->uploader->name;
        } elseif ($this->poetAuthor) {
            return $this->poetAuthor->label_cn;
        }

        return (is_null($this->poet_cn) or $this->poet_cn === '') ? $this->poet : $this->poet_cn;
    }

    /**
     * TODO enable set locales while getting traslator name.
     * TODO use relatable to get translators instead of translatorAuthor, after translator_id is removed.
     * @return string
     */
    public function getTranslatorLabelAttribute() {
        // Use cached translators if available (for performance optimization)
        if ($this->relationLoaded('cached_translators') && $this->cached_translators->isNotEmpty()) {
            return $this->cached_translators->first()['name'];
        }
        // TODO if is_owner_uploaded==Poem::OWNER['translator'] && $this->uploader
        if ($this->translatorAuthor) {
            return $this->translatorAuthor->label;
        }

        return $this->translator;
    }

    /**
     * poetAvatar is a dynamic attribute of Poem, for these scene:
     * 1. uploader->avatar_url if is_owner_uploaded
     * 2. poetAuthor->avatarUrl if the poem has poet author.
     * @return string
     */
    public function getPoetAvatarAttribute() {
        if ($this->is_owner_uploaded === self::$OWNER['uploader'] && $this->uploader) {
            return $this->uploader->avatarUrl;
        }

        if ($this->poetAuthor) {
            return $this->poetAuthor->avatarUrl;
        }

        return config('app.avatar.default');
    }

    public function getTranslatorAvatarAttribute() {
        if ($this->is_owner_uploaded === self::$OWNER['translatorUploader'] && $this->uploader) {
            return $this->uploader->avatarUrl;
        }

        $firstRelatedTranslator = $this->translators->first();
        if ($firstRelatedTranslator instanceof Author) {
            return $firstRelatedTranslator->avatarUrl;
        }

        if ($this->translatorAuthor) {
            return $this->translatorAuthor->avatarUrl;
        }

        return config('app.avatar.default');
    }

    public function getActivityLogsAttribute() {
        // it's right to order by id desc instead of by created_at!
        // TODO including relatable record logs: mergeTo, translator...
        return $this->activities()->orderBy('id', 'desc')->get()->map(function ($activity) {
            $oldVal = $activity->properties->get('old');

            // TODO: it's an ugly way to filter the redundant update log after create,
            // it should not be written to db at the poem creation
            if ($oldVal && array_key_exists('poem', $oldVal) && is_null($oldVal['poem'])
                        && array_key_exists('title', $oldVal) && is_null($oldVal['title'])) {
                return false;
            }

            if ($activity->description === 'updated') {
                $diffs    = $activity->diffs;
                $diffKeys = array_keys($activity->diffs);
                foreach ($diffKeys as $key) {
                    if (in_array($key, self::$ignoreChangedAttributes)) {
                        unset($diffs[$key]);
                    }
                }
                if (empty($diffs)) {
                    return false;
                }
            }

            return $activity;
        })->filter(function ($val) {
            return $val !== false;
        })->values(); // values() makes result keys a continuously increased integer sequence
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param int   $options
     * @param array $extraList
     * @return string
     */
    public function toFillableJson($options = 0, $extraList = []) {
        $allowedFields = $this->fillable;
        $fillable      = array_filter($this->jsonSerialize(), function ($item, $key) use ($extraList, $allowedFields) {
            return in_array($key, $allowedFields) or in_array($key, $extraList);
        }, ARRAY_FILTER_USE_BOTH);
        $json = json_encode($fillable, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray(): array {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'preface'    => $this->preface,
            'subtitle'   => $this->subtitle,
            // 'uploader'   => $this->uploader->name,
            // 'relatedTranslators' => $translatorsLabels,
            'poet'               => $this->poetAuthor ? $this->poetAuthor->name_lang : $this->poet,
            'poem'               => $this->poem,
        ];
    }
    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable(): bool {
        return in_array($this->flag, [self::$FLAG['none'], self::$FLAG['infoNeedConfirm']]);
    }
}
