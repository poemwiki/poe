<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/*
 * This model will be used to log activity.
 * It should be implements the Spatie\Activitylog\Contracts\Activity interface
 * and extend Illuminate\Database\Eloquent\Model.
 */

/**
 * App\Models\ActivityLog.
 *
 * @property int                                           $id
 * @property string|null                                   $log_name
 * @property string                                        $description
 * @property string|null                                   $subject_type
 * @property int|null                                      $subject_id
 * @property string|null                                   $causer_type
 * @property int|null                                      $causer_id
 * @property Collection|null                               $properties
 * @property \Illuminate\Support\Carbon|null               $created_at
 * @property \Illuminate\Support\Carbon|null               $updated_at
 * @property \Illuminate\Database\Eloquent\Model|\Eloquent $causer
 * @property Collection                                    $change
 * @property Collection                                    $changes
 * @property array|bool                                    $diffs
 * @property \Illuminate\Database\Eloquent\Model|\Eloquent $subject
 * @method static Builder|Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static Builder|Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static Builder|Activity inLog($logNames)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereLogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityLog extends Activity {
    public const SUBJECT = [
        'poem'     => 'App\\Models\\Poem',
        'score'    => 'App\\Models\\Score',
        'review'   => 'App\\Models\\Review',
        'userBind' => 'App\\Models\\UserBind',
    ];

    // protected $appends = ['changes', 'logs'];

    /**
     * diff array for each updated attribute.
     * if $this->description !== 'updated' , the diff array will be []
     * eg: [
     *   'poem' => ['old' => 'xx', 'new' => 'xxx'],
     *   'title' => ['old' => 'a', 'new' => 'aa']
     * ].
     * @return array|bool
     */
    public function getDiffsAttribute() {
        $oldVal = $this->change->get('old');
        $diff   = [];

        if ($this->description === 'updated') {
            $keys   = array_keys($oldVal);
            $newVal = $this->properties->get('attributes');
            foreach ($keys as $key) {
                if (isset($newVal[$key])) {
                    $diff[$key] = [
                        'old' => $oldVal[$key],
                        'new' => $newVal[$key]
                    ];
                }
            }
        }

        return $diff;
    }

    public function getChangeAttribute(): Collection {
        return $this->changes();
    }
}
