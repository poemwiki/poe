<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\MessageStatus.
 *
 * @property int $id
 * @mixin \Eloquent
 * @property int $user_id
 * @property int $status
 */
class MessageStatus extends Model {
    use LogsActivity;
    protected $table = 'message_status';

    public const STATUS = [
        'unread' => 0,
        'read'   => 1,
        'trash'  => 2,
    ];
    protected $fillable = [
        'message_id', // message id
        'user_id',
        'status'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function message(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(\App\Models\Message::class);
    }
}