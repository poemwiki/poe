<?php

namespace App\Models;

use App\Traits\HasCompositeKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Review extends Model {
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'review';

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $fillable = [
        'poem_id',
        'user_id',
        'content',
        'title'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [];

    /**
     * @return string
     */
    //    public function getUrlAttribute() {
    //        return route('review/show', ['id' => $this->id]);
    //    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function poem() {
        return $this->belongsTo(\App\Models\Poem::class, 'poem_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function content() {
        return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    }
}
