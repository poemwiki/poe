<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Award.
 *
 * @property int    $campaign_id
 * @property string $award_id
 */
class Award extends Model {
    use LogsActivity;

    public static $RESULT_TYPE = [
        'manually' => 0, // 后台选择获奖用户时，消耗一个reward写入到reward_result表
        'auto'     => 1  // 参与活动的用户点击领奖页面时，消耗一个reward写入到reward_result表，先到先得
    ];

    protected $table = 'award';

    protected static $logFillable          = true;
    protected static $logOnlyDirty         = true;
    public static $ignoreChangedAttributes = ['created_at', 'updated_at'];

    protected $fillable = [
        'result_type',
        'name',
        'campaign_id'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function reward() {
        return $this->hasMany(\App\Models\Reward::class, 'award_id', 'id');
    }
}
