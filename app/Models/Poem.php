<?php

namespace App\Models;

use App\Models\Content;
use App\Repositories\ScoreRepository;
use App\Traits\HasFakeId;
use App\User;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Str;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property mixed original_id
 * @property mixed translatedPoems
 * @property mixed id
 * @property string poetLabel
 * @property integer is_owner_uploaded
 * @property User uploader
 * @property Author poetAuthor
 * @property Illuminate\Support\Collection|Tag[] tags
 */
class Poem extends Model implements Searchable {
    use SoftDeletes;
    use LogsActivity;
    use HasFakeId;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    public static $ignoreChangedAttributes = ['created_at', 'need_confirm', 'length', 'score', 'share_pics', 'short_url', 'poet_wikidata_id', 'translator_wikidata_id' ];

    protected $table = 'poem';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $attributes = [
        'language_id' => 1,
        'is_original' => 1
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
        'share_pics'
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
        'short_url' => 'string',
        'upload_user_id' => 'integer',
        'score' => 'float',
        'is_owner_uploaded' => 'integer',
        'share_pics' => 'json'
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
    public function getFirstLineAttribute() {
        return Str::of($this->poem)->firstLine();
    }

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->poem = Str::trimEmptyLines(Str::trimTailSpaces($model->poem));
            $model->length = grapheme_strlen($model->poem);
        });
        self::created(function ($model) {
            $content = Content::create([
                'entry_id' => $model->id,
                'type' => 0,
                'content' => $model->poem,
                'hash_f' => '',             // parent pure content hash
                'hash' => Str::contentHash($model->poem),        // current pure content hash（用于去重）
                'full_hash_f' => '',        // parent version's full hash
                'full_hash' => Str::contentFullHash($model->poem)    // current version's full hash（用于追踪版本变化）
            ]);

            $model->content_id = $content->id;
            $model->save();
        });

        self::updating(function ($model) {
            $model->poem = Str::trimEmptyLines(Str::trimTailSpaces($model->poem));
            $model->length = grapheme_strlen($model->poem);

            $fullHash = Str::contentFullHash($model->poem);
            if(!$model->content) {
                $oldPoem = Poem::find($model->id);
                $oldFullHash = Str::contentFullHash($oldPoem->poem);
                $oldContent = Content::create([
                    'entry_id' => $oldPoem->id,
                    'type' => 0,
                    'content' => $oldPoem->poem,
                    'hash_f' => '',             // parent pure content hash
                    'hash' => Str::contentHash($oldPoem->poem),        // current pure content hash（用于去重）
                    'full_hash_f' => '',        // parent version's full hash
                    'full_hash' => $oldFullHash
                ]);
                // does this trigger a infinite recursion?
                // $oldPoem->content_id = $oldContent->id;
                // $oldPoem->save();
            }else {
                $oldFullHash = $model->content->full_hash ?: Str::contentFullHash($model->content->content);
            }

            if ($fullHash !== $oldFullHash) {
                // update content when full hash changed
                $content = Content::create([
                    'entry_id' => $model->id,
                    'type' => 0,
                    'content' => $model->poem,
                    'hash_f' => $model->content->hash,
                    'hash' => Str::contentHash($model->poem),
                    'full_hash_f' => $oldFullHash,
                    'full_hash' => $fullHash
                ]);
                // TODO WHY content_id modification not loged in activityLog?
                $model->content_id = $content->id;
                $model->need_confirm = 0;
            }
        });
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
    // TODO poem hasMany translators
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

    // TODO public function getCampaginsAttribute() {}

    public function getIsCampaignAttribute() {
        if($this->tags->count()) {
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
        return $this->score ?: ScoreRepository::calc($this->id)['score'];
    }

    public function getScoreArrayAttribute() {
        return ScoreRepository::calc($this->id);
    }

    /**
     * get score between campaign start and end time
     * TODO update poem.score after each score updated/created
     * @param Campaign $campaign
     * @return array
     */
    public function getCampaignScore(Campaign $campaign) {
        return ScoreRepository::calc($this->id, $campaign->start, $campaign->end);
    }

    /**
     * TODO enable set locales while getting poet name
     * @return string
     */
    public function getPoetLabelAttribute() {
        // TODO if is_owner_uploaded==Poem::OWNER['poet'] && $this->uploader
        // TODO use poetAuthor->label if poem.poet and poem.poet_cn is used for SEO
        if ($this->is_owner_uploaded && $this->uploader) {
            return $this->uploader->name;
        } else if ($this->poetAuthor) {
            return $this->poetAuthor->label;
        } else {
            return ($this->poet === $this->poet_cn or is_null($this->poet_cn)) ? $this->poet : $this->poet_cn.'（'.$this->poet.'）';
        }
    }

    /**
     * TODO enable set locales while getting traslator name
     * @return string
     */
    public function getTranslatorLabelAttribute() {
        // TODO if is_owner_uploaded==Poem::OWNER['translator'] && $this->uploader
        if ($this->translatorAuthor) {
            return $this->translatorAuthor->label;
        } else {
            return $this->translator;
        }
    }

    /**
     * poetAvatar is an dynamic attribute of Poem, for these scene:
     * 1. uploader->avatarUrl if is_owner_uploaded
     * 2. poetAuthor->picUrl[0]  if the poet author has no related user
     * 3. poetAuthor->user->avatarUrl
     * @return string
     */
    public function getPoetAvatarAttribute() {
        if ($this->is_owner_uploaded && $this->uploader) {
            return $this->uploader->avatarUrl;
        }

        if ($this->poetAuthor) {
            if($this->poetAuthor->user) {
                return $this->poetAuthor->user->avatarUrl;
            }

            if($this->poetAuthor->picUrl) {
                return $this->poetAuthor->picUrl[0];
            }
        }

        return asset(\App\User::$defaultAvatarUrl);
    }


    public function getActivityLogsAttribute() {
        // it's right to order by id desc instead of by created_at!
        return $this->activities()->orderBy('id', 'desc')->get()->map(function ($activity) {
            $oldVal = $activity->properties->get('old');

            // TODO: it's an ugly way to filter the redundant update log after create,
            // it should not be written to db at the poem creation
            if ($oldVal && array_key_exists('poem', $oldVal) && is_null($oldVal['poem'])
                && array_key_exists('poet', $oldVal) && is_null($oldVal['poet'])
                && array_key_exists('title', $oldVal) && is_null($oldVal['title'])) {
                return false;
            }

            if($activity->description === 'updated') {
                $diffs = $activity->diffs;
                $diffKeys = array_keys($activity->diffs);
                foreach ($diffKeys as $key) {
                    if(in_array($key, self::$ignoreChangedAttributes)) {
                        unset($diffs[$key]);
                    }
                }
                if(empty($diffs)) return false;
            }

            return $activity;
        })->filter(function ($val) {
            return $val !== false;
        })->values(); // values() makes result keys a continuously increased integer sequence
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toFillableJson($options = 0) {
        $allowedFields = $this->fillable;
        $fillable = array_filter($this->jsonSerialize(), function ($item, $key) use ($allowedFields)  {
            return in_array($key, $allowedFields);
        }, ARRAY_FILTER_USE_BOTH);
        $json = json_encode($fillable, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    public function getSearchResult(): SearchResult {
        return new SearchResult(
            $this,
            $this->title,
            $this->url
        );
    }
}
