<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/*
 * This model will be used to log activity.
 * It should be implements the Spatie\Activitylog\Contracts\Activity interface
 * and extend Illuminate\Database\Eloquent\Model.
 */

class ActivityLog extends Activity {
    const SUBJECT = [
        'poem' => 'App\\Models\\Poem',
        'score' => 'App\\Models\\Score',
        'review' => 'App\\Models\\Review',
        'userBind' => 'App\\Models\\UserBind',
    ];

    // protected $appends = ['changes', 'logs'];

    /**
     * diff array for each updated attribute.
     * if $this->description !== 'updated' , the diff array will be []
     * eg: [
     *   'poem' => ['old' => 'xx', 'new' => 'xxx'],
     *   'title' => ['old' => 'a', 'new' => 'aa']
     * ]
     * @return array|bool
     */
    public function getDiffsAttribute() {
        $oldVal = $this->change->get('old');
        $diff = [];

        if ($this->description === 'updated') {
            $keys = array_keys($oldVal);
            $newVal = $this->properties->get('attributes');
            foreach ($keys as $key) {
                if(isset($newVal[$key])) {
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
