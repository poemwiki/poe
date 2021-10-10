<?php

namespace App\Models;

use App\Traits\HasFakeId;
use App\Traits\HasTranslations;
use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * Class Author.
 *
 * @property int    $id
 * @property string $created_at
 * @property string $updated_at
 * @property mixed fakeId
 * @property array|null                                                         $name_lang
 * @property int|null                                                           $user_id
 * @property string|null                                                        $avatar
 * @property array|null                                                         $pic_url
 * @property int|null                                                           $wikidata_id
 * @property mixed|null                                                         $wikipedia_url
 * @property array|null                                                         $describe_lang
 * @property \Illuminate\Support\Carbon|null                                    $deleted_at
 * @property int|null                                                           $nation_id
 * @property int|null                                                           $dynasty_id
 * @property string|null                                                        $short_url
 * @property int|null                                                           $upload_user_id
 * @property array|null                                                         $wiki_desc_lang
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property int|null                                                           $activities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Alias[]       $alias
 * @property int|null                                                           $alias_count
 * @property \App\Models\Dynasty|null                                           $dynasty
 * @property mixed                                                              $alias_arr
 * @property string                                                             $avatar_url
 * @property string                                                             $fake_id
 * @property mixed|string                                                       $label
 * @property mixed                                                              $label_cn
 * @property mixed                                                              $label_en
 * @property mixed                                                              $resource_url
 * @property array                                                              $translations
 * @property string                                                             $url
 * @property \App\Models\Nation|null                                            $nation
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[]        $poems
 * @property int|null                                                           $poems_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[]        $translatedPoems
 * @property int|null                                                           $translated_poems_count
 * @property \App\User|null                                                     $uploader
 * @property \App\User|null                                                     $user
 * @property \App\Models\Wikidata|null                                          $wikiData
 * @method static \Illuminate\Database\Eloquent\Builder|Author newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Author newQuery()
 * @method static \Illuminate\Database\Query\Builder|Author onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Author query()
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereDescribeLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereDynastyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereNameLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereNationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author wherePicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereShortUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereUploadUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereWikiDescLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereWikidataId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereWikipediaUrl($value)
 * @method static \Illuminate\Database\Query\Builder|Author withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Author withoutTrashed()
 * @mixin \Eloquent
 */
class Author extends Model implements Searchable {
    use SoftDeletes;
    use HasTranslations;
    use HasFakeId;
    use LogsActivity;
    use RelatableNode;

    /**DO NOT CHANGE FAKEID STATICS**/
    public static $FAKEID_KEY    = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    public static $FAKEID_SPARSE = 96969696969;
    /**DO NOT CHANGE FAKEID STATICS**/

    protected static $logFillable             = true;
    protected static $logOnlyDirty            = true;
    protected static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length'];

    protected $table = 'author';

    protected $fillable = [
        'describe_lang',
        'name_lang',
        'pic_url',
        'user_id',
        'wikidata_id',
        'wikipedia_url',
        'nation_id',
        'dynasty_id',
        'upload_user_id',
        'wiki_desc_lang',
        'avatar'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    // these attributes are translatable
    public $translatable = [
        'describe_lang',
        'name_lang',
        'wiki_desc_lang'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'                     => 'integer',
        'wikidata_id'            => 'integer',
        'user_id'                => 'integer',
        'pic_url'                => 'json',
        'nation_id'              => 'integer',
        'dynasty_id'             => 'integer',
        'upload_user_id'         => 'integer'
        // 'name_lang' => 'array',
        // 'describe_lang' => 'array'
    ];

    protected $appends = ['resource_url', 'url', 'label', 'label_en', 'label_cn', 'avatar_url'];

    public function poemsAsPoet(): MorphToMany {
        return $this->morphedByMany(\App\Models\Poem::class, 'start', 'relatable', 'end_id')
            ->where('relation', '=', Relatable::RELATION['poet_is'])
            ->where('end_type', '=', self::class)->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('relatable')
                    ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
            });
    }

    public function poemsAsTranslator(): MorphToMany {
        return $this->morphedByMany(\App\Models\Poem::class, 'start', 'relatable', 'end_id')
            ->where('relation', '=', Relatable::RELATION['translator_is'])
            ->where('end_type', '=', self::class)->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('relatable')
                    ->whereRaw('relatable.start_id = poem.id and relatable.relation=' . Relatable::RELATION['merged_to_poem']);
            });
    }

    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'poet_id', 'id');
    }

    // TODO poem.translator_id should be deprecated
    public function translatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'translator_id', 'id');
    }

    public function user() {
        return $this->hasOne(\App\User::class, 'id', 'user_id');
    }

    public function uploader() {
        return $this->hasOne(\App\User::class, 'id', 'upload_user_id');
    }

    public function nation() {
        return $this->belongsTo(\App\Models\Nation::class, 'nation_id', 'id');
    }

    public function dynasty() {
        return $this->belongsTo(\App\Models\Dynasty::class, 'dynasty_id', 'id');
    }

    public function wikiData() {
        return $this->hasOne(\App\Models\Wikidata::class, 'id', 'wikidata_id');
    }

    public function alias() {
        return $this->hasMany(\App\Models\Alias::class, 'author_id', 'id');
    }

    public function relatedAvatar() {
        return Relatable::authorHasAvatar(self::class, $this->id);
    }

    public function relatedImageFile() {
        return Relatable::authorHasImage(self::class, $this->id);
    }

    public function relateToImage($ID) {
        return Relatable::updateOrCreate([
            'relation'   => Relatable::RELATION['author_has_image'],
            'start_type' => self::class,
            'start_id'   => $this->id,
            'end_type'   => MediaFile::class,
            'end_id'     => $ID
        ]);
    }

    public function relateToAvatar($ID) {
        return Relatable::updateOrCreate([
            'relation'   => Relatable::RELATION['author_has_avatar'],
            'start_type' => self::class,
            'start_id'   => $this->id,
            'end_type'   => MediaFile::class
        ], [
            'end_id'     => $ID
        ]);
    }

    public static function boot() {
        parent::boot();

        self::created(function ($model) {
            Artisan::call('alias:importFromAuthor', ['--id' => $model->id]);
        });
        self::updated(function ($model) {
            // TODO only do importFromAuthor if name_lang changed
            // TODO 如果前端可编辑多别名，此处应删除原有别名，或在controller删除前端选择的别名
            Artisan::call('alias:importFromAuthor', ['--id' => $model->id]);
        });
        self::deleting(function ($model) {
            $model->alias()->delete();
        });
    }

    public function getWikiDataNationId() {
        $countries = $this->wikiData->getClaim(Wikidata::PROP['countries']);
        if (!$countries) {
            return null;
        }

        return str_replace('Q', '', $countries[0]->mainsnak->datavalue->value->id);
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/authors/' . $this->getKey());
    }

    /**
     * @return string
     */
    public function getUrlAttribute() {
        return route('author/show', ['fakeId' => $this->fakeId]);
    }

    /**
     * @return mixed|string
     */
    public function getLabelAttribute() {
        $default  = $this->getTranslated('name_lang', config('app.locale'));
        $fallback = $this->getTranslated('name_lang', config('app.fallback_locale'));

        return $default ?: $fallback;
    }

    public function getLabelEnAttribute() {
        return $this->getTranslated('name_lang', 'en');
    }

    public function getLabelCnAttribute() {
        return $this->getTranslated('name_lang', 'zh-CN');
    }

    // get all alias without repeated values
    public function getAliasArrAttribute() {
        $all = Alias::select(['name', 'locale'])->where('author_id', '=', $this->id)
            ->get()->pluck(['name'])->map(function ($name) {
                return Str::trimSpaces($name);
            })
            ->unique();

        return $all;
    }

    /**
     * use avatarUrl in case users.avatar is null
     * TODO check if valid for each pic_url.
     * @return string
     */
    public function getAvatarUrlAttribute() {
        if ($this->avatar) {
            return $this->avatar;
        }
        if ($this->user) {
            return $this->user->avatar_url;
        }
        $defaultAvatar = config('app.avatar.default');
        $url           = isValidPicUrl($this->pic_url[0] ?? '') ? $this->pic_url[0] : $defaultAvatar;
        if (isWikimediaUrl($url)) {
            // $url = route('author-avatar', ['fakeId' => $this->fake_id]);
            $url = $defaultAvatar;
        }

        return $url;
    }

    public function fetchWikiDesc($force = false) {
        if (!$this->wiki_desc_lang) {
            $titleLocale = $this->wikiData->getSiteTitle(config('app.locale-wikipedia'));
            if (empty($titleLocale)) {
                return '';
            }
            $summary = get_wikipedia_summary($titleLocale);

            if ($summary) {
                // To avoid unnecessary alias:importFromAuthor command execute
                Author::withoutEvents(function () use ($titleLocale, $summary) {
                    $this->setTranslation('wiki_desc_lang', $titleLocale['locale'], $summary);
                    // save summary to description_lang and don't show wiki_desc_lang?
                    // if($this->description_lang === $this->wikiData->getDescription(config('app.locale'))) {
                    //
                    // }
                    $this->save();
                });
            }
        }

        return $this->wiki_desc_lang;
    }

    /**
     * TODO move this to Query service
     * search poems within this author's works.
     */
    public static function searchPoems() {
    }

    public function getSearchResult(): SearchResult {
        $url = route('author/show', ['fakeId' => $this->fakeId]);

        return new SearchResult(
            $this,
            $this->name_lang,
            $url
        );
    }
}
