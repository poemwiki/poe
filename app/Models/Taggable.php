<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Taggable
 *
 * @property int                             $id
 * @property int                             $tag_id
 * @property int                             $taggable_id
 * @property string                          $taggable_type
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property-read int|null $activities_count
 */
class Taggable extends MorphPivot {
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at']);
    }

    protected $table     = 'taggable';
    public $incrementing = true;


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    public $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'            => 'integer',
        'tag_id'        => 'integer',
        'taggable_id'   => 'integer',
        'taggable_type' => 'string'
    ];


    protected $dates = [
        'updated_at',
        'created_at'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'tag_id'        => 'required',
        'taggable_id'   => 'required',
        'taggable_type' => 'required',
    ];

    protected $appends = [];

    /* ************************ ACCESSOR ************************* */
}
