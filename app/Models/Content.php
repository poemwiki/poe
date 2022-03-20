<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * App\Models\Content.
 *
 * @property int                             $id
 * @property int                             $type
 * @property int                             $entry_id
 * @property int                             $hash_crc32
 * @property int                             $full_hash_crc32
 * @property string                          $hash
 * @property string                          $hash_f          current version's father's hash
 * @property string                          $full_hash
 * @property string                          $full_hash_f     current version's father's full_hash
 * @property string                          $content
 * @property string                          $ar_tx_id        arweave transaction id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \App\Models\Poem                $poem
 * @method static \Illuminate\Database\Eloquent\Builder|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Content newQuery()
 * @method static \Illuminate\Database\Query\Builder|Content onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Content query()
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereFullHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereFullHashCrc32($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereFullHashF($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereHashCrc32($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereHashF($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Content whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Content withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Content withoutTrashed()
 * @mixin \Eloquent
 */
class Content extends Model {
    use SoftDeletes;

    public $table = 'content';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $dates = ['created_at', 'updated_at'];

    public $fillable = [
        'hash',
        'hash_f',
        'full_hash',
        'full_hash_f',
        'type',
        'entry_id',
        'content',
        'ar_tx_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'          => 'integer',
        'hash'        => 'string',
        'hash_f'      => 'string',
        'full_hash'   => 'string',
        'full_hash_f' => 'string',
        'type'        => 'integer',
        'entry_id'    => 'integer',
        'content'     => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array
     */
    public static $rules = [
        'hash'      => 'required',
        'new_hash'  => 'required',
        'full_hash' => 'required',
        'type'      => 'required',
        'entry_id'  => 'required',
        'content'   => 'required'
    ];

    /**
     * TODO this should be a morph relation.
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function poem() {
        return $this->belongsTo(\App\Models\Poem::class, 'entry_id', 'id');
    }

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
