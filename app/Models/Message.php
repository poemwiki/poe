<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Message.
 *
 * @property int $id
 * @mixin \Eloquent
 * @property int                        $sender_id
 * @property int                        $receiver_id
 * @property int                        $translation_id
 * @property Translation|null           $translation
 * @property array                      $params
 * @property \Illuminate\Support\Carbon $created_at
 * @property \App\User|null             $sender
 * @property \App\User|null             $receiver
 * @property MessageStatus|null         $userStatus
 * @method static toUser(int $user)
 */
class Message extends Model {
    use LogsActivity;
    protected $table = 'message';

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at', 'updated_at']);
    }

    public const TYPE = [
        'reminder'      => '0', // normal system notice to specific user: "Your wallet has been activated.", "Your poem has a new comment", "Your poem NFT has sold to someone"
        'public'        => '1', // msg to all: notice for wallet activation, website update, new feature, etc
        'private'       => '2', // one to one private msg
    ];

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'translation_id',
        'type',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(\App\User::class, 'sender_id');
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(\App\User::class, 'receiver_id');
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(\App\Models\Translation::class);
    }

    public function status(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(\App\Models\MessageStatus::class);
    }

    public function userStatus(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(\App\Models\MessageStatus::class);
    }

    public function isReadBy(User $user): bool {
        return $this->status()->where('user_id', $user->id)->where('status', MessageStatus::STATUS['read'])->exists();
    }

    /**
     * @param     $query
     * @param int $userID
     * @return Builder
     */
    public function scopeToUser(Builder $query, int $userID): Builder {
        return $query->where('receiver_id', $userID)->orWhere('receiver_id', 0);
    }

    public function getTextAttribute(): string {
        $translation = $this->translation;

        return $translation ? trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, $this->params) : '';
    }

    public static function createReminder($userID, $translationID, $params) {
        return self::create([
            'sender_id'      => 2,
            'receiver_id'    => $userID,
            'translation_id' => $translationID,
            'type'           => self::TYPE['reminder'],
            'params'         => $params,
        ]);
    }

    public function updateOrCreateStatus($userID, $status) {
        return MessageStatus::updateOrCreate([
            'message_id' => $this->id,
            'user_id'    => $userID,
        ], [
            'status'    => $status,
        ]);
    }
}
