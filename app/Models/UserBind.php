<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\UserBind.
 *
 * @property int                                                                                                       $id
 * @property string                                                                                                    $open_id
 * @property int                                                                                                       $open_id_crc32
 * @property string                                                                                                    $union_id
 * @property int                                                                                                       $union_id_crc32
 * @property int                                                                                                       $user_id
 * @property int                                                                                                       $bind_status
 * @property int                                                                                                       $bind_ref            绑定来源：0：微信内授权 1：微信扫码登录 2:微信小程序登录
 * @property string|null                                                                                               $nickname
 * @property string|null                                                                                               $tel
 * @property string|null                                                                                               $email
 * @property string|null                                                                                               $avatar
 * @property int                                                                                                       $gender              0:unknow 1:male 2:female
 * @property mixed|null                                                                                                $info
 * @property string|null                                                                                               $deleted_at
 * @property \Illuminate\Support\Carbon|null                                                                           $created_at
 * @property \Illuminate\Support\Carbon|null                                                                           $updated_at
 * @property string|null                                                                                               $weapp_session_key
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\ActivityLog[]                                        $activities
 * @property int|null                                                                                                  $activities_count
 * @property \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[]                                       $clients
 * @property int|null                                                                                                  $clients_count
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property int|null                                                                                                  $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[]                                        $tokens
 * @property int|null                                                                                                  $tokens_count
 * @property \App\User                                                                                                 $user
 */
class UserBind extends Model {
    use HasApiTokens;
    use Notifiable;
    use LogsActivity;

    protected $table      = 'user_bind_info';
    public const BIND_REF = [
        'wechat'      => 0, // 微信内授权（微信内置浏览器打开时，使用微信登录）
        'wechat-scan' => 1, // 微信扫码登录
        'weapp'       => 2, // 微信小程序登录
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at']);
    }

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'open_id',
        'union_id',
        'user_id',
        'bind_status',
        'bind_ref',
        'nickname',
        'avatar',
        'gender',
        'info',
        'tel',
        'email',
        'weapp_session_key',
    ];

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->open_id_crc32  = Str::of($model->open_id)->crc32();
            $model->union_id_crc32 = Str::of($model->union_id)->crc32();
        });

        self::updating(function ($model) {
            $model->open_id_crc32  = Str::of($model->open_id)->crc32();
            $model->union_id_crc32 = Str::of($model->union_id)->crc32();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }
}
