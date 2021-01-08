<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

use Spatie\Activitylog\Traits\LogsActivity;

class Taggable extends MorphPivot {
    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $table = 'taggable';
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
        'id' => 'integer',
        'tag_id' => 'integer',
        'taggable_id' => 'integer',
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
        'tag_id' => 'required',
        'taggable_id' => 'required',
        'taggable_type' => 'required',
    ];

    protected $appends = [];

    /* ************************ ACCESSOR ************************* */


}
