<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Events\Liked;
use App\Events\Unliked;

/**
 * App\Models\Like
 *
 * @property int $id
 * @property int $user_id
 * @property int $likeable_id
 * @property string $likeable_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Model|\Eloquent $likeable
 * @property-read Like $liker
 * @property-read Like $user
 * @method static Builder|Like newModelQuery()
 * @method static Builder|Like newQuery()
 * @method static Builder|Like query()
 * @method static Builder|Like whereCreatedAt($value)
 * @method static Builder|Like whereId($value)
 * @method static Builder|Like whereLikeableId($value)
 * @method static Builder|Like whereLikeableType($value)
 * @method static Builder|Like whereUpdatedAt($value)
 * @method static Builder|Like whereUserId($value)
 * @method static Builder|Like withType(string $type)
 */
class Like extends Model {
    protected $guarded = [];

    protected $dispatchesEvents = [
        'created' => Liked::class,
        'deleted' => Unliked::class,
    ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = []) {
        $this->table = 'likes';

        parent::__construct($attributes);
    }

    protected static function boot() {
        parent::boot();

        self::saving(function ($like) {
            $userForeignKey = 'user_id';
            $like->{$userForeignKey} = $like->{$userForeignKey} ?: auth()->id();

            // if (\config('like.uuids')) {
            //     $like->{$like->getKeyName()} = $like->{$like->getKeyName()} ?: (string)Str::orderedUuid();
            // }
        });
    }

    public function likeable(): \Illuminate\Database\Eloquent\Relations\MorphTo {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(Like::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function liker() {
        return $this->user();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithType(Builder $query, string $type) {
        return $query->where('likeable_type', app($type)->getMorphClass());
    }
}