<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @SWG\Definition(
 *      definition="Content",
 *      required={"hash", "new_hash", "type", "entry_id", "content"},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="hash",
 *          description="hash",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="new_hash",
 *          description="new_hash",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="type",
 *          description="type",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="entry_id",
 *          description="entry_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="content",
 *          description="content",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="deleted_at",
 *          description="deleted_at",
 *          type="string",
 *          format="date-time"
 *      )
 * )
 */
class Content extends Model
{
    use SoftDeletes;

    public $table = 'content';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    public $fillable = [
        'hash',
        'hash_f',
        'full_hash',
        'full_hash_f',
        'type',
        'entry_id',
        'content'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'hash' => 'string',
        'hash_f' => 'string',
        'full_hash' => 'string',
        'full_hash_f' => 'string',
        'type' => 'integer',
        'entry_id' => 'integer',
        'content' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'hash' => 'required',
        'new_hash' => 'required',
        'full_hash' => 'required',
        'type' => 'required',
        'entry_id' => 'required',
        'content' => 'required'
    ];

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->hash_crc32 = Str::crc32($model->hash);
            $model->full_hash_crc32 = Str::crc32($model->full_hash);
        });

        // self::created(function ($model) {
        // });

        // self::updating(function ($model) {
        // });
    }

}
