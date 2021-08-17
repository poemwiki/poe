<?php

namespace App\Models;

use App\Traits\HasFakeId;
use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * Class Author
 *
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property mixed fakeId
 * @package App
 * @property array|null $name_lang
 * @property int|null $user_id
 * @property array|null $pic_url
 * @property int|null $wikidata_id
 * @property mixed|null $wikipedia_url
 * @property array|null $describe_lang
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $nation_id
 * @property int|null $dynasty_id
 * @property string|null $short_url
 * @property int|null $upload_user_id
 * @property array|null $wiki_desc_lang
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Alias[] $alias
 * @property-read int|null $alias_count
 * @property-read \App\Models\Dynasty|null $dynasty
 * @property-read mixed $alias_arr
 * @property-read string $avatar_url
 * @property-read string $fake_id
 * @property-read mixed|string $label
 * @property-read mixed $label_cn
 * @property-read mixed $label_en
 * @property-read mixed $resource_url
 * @property-read array $translations
 * @property-read string $url
 * @property-read \App\Models\Nation|null $nation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[] $poems
 * @property-read int|null $poems_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Poem[] $translatedPoems
 * @property-read int|null $translated_poems_count
 * @property-read \App\User|null $uploader
 * @property-read \App\User|null $user
 * @property-read \App\Models\Wikidata|null $wikiData
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
    static $FAKEID_KEY = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    static $FAKEID_SPARSE = 96969696969;
    /**DO NOT CHANGE FAKEID STATICS**/

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length'];

    static public $defaultAvatarUrl = 'images/avatar-default.png';

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
        'wiki_desc_lang'
    ];


    protected $dates = [
        'created_at',
        'deleted_at',
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
        'id' => 'integer',
        'wikidata_id' => 'integer',
        'user_id' => 'integer',
        'pic_url' => 'json',
        'nation_id' => 'integer',
        'dynasty_id' => 'integer',
        'upload_user_id' => 'integer',
        // 'name_lang' => 'array',
        // 'describe_lang' => 'array'
    ];

    protected $appends = ['resource_url', 'url', 'label', 'label_en', 'label_cn', 'avatar_url'];

    public function poemsAsPoet() : MorphToMany {
        return $this->morphedByMany(\App\Models\Poem::class, 'start', 'relatable', 'end_id')
            ->where('relation', '=', Relatable::RELATION['poet_is']);
    }
    public function poemsAsTranslator() : MorphToMany {
        return $this->morphedByMany(\App\Models\Poem::class, 'start', 'relatable', 'end_id')
            ->where('relation', '=', Relatable::RELATION['translator_is']);
    }

    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'poet_id', 'id');
    }
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
        if(!$countries) return null;

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
        $default = $this->getTranslated('name_lang', config('app.locale'));
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
     * TODO check if valid for each pic_url
     * @return string
     */
    public function getAvatarUrlAttribute() {
        if($this->user) {
            return $this->user->avatar_url;
        }
        $url = isValidPicUrl($this->pic_url[0] ?? '') ? $this->pic_url[0] : asset(static::$defaultAvatarUrl);
        if (isWikimediaUrl($url)) {
            // $url = route('author-avatar', ['fakeId' => $this->fake_id]);
            $url = asset(static::$defaultAvatarUrl);
        }
        return $url;
    }

    public function fetchWikiDesc($force = false) {
        if(!$this->wiki_desc_lang) {
            $titleLocale = $this->wikiData->getSiteTitle(config('app.locale-wikipedia'));
            if(empty($titleLocale)) {
                return '';
            }
            $summary = get_wikipedia_summary($titleLocale);

            if($summary) {
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
