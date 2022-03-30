<?php

namespace App\Models;

use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\MessageStatus.
 *
 * @property int $id
 * @mixin \Eloquent
 * @property int $user_id
 */
class MessageStatus {
    use LogsActivity;
    protected $table = 'message_status';

    public const STATUS = [
        'unread' => '0',
        'read'   => '1',
        'trash'  => '2',
    ];
    protected $fillable = [
        'notice_id',
        'user_id',
        'status'
    ];

    protected $casts = [
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(\App\Models\Message::class);
    }
}