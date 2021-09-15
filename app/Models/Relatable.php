<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/*
 * This model will be used to log activity.
 * It should be implements the Spatie\Activitylog\Contracts\Activity interface
 * and extend Illuminate\Database\Eloquent\Model.
 */

/**
 * App\Models\Relation.
 *
 * @property int                             $id
 * @property int                             $relation
 * @property string|null                     $start_type
 * @property int|null                        $start_id
 * @property string|null                     $end_type
 * @property int|null                        $end_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 * @method static authorHasAvatar(string $class, int $id)
 */
class Relatable extends MorphPivot {
    use LogsActivity;
    public $incrementing = true; // to avoid activity log subject_id null. see https://github.com/spatie/laravel-activitylog/issues/598#issuecomment-535009005

    protected static $logFillable          = true;
    protected static $logOnlyDirty         = true;
    public static $ignoreChangedAttributes = ['created_at', 'updated_at'];

    public const NODE_TYPE = [
        'poem'   => \App\Models\Poem::class,
        'author' => \App\Models\Author::class
    ];
    public const RELATION = [
        'poet_is'          => 0,
        'translator_is'    => 1,
        'merged_to_poem'   => 2,
        'merged_to_author' => 3,
        'heteronymy_of'    => 4,
        'has_egg'          => 5,
        'has_avatar'       => 6
    ];
    // TODO check for start_type and end_type before add relation
    // TODO check for relation limit before add relation(e.g. poem1 has been merged to poem2, then poem1 can not be merged to poem3)
    public const RELATION_RULES = [
        'poet_is'                    => ['start_type' => Poem::class, 'end_type' => Author::class, 'limit' => 1],
        'translator_is'              => ['start_type' => Poem::class, 'end_type' => Author::class, 'limit' => 6],
        'merged_to_poem'             => ['start_type' => Poem::class, 'end_type' => Poem::class, 'limit' => 1],
        'merged_to_author'           => ['start_type' => Author::class, 'end_type' => Author::class, 'limit' => 1],
        'has_egg'                    => ['start_type' => Poem::class, 'end_type' => Egg::class, 'limit' => 1],
        'author_has_avatar'          => ['start_type' => Author::class, 'end_type' => Egg::class, 'limit' => 1]
    ];

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $table    = 'relatable';
    protected $fillable = [
        'start_type',
        'start_id',
        'end_type',
        'end_id',
        'relation',
        'properties'
    ];
    protected $dates = [
        'updated_at',
        'created_at'
    ];

    // protected $appends = ['changes', 'logs'];

    public function start(): MorphTo {
        return $this->morphTo();
    }

    public function end(): MorphTo {
        return $this->morphTo();
    }

    public function scopeTranslatorIs($query, $startType, $startId) {
        $query->where([
            'relation'   => Relatable::RELATION['translator_is'],
            'start_type' => $startType,
            'start_id'   => $startId
        ]);
    }

    public function scopePoetIs($query, $startType, $startId) {
        $query->where([
            'relation'   => Relatable::RELATION['poet_is'],
            'start_type' => $startType,
            'start_id'   => $startId
        ]);
    }

    public function scopeMergedToPoem($query, $startType, $startId) {
        $query->where([
            'relation'   => Relatable::RELATION['merged_to_poem'],
            'start_type' => $startType,
            'start_id'   => $startId
        ])->limit(1);
    }

    public function scopeAuthorHasAvatar($query, $startType, $startId) {
        $query->where([
            'relation'   => Relatable::RELATION['author_has_avatar'],
            'start_type' => $startType,
            'start_id'   => $startId
        ]);
    }
}
