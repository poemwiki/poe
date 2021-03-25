<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

use Laravel\Passport\HasApiTokens;

class UserBind extends Model {
    use HasApiTokens, Notifiable;

    protected $table = 'user_bind_info';
    const BIND_REF = [
        'wechat' => 0,
        'wechat-scan' => 1,
        'weapp' => 2,
    ];

    use LogsActivity;

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $ignoreChangedAttributes = ['created_at'];

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
            $model->open_id_crc32 = Str::of($model->open_id)->crc32();
            $model->union_id_crc32 = Str::of($model->union_id)->crc32();
        });

        self::updating(function ($model) {
            $model->open_id_crc32 = Str::of($model->open_id)->crc32();
            $model->union_id_crc32 = Str::of($model->union_id)->crc32();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }


    public function findForPassport($openId) {
        return $this->where('open_id', $openId)->first();
    }
}
