<?php

namespace App\Models;

use App\Traits\Likeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

class Review extends Model {
    use SoftDeletes;
    use LogsActivity;
    use Likeable;

    protected $table = 'review';

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

    protected $fillable = [
        'poem_id',
        'user_id',
        'content',
        'title',
        'reply_id'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = ['name', 'avatar', 'reply_to_user'];


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
    public function getNameAttribute() {
        return $this->user ? $this->user->name : '[已注销]';
    }
    /**
     * @return string
     */
    public function getReplyToUserAttribute() {
        return $this->replyOfReview ? $this->replyOfReview->user->name : '[已注销]';
    }

    /**
     * @return string
     */
    public function getAvatarAttribute() {
        return $this->user ? $this->user->avatarUrl : asset(\App\User::$defaultAvatarUrl);
    }

    public function getPureContentAttribute() {
        return str_replace('&nbsp;', ' ', strip_tags($this->content));
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
    public function replyOfReview() {
        return $this->belongsTo(self::class, 'reply_id', 'id');
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
