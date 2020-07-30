<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @SWG\Definition(
 *      definition="Poem",
 *      required={"updated_at", "created_at", "is_lock"},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="title",
 *          description="title",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="language",
 *          description="language",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="is_original",
 *          description="is_original",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="poet",
 *          description="poet",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="poet_cn",
 *          description="poet_cn",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="bedtime_post_id",
 *          description="bedtime_post_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="bedtime_post_title",
 *          description="bedtime_post_title",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="poem",
 *          description="poem",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="length",
 *          description="length",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="translator",
 *          description="translator",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="from",
 *          description="from",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="year",
 *          description="year",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="month",
 *          description="month",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="date",
 *          description="date",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="dynasty",
 *          description="dynasty",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="nation",
 *          description="nation",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="need_confirm",
 *          description="need_confirm",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="is_lock",
 *          description="is_lock",
 *          type="boolean"
 *      )
 * )
 */

/**
 * Class Poem
 * @package App\Models
 */
class Poem extends Model
{
    use SoftDeletes;

    public $table = 'poem';

    const FAKEID_KEY = 'PoemWikikiWmeoP'; // Symmetric-key for xor
    const FAKEID_SPARSE = 96969696969;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
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
        'is_lock'
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

    public static function boot()
    {
        parent::boot();

        self::creating(function($model){
            $model->length = grapheme_strlen($model->poem);
        });


        self::updating(function($model){
            $model->length = grapheme_strlen($model->poem);
        });
    }



    public static function noSpace($str) {
        return preg_replace("#\s+#u", '', $str);
    }
    public static function pureStr($str) {
        return preg_replace("#[[:punct:]]+#u", '', self::noSpace($str));
    }

    public static function contentHash($str) {
        return hash('sha256', self::pureStr($str));
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
