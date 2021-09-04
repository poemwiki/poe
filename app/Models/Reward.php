<?php

/*
 * This file is part of the poemwiki.
 * (c) poemwiki <poemwiki@126.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Reward.
 *
 * @property int    $campaign_id
 * @property string $reward
 */
class Reward extends Model {
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'reward';

    protected static $logFillable          = true;
    protected static $logOnlyDirty         = true;
    public static $ignoreChangedAttributes = ['created_at', 'updated_at'];

    protected $fillable = [
        'campaign_id',
        'reward',
        'level'
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
