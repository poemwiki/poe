<?php

namespace App\Models;

use App\Models\Content;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property mixed original_id
 * @property mixed translatedPoems
 * @property mixed id
 */
class Poem extends Model implements Searchable {
    use SoftDeletes;
    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length'];

    protected $table = 'poem';

    const FAKEID_KEY = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    const FAKEID_SPARSE = 96969696969;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


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
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'title' => 'string',
        'language_id' => 'integer',
        'is_original' => 'integer',
        'poet' => 'string',
        'poet_cn' => 'string',
        'bedtime_post_id' => 'integer',
        'bedtime_post_title' => 'string',
        'poem' => 'string',
        'length' => 'integer',
        'translator' => 'string',
        'from' => 'string',
        'year' => 'string',
        'month' => 'string',
        'date' => 'string',
        'location' => 'string',
        'dynasty' => 'string',
        'nation' => 'string',
        'need_confirm' => 'integer',
        'is_lock' => 'boolean',
        'content_id' => 'integer',
        'original_id' => 'integer',
        'translated_id' => 'integer',
        'preface' => 'string',
        'subtitle' => 'string',
        'genre_id' => 'integer',
        'poet_wikidata_id' => 'integer',
        'translator_wikidata_id' => 'integer',
    ];


    protected $dates = [
        'updated_at',
        'created_at',
        'deleted_at'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required',
        'poet' => 'required',
        //        'updated_at' => 'required',
        //        'created_at' => 'required',
        //        'is_lock' => 'required'
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/poems/' . $this->getKey());
    }

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->poem = self::trimTailSpaces($model->poem);
            $model->length = grapheme_strlen($model->poem);
        });
        self::created(function ($model) {
            $hash = self::contentHash($model->poem);
            $fullHash = self::contentFullHash($model->poem);
            $content = Content::create([
                'entry_id' => $model->id,
                'type' => 0,
                'content' => $model->poem,
                'hash' => $hash,
                'new_hash' => $hash,
                'full_hash' => $fullHash
            ]);

            $model->content_id = $content->id;
            $model->save();
        });

        self::updating(function ($model) {
            $model->poem = self::trimTailSpaces($model->poem);
            $model->length = grapheme_strlen($model->poem);
            $fullHash = self::contentFullHash($model->poem);

            if ($fullHash !== $model->content->full_hash) {
                // need update content
                $hash = self::contentHash($model->poem);
                $content = Content::create([
                    'entry_id' => $model->id,
                    'type' => 0,
                    'content' => $model->poem,
                    'hash' => $hash,
                    'new_hash' => $hash,
                    'full_hash' => $fullHash
                ]);
                $model->content_id = $content->id;
                $model->need_confirm = 0;
            }
        });
    }


    public static function trimSpaces($str) {
        return preg_replace('#^\s+|\s+$#u', '', $str);
    }

    public static function trimTailSpaces($str) {
        return preg_replace('#\s+$#u', '', $str);
    }

    public static function noSpace($str) {
        return preg_replace("#\s+#u", '', $str);
    }

    public static function noPunct($str) {
        return preg_replace("#[[:punct:]]+#u", '', $str);
    }

    public static function pureStr($str) {
        return self::noPunct(self::noSpace($str));
    }

    public static function contentHash($str) {
        return hash('sha256', self::pureStr($str));
    }

    public static function contentFullHash($str) {
        return hash('sha256', $str);
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
    public function originalPoem() {
        return $this->belongsTo(\App\Models\Poem::class, 'original_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function translatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'original_id', 'id');
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
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function sameTranslatedPoems() {
        return $this->hasMany(\App\Models\Poem::class, 'original_id', 'original_id');
    }

    public function otherTranslatedPoems() {
        return $this->sameTranslatedPoems()->where('id', '<>', $this->id);
    }

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
    public function translatorAuthor() {
        return $this->belongsTo(\App\Models\Author::class, 'translator_id', 'id');
    }
    public function translatorThroughWikidata() {
        return $this->belongsTo(\App\Models\Author::class, 'translator_wikidata_id', 'wikidata_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function author() {
        return $this->hasOne(\App\Models\Author::class, 'id', 'poet_id');
    }

    // TODO poem hasMany translators

    /**
     * @return string A xor encrypted string
     */
    public function getFakeIdAttribute() {
        return self::getFakeId($this->id);
    }

    /**
     * @return string
     */
    public function getUrlAttribute() {
        return route('poem', ['id' => $this->id]);
    }

    /**
     * @return string A xor encrypted string
     */
    public static function getFakeId($id) {
        return base64_encode(($id * self::FAKEID_SPARSE) ^ mb_ord(self::FAKEID_KEY));
    }

    /**
     * @param $fakeId
     * @return false|int The decrypted id of poem
     */
    public static function getIdFromFakeId($fakeId) {
        $decoded = base64_decode($fakeId);
        if (!is_numeric($decoded)) {
            return false;
        }
        return ($decoded ^ mb_ord(self::FAKEID_KEY)) / self::FAKEID_SPARSE;
    }

    public function getSearchResult(): SearchResult {
        $url = route('poem', $this->id);

        return new SearchResult(
            $this,
            $this->title,
            $url
        );
    }
}
