<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Reward
 *
 * @property int $user_id
 * @property int $reward_id     reward_result has reward_id unique constraint, to ensure a reward cannot gained twice or more times
 * @property int $campaign_id   reward_result has [user_id, campaign_id] unique constraint, to ensure a user cannot get 2 or more reward at one campaign
 * @package App
 */
class RewardResult extends Model{
    use LogsActivity;
    protected $table = 'reward_result';

    protected $fillable = [
        'user_id',
        'reward_id',
        'campaign_id'
    ];


    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function reward() {
        return $this->hasOne(\App\Models\Reward::class, 'id', 'reward_id');
    }
}