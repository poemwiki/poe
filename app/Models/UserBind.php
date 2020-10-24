<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class UserBind extends Model {
    protected $table = 'user_bind_info';
    const BIND_REF = [
        'wechat' => 0,
        'wechat-scan' => 1
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
        'info'
    ];

    public static function boot() {
        parent::boot();

        // TODO check if created same poem by hash
        self::creating(function ($model) {
            $model->open_id_crc32 = self::crc32($model->open_id);
            $model->union_id_crc32 = self::crc32($model->union_id);
        });


        self::updating(function ($model) {
            $model->open_id_crc32 = self::crc32($model->open_id);
            $model->union_id_crc32 = self::crc32($model->union_id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     **/
    public function user() {
        return $this->belongsTo(\App\User::class, 'user_id', 'id');
    }

    /**
     * @desc CRC32的修正方法，修正php x86模式下出现的负值情况
     *
     * @param $str
     *
     * @return string
     */
    public static function crc32($str)
    {
        return sprintf("%u", crc32($str));
    }
}
