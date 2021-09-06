<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Reward.
 *
 * @property int $user_id
 * @property int $reward_id   reward_result has reward_id unique constraint, to ensure a reward cannot gained twice or more times
 * @property int $campaign_id reward_result has [user_id, campaign_id] unique constraint, to ensure a user cannot get 2 or more reward at one campaign
 */
class RewardResult extends Model {
    use LogsActivity;
    protected $table = 'reward_result';

    protected $fillable = [
        'user_id',
        'reward_id',
        'campaign_id',
        'poem_id'
    ];

    // TODO add opened_at to know the moment that the user opened the reward/show page
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function reward() {
        return $this->hasOne(\App\Models\Reward::class, 'id', 'reward_id');
    }

    public function poem() {
        return $this->belongsTo(\App\Models\Poem::class, 'poem_id', 'id');
    }

    public function campaign() {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'id');
    }
}
