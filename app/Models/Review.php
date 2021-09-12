<?php

namespace App\Models;

use App\Traits\Likeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Review.
 *
 * @property int                                                                $id
 * @property int                                                                $poem_id
 * @property int                                                                $user_id
 * @property int|null                                                           $like
 * @property int|null                                                           $content_id
 * @property string|null                                                        $title
 * @property string                                                             $content
 * @property \Illuminate\Support\Carbon|null                                    $created_at
 * @property \Illuminate\Support\Carbon|null                                    $updated_at
 * @property \Illuminate\Support\Carbon|null                                    $deleted_at
 * @property int|null                                                           $reply_id
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[] $activities
 * @property int|null                                                           $activities_count
 * @property string                                                             $avatar
 * @property string                                                             $name
 * @property mixed                                                              $pure_content
 * @property string                                                             $reply_to_user
 * @property mixed                                                              $rich_content
 * @property \Illuminate\Database\Eloquent\Collection|\App\User[]               $likers
 * @property int|null                                                           $likers_count
 * @property \App\Models\Poem                                                   $poem
 * @property Review|null                                                        $replyOfReview
 * @property \App\User                                                          $user
 */
class Review extends Model {
    use SoftDeletes;
    use LogsActivity;
    use Likeable;

    protected $table = 'review';

    protected static $logFillable             = true;
    protected static $logOnlyDirty            = true;
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
        return $this->user ? $this->user->avatarUrl : config('app.avatar.default');
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

    /*
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    // public function content() {
    //     return $this->hasOne(\App\Models\Content::class, 'id', 'content_id');
    // }
}
