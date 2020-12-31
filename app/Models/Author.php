<?php

namespace App\Models;

use App\Traits\HasFakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Author extends Model implements Searchable {
    use SoftDeletes;
    use HasTranslations;
    use HasFakeId;
    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
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
        // 'name_lang' => 'array',
        // 'describe_lang' => 'array'
    ];

    protected $appends = ['resource_url', 'url', 'label', 'label_en', 'label_cn'];

    public function poems() {
        return $this->hasMany(\App\Models\Poem::class, 'poet_id', 'id');
    }
    public function translatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'translator_id', 'id');
    }
    public function user() {
        return $this->hasOne(\App\User::class, 'id', 'user_id');
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
    public function getLabelAttribute() {
        return $this->getTranslated('name_lang', 'en') . "（" . $this->getTranslated('name_lang', 'zh-CN') . "）";
    }
    public function getLabelEnAttribute() {
        return $this->getTranslated('name_lang', 'en');
    }
    public function getLabelCNAttribute() {
        return $this->getTranslated('name_lang', 'zh-CN');
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
