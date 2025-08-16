<?php

/*
 * This file is part of the poemwiki.
 * (c) poemwiki <poemwiki@126.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Reward.
 *
 * @property int    $campaign_id
 * @property string $award_id
 */
class Reward extends Model {
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'reward';

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['created_at', 'updated_at']);
    }

    protected $fillable = [
        'campaign_id',
        'reward',
        'award_id'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function rewardResult() {
        return $this->belongsTo(\App\Models\RewardResult::class, 'id', 'reward_id');
    }

    public function award() {
        return $this->belongsTo(\App\Models\Award::class, 'award_id', 'id');
    }
}
