<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Reward
 *
 * @property int $campaign_id
 * @property string $reward
 * @package App
 */
class Reward extends Model{
    use SoftDeletes;
    use LogsActivity;
    protected $table = 'reward';

    protected $fillable = [
        'campaign_id',
        'reward'
    ];


    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function rewardResult() {
        return $this->belongsTo(\App\Models\RewardResult::class, 'id', 'reward_id');
    }
}