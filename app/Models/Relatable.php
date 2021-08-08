<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/*
 * This model will be used to log activity.
 * It should be implements the Spatie\Activitylog\Contracts\Activity interface
 * and extend Illuminate\Database\Eloquent\Model.
 */

/**
 * App\Models\Relation
 *
 * @property int $id
 * @property int relation
 * @property string|null $start_type
 * @property int|null $start_id
 * @property string|null $end_type
 * @property int|null $end_id
 * @property Collection|null $properties
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class Relatable extends MorphPivot {
    const NODE_TYPE = [
        'poem' => \App\Models\Poem::class,
        'author' => \App\Models\Author::class
    ];
    const RELATION = [
        'poet_is' => 0,
        'translator_is' => 1
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'relatable';
    protected $dates = [
        'updated_at',
        'created_at'
    ];

    // protected $appends = ['changes', 'logs'];

    public function start() : MorphTo {
        return $this->morphTo();
    }
    public function end() : MorphTo {
        return $this->morphTo();
    }

    public function scopeTranslatorIs($query, $startType, $startId) {
        $query->where([
            'relation' => Relatable::RELATION['translator_is'],
            'start_type' => $startType,
            'start_id' => $startId
        ]);
    }
}
