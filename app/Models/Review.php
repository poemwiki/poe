<?php

namespace App\Models;

use App\Traits\HasCompositeKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
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


    // public static function boot() {
    //     parent::boot();
    //
    //     self::retrieved(function ($model) {
    //         $model->content = Str::of($model->content)->addLinks();
    //         dd($model->content);
    //     });
    // }

    public function getRichContentAttribute() {
        // dd(Str::of($this->content)->addLinks());
        return Str::of($this->content)->addLinks();
    }

    /**
     * @return string
     */
    public function getUnameAttribute() {
       return $this->user->name;
    }


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
    // public function content() {
    //     return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    // }
}
