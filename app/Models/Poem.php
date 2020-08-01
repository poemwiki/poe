<?php

namespace App\Models;
use App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poem extends Model
{
    use SoftDeletes;
    protected $table = 'poem';

    const FAKEID_KEY = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    const FAKEID_SPARSE = 96969696969;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'title',
        'language',
        'is_original',
        'poet',
        'poet_cn',
        'bedtime_post_id',
        'bedtime_post_title',
        'poem',
        'length',
        'translator',
        'from',
        'year',
        'month',
        'date',
        'dynasty',
        'nation',
        'need_confirm',
        'is_lock',
        'content_id',

    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'title' => 'string',
        'language' => 'integer',
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
        'dynasty' => 'string',
        'nation' => 'string',
        'need_confirm' => 'integer',
        'is_lock' => 'boolean',
        'content_id' => 'integer'
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
        //        'updated_at' => 'required',
        //        'created_at' => 'required',
        //        'is_lock' => 'required'
    ];

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute() {
        return url('/admin/poems/'.$this->getKey());
    }

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function($model){
            $model->poem = self::trimTailSpaces($model->poem);
            $model->length = grapheme_strlen($model->poem);

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
        });

        self::updating(function($model){
            $model->poem = self::trimTailSpaces($model->poem);
            $model->length = grapheme_strlen($model->poem);

            $fullHash = self::contentFullHash($model->poem);
            if($fullHash !== $model->content->fullHash) {
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
        return preg_replace("##[[:punct:]]+#u", '', $str);
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
    public function language() {
        return $this->belongsTo(\App\Models\Language::class, 'id', 'language');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function content() {
        return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    }

    /**
     * @return string A xor encrypted string
     */
    public function getFakeId() {
        return base64_encode(($this->id * self::FAKEID_SPARSE) ^ mb_ord(self::FAKEID_KEY));
    }

    /**
     * @param $fakeId
     * @return float|int The decrypted id of poem
     */
    public static function getIdFromFakeId($fakeId) {
        return (base64_decode($fakeId) ^ mb_ord(self::FAKEID_KEY)) / self::FAKEID_SPARSE;
    }

    public function getUrl() {
        return app('url')->route('posts.show', [$this->getFakeId()]);
    }

}
