<?php

namespace App\Models;

use App\Traits\HasFakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

/**
 * Class Author
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property User user
 * @property array|null picUrl
 * @package App
 */
class Author extends Model implements Searchable {
    use SoftDeletes;
    use HasTranslations;
    use HasFakeId;
    use LogsActivity;

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
        'upload_user_id'
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
            // TODO 如果前端可编辑多别名，此处应删除原有别名，或在controller删除前端选择的别名
            Artisan::call('alias:importFromAuthor', ['--id' => $model->id]);
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
        if ($default !== $fallback) {
           return  $default." ($fallback)";
        }
        return $default ?: $fallback;
    }
    public function getLabelEnAttribute() {
        return $this->getTranslated('name_lang', 'en');
    }
    public function getLabelCNAttribute() {
        return $this->getTranslated('name_lang', 'zh-CN');
    }


    private function isValidPicUrl($url) {
        return !empty($url) && !str_ends_with($url, 'tif');
    }
    /**
     * use avatarUrl in case users.avatar is null
     * @return string
     */
    public function getAvatarUrlAttribute() {
        return $this->isValidPicUrl($this->pic_url[0] ?? '') ? $this->pic_url[0] : asset(static::$defaultAvatarUrl);
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
